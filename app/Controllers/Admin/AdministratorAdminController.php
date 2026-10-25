<?php
namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Upload;

/**
 * System → Administrators (Super Admin only).
 * Create administrator accounts, assign roles, reset passwords and control access.
 */
final class AdministratorAdminController extends Controller
{
    public const ROLES = [
        'super_admin' => ['Super Admin', 'Full access, including administrators, security and payment settings.'],
        'administrator' => ['Administrator', 'Manages fundraising, people, content, branding and messages.'],
        'finance_manager' => ['Finance Manager', 'Donations, verification, campaigns and online payments.'],
        'volunteer_manager' => ['Volunteer Manager', 'Volunteer approvals, profiles and portal messages.'],
        'content_manager' => ['Content Manager', 'Website content, pages, gallery, news and events.'],
        'event_manager' => ['Event Manager', 'Events and related website content.'],
        'support_agent' => ['Support Agent', 'Portal messages and website live chat.'],
    ];
    public const STATUSES = ['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'];

    private function authorize(): array
    {
        $u = Auth::requireAdmin();
        if (($u['role'] ?? '') !== 'super_admin') { http_response_code(403); exit('Only a Super Admin can manage administrator accounts.'); }
        return $u;
    }

    private static function roleKeys(): array { return array_keys(self::ROLES); }

