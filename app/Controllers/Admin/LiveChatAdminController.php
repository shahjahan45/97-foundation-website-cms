<?php
namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

final class LiveChatAdminController extends Controller
{
    private ?array $chatColumns = null;

    private function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function wantsJson(): bool
    {
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        $requestedWith = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        return str_contains($accept, 'application/json') || in_array($requestedWith, ['xmlhttprequest', '97foundation-adminchat'], true);
    }

    private function columns(): array
    {
        if ($this->chatColumns !== null) return $this->chatColumns;
        $this->chatColumns = [];
        try {
            $rows = Database::all('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="chat_conversations"');
            foreach ($rows as $row) $this->chatColumns[(string)$row['COLUMN_NAME']] = true;
        } catch (\Throwable) {
            // The base V4 schema remains usable even if the optional V4.1 migration was not run yet.
        }
        return $this->chatColumns;
    }

    private function hasColumn(string $column): bool
    {
        return isset($this->columns()[$column]);
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, ['open', 'waiting', 'closed'], true) ? $status : '';
    }

    private function buildReturnUrl(int $id = 0): string
    {
        $query = [];
        if ($id > 0) $query['chat'] = $id;
        $status = $this->normalizeStatus((string)($_POST['return_status'] ?? ''));
        $q = trim((string)($_POST['return_q'] ?? ''));
        $page = max(1, (int)($_POST['return_page'] ?? 1));
        $perPage = (int)($_POST['return_per_page'] ?? 25);
        if (!in_array($perPage, [10,20,25,50,100], true)) $perPage = 25;
        if ($status !== '') $query['status'] = $status;
        if ($q !== '') $query['q'] = $q;
        if ($page > 1) $query['page'] = $page;
        if ($perPage !== 25) $query['per_page'] = $perPage;
        return 'admin/live-chat' . ($query ? '?' . http_build_query($query) : '');
    }

    private function loadWorkspace(bool $markRead = true): array
    {
        $status = $this->normalizeStatus((string)($_GET['status'] ?? ''));
        $q = trim((string)($_GET['q'] ?? ''));
        $selected = max(0, (int)($_GET['chat'] ?? 0));
        $params = [];
        $where = '1=1';

        if ($status !== '') {
            $where .= ' AND c.status=?';
            $params[] = $status;
        }
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where .= ' AND (c.visitor_name LIKE ? OR c.visitor_email LIKE ? OR c.visitor_phone LIKE ? OR CAST(c.id AS CHAR) LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }

        $total = (int)(Database::one('SELECT COUNT(*) c FROM chat_conversations c WHERE ' . $where, $params)['c'] ?? 0);
        $pager = admin_pager($total, 25);

        $unreadSql = $this->hasColumn('admin_last_read_at')
            ? '(SELECT COUNT(*) FROM chat_messages unread WHERE unread.conversation_id=c.id AND unread.sender_type="visitor" AND (c.admin_last_read_at IS NULL OR unread.created_at>c.admin_last_read_at))'
            : '(SELECT COUNT(*) FROM chat_messages unread WHERE unread.conversation_id=c.id AND unread.sender_type="visitor" AND unread.id>COALESCE((SELECT MAX(sent.id) FROM chat_messages sent WHERE sent.conversation_id=c.id AND sent.sender_type="admin"),0))';
        $onlineSql = $this->hasColumn('visitor_last_seen_at')
            ? 'IF(c.status<>"closed" AND c.visitor_last_seen_at>=DATE_SUB(NOW(),INTERVAL 35 SECOND),1,0)'
            : 'IF(c.status<>"closed" AND c.last_message_at>=DATE_SUB(NOW(),INTERVAL 5 MINUTE),1,0)';

        $rows = Database::all(
            'SELECT c.*,u.name assigned_name,' .
            '(SELECT message FROM chat_messages cm WHERE cm.conversation_id=c.id ORDER BY cm.id DESC LIMIT 1) last_message,' .
            $unreadSql . ' unread_count,' . $onlineSql . ' is_online ' .
            'FROM chat_conversations c LEFT JOIN users u ON u.id=c.assigned_admin_id ' .
            'WHERE ' . $where . ' ORDER BY c.last_message_at DESC,c.id DESC LIMIT ' . $pager['per_page'] . ' OFFSET ' . $pager['offset'],
            $params
        );

        if ($selected === 0 && !empty($rows)) $selected = (int)$rows[0]['id'];
        $chat = $selected > 0
            ? Database::one('SELECT c.*,u.name assigned_name,' . $onlineSql . ' is_online FROM chat_conversations c LEFT JOIN users u ON u.id=c.assigned_admin_id WHERE c.id=?', [$selected])
            : null;

        if ($chat && $markRead && $this->hasColumn('admin_last_read_at')) {
            Database::exec('UPDATE chat_conversations SET admin_last_read_at=NOW() WHERE id=?', [$selected]);
            foreach ($rows as &$row) if ((int)$row['id'] === $selected) $row['unread_count'] = 0;
            unset($row);
        }

        $messages = $chat
            ? Database::all('SELECT id,sender_type,sender_user_id,sender_name,message,created_at FROM chat_messages WHERE conversation_id=? ORDER BY id ASC LIMIT 500', [$selected])
            : [];

        $counts = Database::one(
            'SELECT COUNT(*) total,' .
            'COALESCE(SUM(status="open"),0) open_count,' .
            'COALESCE(SUM(status="waiting"),0) waiting_count,' .
            'COALESCE(SUM(status="closed"),0) closed_count FROM chat_conversations'
        ) ?: [];

        return [
            'conversations' => $rows,
            'chat' => $chat,
            'messages' => $messages,
            'pagination' => $pager,
            'status' => $status,
            'q' => $q,
            'counts' => $counts,
            'features' => [
                'notes' => $this->hasColumn('admin_notes'),
                'presence' => $this->hasColumn('visitor_last_seen_at'),
                'unread_tracking' => $this->hasColumn('admin_last_read_at'),
            ],
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'waiting' => 'Pending',
            'closed' => 'Closed',
            default => 'Open',
        };
    }

    private function timeLabel(?string $value, string $format): string
    {
        if (!$value) return '';
        $time = strtotime($value);
        return $time ? date($format, $time) : '';
    }

    private function workspacePayload(array $workspace): array
    {
        $conversations = array_map(function (array $c): array {
            return [
                'id' => (int)$c['id'],
                'visitor_name' => (string)$c['visitor_name'],
                'visitor_email' => (string)$c['visitor_email'],
                'visitor_phone' => (string)($c['visitor_phone'] ?? ''),
                'category' => (string)($c['category'] ?? 'general'),
                'last_message' => (string)($c['last_message'] ?? ''),
                'last_time' => $this->timeLabel($c['last_message_at'] ?? null, 'H:i'),
                'status' => (string)$c['status'],
                'status_label' => $this->statusLabel((string)$c['status']),
                'unread_count' => (int)($c['unread_count'] ?? 0),
                'is_online' => (bool)($c['is_online'] ?? false),
            ];
        }, $workspace['conversations']);

        $chat = null;
        if (!empty($workspace['chat'])) {
            $c = $workspace['chat'];
            $chat = [
                'id' => (int)$c['id'],
                'visitor_name' => (string)$c['visitor_name'],
                'visitor_email' => (string)$c['visitor_email'],
                'visitor_phone' => (string)($c['visitor_phone'] ?? ''),
                'category' => (string)($c['category'] ?? 'general'),
                'status' => (string)$c['status'],
                'status_label' => $this->statusLabel((string)$c['status']),
                'assigned_name' => (string)($c['assigned_name'] ?? ''),
                'created_at' => (string)$c['created_at'],
                'started_label' => $this->timeLabel($c['created_at'] ?? null, 'd M Y · H:i'),
                'last_activity_label' => $this->timeLabel($c['last_message_at'] ?? null, 'd M Y · H:i'),
                'is_online' => (bool)($c['is_online'] ?? false),
                'admin_notes' => (string)($c['admin_notes'] ?? ''),
            ];
        }

        $messages = array_map(function (array $m): array {
            $ts = strtotime((string)$m['created_at']);
            return [
                'id' => (int)$m['id'],
                'sender_type' => (string)$m['sender_type'],
                'sender_name' => (string)($m['sender_name'] ?: ucfirst((string)$m['sender_type'])),
                'message' => (string)$m['message'],
                'date_key' => $ts ? date('Y-m-d', $ts) : '',
                'date_label' => $ts ? (date('Y-m-d', $ts) === date('Y-m-d') ? 'Today' : (date('Y-m-d', $ts) === date('Y-m-d', strtotime('-1 day')) ? 'Yesterday' : date('d M Y', $ts))) : '',
                'time_label' => $ts ? date('H:i', $ts) : '',
            ];
        }, $workspace['messages']);

        return [
            'ok' => true,
            'conversations' => $conversations,
            'chat' => $chat,
            'messages' => $messages,
            'counts' => [
                'total' => (int)($workspace['counts']['total'] ?? 0),
                'open' => (int)($workspace['counts']['open_count'] ?? 0),
                'waiting' => (int)($workspace['counts']['waiting_count'] ?? 0),
                'closed' => (int)($workspace['counts']['closed_count'] ?? 0),
            ],
            'pagination' => [
                'page' => (int)($workspace['pagination']['page'] ?? 1),
                'pages' => (int)($workspace['pagination']['pages'] ?? 1),
                'total' => (int)($workspace['pagination']['total'] ?? 0),
                'per_page' => (int)($workspace['pagination']['per_page'] ?? 25),
            ],
            'status' => (string)$workspace['status'],
            'q' => (string)$workspace['q'],
            'features' => $workspace['features'],
        ];
    }

    public function index(): void
    {
        $user = Auth::requireAdmin();
        Auth::enforce('manage_messages');
        $workspace = $this->loadWorkspace(true);
        $this->view('admin/live-chat', array_merge($workspace, [
            'user' => $user,
            'adminTitle' => 'Live Chat',
            'adminBodyClass' => 'admin-live-chat-page',
            'adminPage' => 'live-chat',
        ]), 'admin');
    }

    public function feed(): void
    {
        Auth::requireAdmin();
        Auth::enforce('manage_messages');
        $this->json($this->workspacePayload($this->loadWorkspace(true)));
    }

    public function reply(): void
    {
        $this->requirePost();
        $user = Auth::requireAdmin();
        Auth::enforce('manage_messages');
        $id = (int)($_POST['conversation_id'] ?? 0);
        $message = substr(trim((string)($_POST['message'] ?? '')), 0, 3000);
        if (!$id || $message === '') {
            if ($this->wantsJson()) $this->json(['ok' => false, 'message' => 'Select a conversation and enter a reply.'], 422);
            Session::flash('error', 'Select a chat and enter a reply.');
            redirect($this->buildReturnUrl($id));
        }
        $conversation = Database::one('SELECT id,status FROM chat_conversations WHERE id=?', [$id]);
        if (!$conversation) {
            if ($this->wantsJson()) $this->json(['ok' => false, 'message' => 'Conversation not found.'], 404);
            Session::flash('error', 'Chat not found.');
            redirect($this->buildReturnUrl());
        }
        if (($conversation['status'] ?? '') === 'closed') {
            $closedMessage = 'This conversation is closed. Reopen it before sending another reply.';
            if ($this->wantsJson()) $this->json(['ok' => false, 'message' => $closedMessage], 409);
            Session::flash('error', $closedMessage);
            redirect($this->buildReturnUrl($id));
        }

        Database::transaction(function () use ($id, $message, $user): void {
            Database::insert('INSERT INTO chat_messages(conversation_id,sender_type,sender_user_id,sender_name,message) VALUES(?,"admin",?,?,?)', [$id, (int)$user['id'], $user['name'], $message]);
            $sql = 'UPDATE chat_conversations SET assigned_admin_id=?,status="open",last_message_at=NOW()';
            if ($this->hasColumn('admin_last_read_at')) $sql .= ',admin_last_read_at=NOW()';
            $sql .= ' WHERE id=?';
            Database::exec($sql, [(int)$user['id'], $id]);
        });
        Audit::write((int)$user['id'], 'CHAT_REPLY', 'live_chat', $id, 'Live chat reply');

        if ($this->wantsJson()) $this->json(['ok' => true, 'message' => 'Reply sent.']);
        Session::flash('success', 'Reply sent.');
        redirect($this->buildReturnUrl($id));
    }

    public function status(): void
    {
        $this->requirePost();
        $user = Auth::requireAdmin();
        Auth::enforce('manage_messages');
        $id = (int)($_POST['id'] ?? 0);
        $status = $this->normalizeStatus((string)($_POST['status'] ?? 'open')) ?: 'open';
        $conversation = $id ? Database::one('SELECT id,status FROM chat_conversations WHERE id=?', [$id]) : null;
        if (!$conversation) {
            if ($this->wantsJson()) $this->json(['ok' => false, 'message' => 'Conversation not found.'], 404);
            Session::flash('error', 'Chat not found.');
            redirect($this->buildReturnUrl());
        }
        $previous = (string)$conversation['status'];
        Database::transaction(function () use ($status, $previous, $id, $user): void {
            Database::exec('UPDATE chat_conversations SET status=?,assigned_admin_id=COALESCE(assigned_admin_id,?),last_message_at=IF(?="closed",NOW(),last_message_at) WHERE id=?', [$status, (int)$user['id'], $status, $id]);
            if ($status === 'closed' && $previous !== 'closed') {
                Database::insert('INSERT INTO chat_messages(conversation_id,sender_type,sender_user_id,sender_name,message) VALUES(?,"system",?,?,?)', [$id, (int)$user['id'], '97 Foundation Support', 'This conversation has been closed by the 97 Foundation Support team. Thank you for contacting us.']);
            } elseif ($previous === 'closed' && $status !== 'closed') {
                Database::insert('INSERT INTO chat_messages(conversation_id,sender_type,sender_user_id,sender_name,message) VALUES(?,"system",?,?,?)', [$id, (int)$user['id'], '97 Foundation Support', 'This conversation has been reopened by the support team. You can continue the conversation.']);
            }
        });
        Audit::write((int)$user['id'], 'CHAT_STATUS', 'live_chat', $id, 'Chat status ' . $status);
        $message = $status === 'closed' ? 'Conversation closed. The visitor has been notified.' : ($previous === 'closed' ? 'Conversation reopened.' : 'Conversation status updated.');

        if ($this->wantsJson()) $this->json(['ok' => true, 'message' => $message, 'status' => $status, 'status_label' => $this->statusLabel($status)]);
        Session::flash('success', $message);
        redirect($this->buildReturnUrl($id));
    }

    public function note(): void
    {
        $this->requirePost();
        $user = Auth::requireAdmin();
        Auth::enforce('manage_messages');
        $id = (int)($_POST['id'] ?? 0);
        $notes = substr(trim((string)($_POST['admin_notes'] ?? '')), 0, 5000);
        if (!$id || !Database::one('SELECT id FROM chat_conversations WHERE id=?', [$id])) {
            if ($this->wantsJson()) $this->json(['ok' => false, 'message' => 'Conversation not found.'], 404);
            Session::flash('error', 'Chat not found.');
            redirect($this->buildReturnUrl());
        }
        if (!$this->hasColumn('admin_notes')) {
            $message = 'Support notes require the included V4.1 database upgrade. Run database/upgrade_v4.php once.';
            if ($this->wantsJson()) $this->json(['ok' => false, 'message' => $message], 409);
            Session::flash('error', $message);
            redirect($this->buildReturnUrl($id));
        }
        Database::exec('UPDATE chat_conversations SET admin_notes=?,assigned_admin_id=COALESCE(assigned_admin_id,?) WHERE id=?', [$notes !== '' ? $notes : null, (int)$user['id'], $id]);
        Audit::write((int)$user['id'], 'CHAT_NOTE', 'live_chat', $id, 'Support note updated');
        if ($this->wantsJson()) $this->json(['ok' => true, 'message' => 'Support notes saved.']);
        Session::flash('success', 'Support notes saved.');
        redirect($this->buildReturnUrl($id));
    }

    public function delete(): void
    {
        $this->requirePost();
        $user = Auth::requireAdmin();
        if ($user['role'] !== 'super_admin') {
            if ($this->wantsJson()) $this->json(['ok' => false, 'message' => 'Only a Super Admin can permanently delete conversations.'], 403);
            http_response_code(403);
            exit('Forbidden');
        }
        $id = (int)($_POST['id'] ?? 0);
        if (!$id || !Database::one('SELECT id FROM chat_conversations WHERE id=?', [$id])) {
            if ($this->wantsJson()) $this->json(['ok' => false, 'message' => 'Conversation not found.'], 404);
            Session::flash('error', 'Chat not found.');
            redirect($this->buildReturnUrl());
        }
        Database::exec('DELETE FROM chat_conversations WHERE id=?', [$id]);
        Audit::write((int)$user['id'], 'DELETE', 'live_chat', $id, 'Chat deleted');
        if ($this->wantsJson()) $this->json(['ok' => true, 'message' => 'Conversation deleted.']);
        Session::flash('success', 'Chat deleted.');
        redirect($this->buildReturnUrl());
    }
}
