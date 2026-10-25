<?php
namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Officials;
use App\Core\Session;
use App\Core\Upload;

/** Admin → People & Community → Officials (current & past office bearers). */
final class OfficialAdminController extends Controller
{
    private function authorize(): array
    {
        $u = Auth::requireAdmin();
        if (!Auth::can('manage_content') && !Auth::can('manage_volunteers')) { http_response_code(403); exit('Forbidden'); }
        Officials::ensureTable();
        return $u;
    }

    public function index(): void
    {
        $u = $this->authorize();
        $tab = (string)($_GET['status'] ?? 'current');
        if (!in_array($tab, Officials::STATUSES, true)) $tab = 'current';
        $q = trim((string)($_GET['q'] ?? ''));
        $where = 'WHERE status=?';
        $params = [$tab];
        if ($q !== '') { $where .= ' AND (full_name LIKE ? OR position LIKE ?)'; $params[] = '%' . $q . '%'; $params[] = '%' . $q . '%'; }
        $total = (int)(Database::one('SELECT COUNT(*) c FROM foundation_officials ' . $where, $params)['c'] ?? 0);
        $pagination = admin_pager($total, 12);
        $order = $tab === 'past' ? 'COALESCE(term_end,term_start,0) DESC, sort_order, full_name' : 'sort_order, full_name';
        $rows = Database::all('SELECT * FROM foundation_officials ' . $where . ' ORDER BY ' . $order . ' LIMIT ' . (int)$pagination['per_page'] . ' OFFSET ' . (int)$pagination['offset'], $params);
        $this->view('admin/officials', [
            'user' => $u, 'officials' => $rows, 'tab' => $tab, 'q' => $q, 'counts' => Officials::counts(),
            'pagination' => $pagination, 'adminTitle' => 'Foundation Officials',
        ], 'admin');
    }

    public function save(): void
    {
        $this->requirePost();
        $u = $this->authorize();
        $id = (int)($_POST['id'] ?? 0);
        $name = mb_substr(trim((string)($_POST['full_name'] ?? '')), 0, 160);
        $position = mb_substr(trim((string)($_POST['position'] ?? '')), 0, 140);
        $status = (string)($_POST['status'] ?? 'current');
        if (!in_array($status, Officials::STATUSES, true)) $status = 'current';
        $back = 'admin/officials?status=' . $status;
        if (mb_strlen($name) < 2 || mb_strlen($position) < 2) { Session::flash('error', 'Full name and position are required.'); redirect($back); }
        $year = static function ($v): ?int { $v = (int)$v; return ($v >= 1950 && $v <= 2100) ? $v : null; };
        $start = $year($_POST['term_start'] ?? null);
        $end = $year($_POST['term_end'] ?? null);
        if ($start && $end && $end < $start) { Session::flash('error', 'The term end year must be the same as or after the start year.'); redirect($back); }
        $email = trim((string)($_POST['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { Session::flash('error', 'Enter a valid email address or leave it empty.'); redirect($back); }
        $photo = null;
        try {
            if (isset($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) $photo = Upload::publicImage($_FILES['photo'], 'officials');
        } catch (\Throwable $e) { Session::flash('error', $e->getMessage()); redirect($back); }
        $data = [
            $name, $position, $start, $end, $status,
            mb_substr(trim((string)($_POST['bio'] ?? '')), 0, 1200) ?: null,
            mb_substr($email, 0, 190) ?: null,
            mb_substr(trim((string)($_POST['phone'] ?? '')), 0, 60) ?: null,
            isset($_POST['show_contact']) ? 1 : 0,
            (int)($_POST['sort_order'] ?? 0),
            isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($id) {
            $old = Database::one('SELECT photo_path FROM foundation_officials WHERE id=?', [$id]);
            if (!$old) { Session::flash('error', 'Official not found.'); redirect($back); }
            $photoPath = isset($_POST['remove_photo']) ? null : ($photo ?: $old['photo_path']);
            Database::exec('UPDATE foundation_officials SET full_name=?,position=?,term_start=?,term_end=?,status=?,bio=?,email=?,phone=?,show_contact=?,sort_order=?,is_active=?,photo_path=? WHERE id=?', [...$data, $photoPath, $id]);
            Audit::write((int)$u['id'], 'OFFICIAL_UPDATED', 'officials', $id, 'Official updated: ' . $name);
            Session::flash('success', 'Official profile updated.');
        } else {
            $id = Database::insert('INSERT INTO foundation_officials(full_name,position,term_start,term_end,status,bio,email,phone,show_contact,sort_order,is_active,photo_path,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)', [...$data, $photo, (int)$u['id']]);
            Audit::write((int)$u['id'], 'OFFICIAL_CREATED', 'officials', $id, 'Official added: ' . $name);
            Session::flash('success', 'Official added to the ' . ($status === 'past' ? 'Past Officials' : 'Officials') . ' page.');
        }
        redirect($back);
    }

    /** Move a current official to Past Officials (or back) in one click. */
    public function status(): void
    {
        $this->requirePost();
        $u = $this->authorize();
        $id = (int)($_POST['id'] ?? 0);
        $row = Database::one('SELECT id,status,term_end,full_name FROM foundation_officials WHERE id=?', [$id]);
        if (!$row) { Session::flash('error', 'Official not found.'); redirect('admin/officials'); }
        $to = $row['status'] === 'current' ? 'past' : 'current';
        $termEnd = $to === 'past' ? ((int)$row['term_end'] ?: (int)date('Y')) : null;
        Database::exec('UPDATE foundation_officials SET status=?, term_end=? WHERE id=?', [$to, $termEnd, $id]);
        Audit::write((int)$u['id'], 'OFFICIAL_STATUS', 'officials', $id, $row['full_name'] . ' moved to ' . $to);
        Session::flash('success', $row['full_name'] . ($to === 'past' ? ' moved to Past Officials.' : ' restored to current Officials.'));
        redirect('admin/officials?status=' . $row['status']);
    }

    public function delete(): void
    {
        $this->requirePost();
        $u = $this->authorize();
        if (($u['role'] ?? '') !== 'super_admin' && !Auth::can('manage_content')) { http_response_code(403); exit('Forbidden'); }
        $id = (int)($_POST['id'] ?? 0);
        $row = Database::one('SELECT status,photo_path,full_name FROM foundation_officials WHERE id=?', [$id]);
        if ($row) {
            Database::exec('DELETE FROM foundation_officials WHERE id=?', [$id]);
            if (!empty($row['photo_path'])) Upload::removePublicImage($row['photo_path']);
            Audit::write((int)$u['id'], 'DELETE', 'officials', $id, 'Official deleted: ' . $row['full_name']);
            Session::flash('success', 'Official removed.');
        }
        redirect('admin/officials?status=' . ($row['status'] ?? 'current'));
    }
}