    private static function passwordProblem(string $password): ?string
    {
        if (strlen($password) < 12) return 'Password must be at least 12 characters.';
        if (strlen($password) > 128) return 'Password must be 128 characters or fewer.';
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
            return 'Password must include upper and lower case letters, a number and a symbol.';
        }
        return null;
    }

    private static function hash(string $password): string
    {
        return password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
    }

    private static function activeSuperAdmins(?int $excludeId = null): int
    {
        $sql = 'SELECT COUNT(*) c FROM users WHERE role="super_admin" AND status="active"';
        $params = [];
        if ($excludeId) { $sql .= ' AND id<>?'; $params[] = $excludeId; }
        return (int)(Database::one($sql, $params)['c'] ?? 0);
    }

    public function index(): void
    {
        $u = $this->authorize();
        $roles = self::roleKeys();
        $in = implode(',', array_fill(0, count($roles), '?'));
        $q = trim((string)($_GET['q'] ?? ''));
        $role = (string)($_GET['role'] ?? '');
        $where = 'WHERE role IN (' . $in . ')';
        $params = $roles;
        if ($q !== '') { $where .= ' AND (name LIKE ? OR email LIKE ? OR username LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
        if (isset(self::ROLES[$role])) { $where .= ' AND role=?'; $params[] = $role; }
        $total = (int)(Database::one('SELECT COUNT(*) c FROM users ' . $where, $params)['c'] ?? 0);
        $pagination = admin_pager($total, 20);
        $rows = Database::all('SELECT id,name,email,username,role,status,profile_photo,two_factor_enabled,last_login_at,created_at FROM users ' . $where . ' ORDER BY FIELD(role,"super_admin","administrator","finance_manager","volunteer_manager","content_manager","event_manager","support_agent"), name LIMIT ' . (int)$pagination['per_page'] . ' OFFSET ' . (int)$pagination['offset'], $params);
        $stats = Database::one('SELECT COUNT(*) total, SUM(CASE WHEN status="active" THEN 1 ELSE 0 END) active, SUM(CASE WHEN status<>"active" THEN 1 ELSE 0 END) blocked, SUM(CASE WHEN two_factor_enabled=1 THEN 1 ELSE 0 END) twofa FROM users WHERE role IN (' . $in . ')', $roles) ?: [];
        $this->view('admin/administrators', [
            'user' => $u, 'admins' => $rows, 'stats' => $stats, 'q' => $q, 'roleFilter' => $role,
            'roles' => self::ROLES, 'statuses' => self::STATUSES, 'pagination' => $pagination, 'adminTitle' => 'Administrators',
        ], 'admin');
    }

    public function save(): void
    {
        $this->requirePost();
        $u = $this->authorize();
        $id = (int)($_POST['id'] ?? 0);
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 120);
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $username = strtolower(trim((string)($_POST['username'] ?? '')));
        $role = (string)($_POST['role'] ?? 'administrator');
        $status = (string)($_POST['status'] ?? 'active');
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirmation'] ?? '');

        if (mb_strlen($name) < 2) { Session::flash('error', 'Enter the administrator’s full name.'); redirect('admin/administrators'); }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) { Session::flash('error', 'Enter a valid email address.'); redirect('admin/administrators'); }
        if ($username !== '' && !preg_match('/^[a-z0-9._-]{3,40}$/', $username)) { Session::flash('error', 'Username must be 3–40 characters: letters, numbers, dots, dashes or underscores.'); redirect('admin/administrators'); }
        if (!isset(self::ROLES[$role])) { Session::flash('error', 'Choose a valid role.'); redirect('admin/administrators'); }
        if (!isset(self::STATUSES[$status])) $status = 'active';

        $dupe = Database::one('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1', [$email, $id]);
        if ($dupe) { Session::flash('error', 'That email address is already used by another account.'); redirect('admin/administrators'); }
        if ($username !== '' && Database::one('SELECT id FROM users WHERE username=? AND id<>? LIMIT 1', [$username, $id])) { Session::flash('error', 'That username is already taken.'); redirect('admin/administrators'); }

        if (!$id || $password !== '') {
            if ($problem = self::passwordProblem($password)) { Session::flash('error', $problem); redirect('admin/administrators'); }
            if ($password !== $confirm) { Session::flash('error', 'The two password fields do not match.'); redirect('admin/administrators'); }
        }

        $photo = null;
        try {
            if (isset($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) $photo = Upload::publicImage($_FILES['photo'], 'profiles');
        } catch (\Throwable $e) { Session::flash('error', $e->getMessage()); redirect('admin/administrators'); }

        if ($id) {
            $old = Database::one('SELECT id,role,status,profile_photo FROM users WHERE id=? LIMIT 1', [$id]);
            if (!$old || !isset(self::ROLES[$old['role']])) { Session::flash('error', 'Administrator not found.'); redirect('admin/administrators'); }
            if ($id === (int)$u['id'] && ($role !== 'super_admin' || $status !== 'active')) { Session::flash('error', 'You cannot change your own role or deactivate your own account.'); redirect('admin/administrators'); }
            if ($old['role'] === 'super_admin' && ($role !== 'super_admin' || $status !== 'active') && self::activeSuperAdmins($id) === 0) { Session::flash('error', 'At least one active Super Admin must remain.'); redirect('admin/administrators'); }
            $photoPath = isset($_POST['remove_photo']) ? null : ($photo ?: $old['profile_photo']);
            Database::exec('UPDATE users SET name=?,email=?,username=?,role=?,status=?,profile_photo=? WHERE id=?', [$name, $email, $username ?: null, $role, $status, $photoPath, $id]);
            if ($password !== '') Database::exec('UPDATE users SET password_hash=? WHERE id=?', [self::hash($password), $id]);
            Audit::write((int)$u['id'], 'ADMIN_UPDATED', 'administrators', $id, 'Administrator updated: ' . $email . ' (' . $role . ($password !== '' ? ', password reset' : '') . ')');
            Session::flash('success', $name . '’s account was updated' . ($password !== '' ? ' and the password was reset.' : '.'));
        } else {
            $id = Database::insert('INSERT INTO users(name,email,username,password_hash,role,status,profile_photo) VALUES(?,?,?,?,?,?,?)', [$name, $email, $username ?: null, self::hash($password), $role, $status, $photo]);
            Audit::write((int)$u['id'], 'ADMIN_CREATED', 'administrators', $id, 'Administrator created: ' . $email . ' (' . $role . ')');
            Session::flash('success', $name . ' can now sign in at ' . absolute_url('admin/login') . ' as ' . self::ROLES[$role][0] . '.');
        }
        redirect('admin/administrators');
    }

    /** Activate / suspend in one click. */
    public function status(): void
    {
        $this->requirePost();
        $u = $this->authorize();
        $id = (int)($_POST['id'] ?? 0);
        $row = Database::one('SELECT id,name,role,status FROM users WHERE id=? LIMIT 1', [$id]);
        if (!$row || !isset(self::ROLES[$row['role']])) { Session::flash('error', 'Administrator not found.'); redirect('admin/administrators'); }
        if ($id === (int)$u['id']) { Session::flash('error', 'You cannot suspend your own account.'); redirect('admin/administrators'); }
        $to = $row['status'] === 'active' ? 'suspended' : 'active';
        if ($to !== 'active' && $row['role'] === 'super_admin' && self::activeSuperAdmins($id) === 0) { Session::flash('error', 'At least one active Super Admin must remain.'); redirect('admin/administrators'); }
        Database::exec('UPDATE users SET status=? WHERE id=?', [$to, $id]);
        Audit::write((int)$u['id'], 'ADMIN_STATUS', 'administrators', $id, $row['name'] . ' set to ' . $to);
        Session::flash('success', $row['name'] . ($to === 'active' ? ' can sign in again.' : ' has been suspended and can no longer sign in.'));
        redirect('admin/administrators');
    }

    public function delete(): void
    {
        $this->requirePost();
        $u = $this->authorize();
        $id = (int)($_POST['id'] ?? 0);
        $row = Database::one('SELECT id,name,email,role,profile_photo FROM users WHERE id=? LIMIT 1', [$id]);
        if (!$row || !isset(self::ROLES[$row['role']])) { Session::flash('error', 'Administrator not found.'); redirect('admin/administrators'); }
        if ($id === (int)$u['id']) { Session::flash('error', 'You cannot delete your own account.'); redirect('admin/administrators'); }
        if ($row['role'] === 'super_admin' && self::activeSuperAdmins($id) === 0) { Session::flash('error', 'At least one active Super Admin must remain.'); redirect('admin/administrators'); }
        try {
            Database::exec('DELETE FROM users WHERE id=?', [$id]);
        } catch (\Throwable $e) {
            error_log('Administrator delete blocked: ' . $e->getMessage());
            Database::exec('UPDATE users SET status="inactive" WHERE id=?', [$id]);
            Session::flash('error', $row['name'] . ' is linked to existing records, so the account was deactivated instead of deleted.');
            redirect('admin/administrators');
        }
        Audit::write((int)$u['id'], 'DELETE', 'administrators', $id, 'Administrator deleted: ' . $row['email']);
        Session::flash('success', $row['name'] . '’s administrator account was deleted.');
        redirect('admin/administrators');
    }
}
