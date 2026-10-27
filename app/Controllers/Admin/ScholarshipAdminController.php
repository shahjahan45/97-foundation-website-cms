<?php
namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\PersonalDataVault;
use App\Core\Scholarship;
use App\Core\Session;

/**
 * V7.3 — scholarship & education support applications (authorised administrators only).
 * Decisions are always manual: nothing in this controller changes a status on its own.
 */
final class ScholarshipAdminController extends Controller
{
    private const REVEAL_SECONDS = 120;

    private function guard(): array
    {
        $u = Auth::requireAdmin();
        Auth::enforce('manage_scholarships');
        return $u;
    }

    /** @return array{0:string,1:array,2:array} where-clause, params, active filters */
    private function filters(): array
    {
        $f = [
            'q' => mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80),
            'status' => isset(Scholarship::STATUSES[$_GET['status'] ?? '']) ? (string)$_GET['status'] : '',
            'purpose' => isset(Scholarship::PURPOSES[$_GET['purpose'] ?? '']) ? (string)$_GET['purpose'] : '',
            'level' => isset(Scholarship::LEVELS[$_GET['level'] ?? '']) ? (string)$_GET['level'] : '',
            'eligibility' => isset(Scholarship::ELIGIBILITY[$_GET['eligibility'] ?? '']) ? (string)$_GET['eligibility'] : '',
            'flag' => ($_GET['flag'] ?? '') === 'duplicate' ? 'duplicate' : '',
            'nic' => '',
        ];
        $where = ['1=1'];
        $params = [];
        foreach (['status', 'purpose', 'eligibility'] as $k) {
            if ($f[$k] !== '') { $where[] = 'a.' . $k . '=?'; $params[] = $f[$k]; }
        }
        if ($f['level'] !== '') { $where[] = 'a.education_level=?'; $params[] = $f['level']; }
        if ($f['flag'] === 'duplicate') $where[] = 'a.possible_duplicate=1';
        if ($f['q'] !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $f['q']) . '%';
            $mobile = Scholarship::normaliseMobile($f['q']);
            $where[] = '(a.reference LIKE ? OR a.student_name LIKE ? OR a.guardian_name LIKE ?' . ($mobile ? ' OR a.mobile=?' : '') . ')';
            array_push($params, $like, $like, $like);
            if ($mobile) $params[] = $mobile;
        }
        // NIC searches arrive by POST and are kept in the session as a keyed hash, so the number never appears in a URL.
        if (($_GET['nic'] ?? '') === 'match') {
            $hash = (string)Session::get('scholarship_nic_filter', '');
            if ($hash !== '') { $where[] = 'a.guardian_nic_hash=?'; $params[] = $hash; $f['nic'] = 'match'; }
        }
        return [implode(' AND ', $where), $params, $f];
    }

    public function index(): void
    {
        $u = $this->guard();
        [$where, $params, $f] = $this->filters();
        $total = (int)(Database::one('SELECT COUNT(*) c FROM scholarship_applications a WHERE ' . $where, $params)['c'] ?? 0);
        $pagination = admin_pager($total, 20);
        $rows = Database::all('SELECT a.id,a.reference,a.student_name,a.student_grade,a.education_level,a.guardian_name,a.guardian_nic_last4,a.mobile,a.purpose,a.status,a.eligibility,a.rating,a.possible_duplicate,a.created_at,a.reviewed_at,r.name reviewer FROM scholarship_applications a LEFT JOIN users r ON r.id=a.reviewed_by WHERE ' . $where . ' ORDER BY CASE a.status WHEN "pending" THEN 0 WHEN "under_review" THEN 1 WHEN "shortlisted" THEN 2 ELSE 3 END, a.created_at DESC, a.id DESC LIMIT ' . (int)$pagination['per_page'] . ' OFFSET ' . (int)$pagination['offset'], $params);
        $stats = Database::one('SELECT COUNT(*) total,
            SUM(CASE WHEN status="pending" THEN 1 ELSE 0 END) pending,
            SUM(CASE WHEN status="under_review" THEN 1 ELSE 0 END) under_review,
            SUM(CASE WHEN status="shortlisted" THEN 1 ELSE 0 END) shortlisted,
            SUM(CASE WHEN status="approved" THEN 1 ELSE 0 END) approved,
            SUM(CASE WHEN status="rejected" THEN 1 ELSE 0 END) rejected,
            SUM(CASE WHEN eligibility="eligible" AND status NOT IN("approved","rejected") THEN 1 ELSE 0 END) qualified,
            SUM(CASE WHEN possible_duplicate=1 THEN 1 ELSE 0 END) duplicates
            FROM scholarship_applications') ?: [];
        $byPurpose = [];
        foreach (Database::all('SELECT purpose,COUNT(*) c FROM scholarship_applications GROUP BY purpose') as $r) $byPurpose[(string)$r['purpose']] = (int)$r['c'];
        $this->view('admin/scholarships', [
            'user' => $u, 'rows' => $rows, 'pagination' => $pagination, 'filters' => $f, 'stats' => $stats, 'byPurpose' => $byPurpose,
            'open' => Scholarship::applicationsOpen(), 'closedMessage' => Scholarship::closedMessage(),
            'adminTitle' => 'Scholarship Applications', 'adminBodyClass' => 'admin-scholarships',
        ], 'admin');
    }

    public function details(string $id): void
    {
        $u = $this->guard();
        $app = Database::one('SELECT a.*,r.name reviewer FROM scholarship_applications a LEFT JOIN users r ON r.id=a.reviewed_by WHERE a.id=?', [(int)$id]);
        if (!$app) { Session::flash('error', 'That application could not be found.'); redirect('admin/scholarships'); }
        $timeline = Database::all('SELECT s.*,u.name admin_name FROM scholarship_reviews s LEFT JOIN users u ON u.id=s.admin_id WHERE s.application_id=? ORDER BY s.created_at DESC,s.id DESC', [(int)$app['id']]);
        $related = Database::all('SELECT id,reference,student_name,student_grade,purpose,status,created_at FROM scholarship_applications WHERE guardian_nic_hash=? AND id<>? ORDER BY created_at DESC LIMIT 10', [$app['guardian_nic_hash'], (int)$app['id']]);

        // The full NIC is shown only for a short time after an audited "show" request.
        $revealedAt = (int)Session::get('scholarship_reveal.' . (int)$app['id'], 0);
        $nic = '';
        if ($revealedAt && time() - $revealedAt <= self::REVEAL_SECONDS) $nic = PersonalDataVault::decrypt((string)$app['guardian_nic_enc']);
        else Session::forget('scholarship_reveal.' . (int)$app['id']);

        $neighbours = [
            'prev' => Database::one('SELECT id,reference FROM scholarship_applications WHERE id>? ORDER BY id ASC LIMIT 1', [(int)$app['id']]),
            'next' => Database::one('SELECT id,reference FROM scholarship_applications WHERE id<? ORDER BY id DESC LIMIT 1', [(int)$app['id']]),
        ];
        unset($app['guardian_nic_enc'], $app['guardian_nic_hash'], $app['submit_ip_hash']);
        $this->view('admin/scholarship-view', [
            'user' => $u, 'app' => $app, 'timeline' => $timeline, 'related' => $related, 'nic' => $nic,
            'nicLength' => $nic !== '' ? strlen($nic) : 10, 'neighbours' => $neighbours,
            'adminTitle' => 'Application ' . $app['reference'], 'adminBodyClass' => 'admin-scholarships',
        ], 'admin');
    }

    public function evaluate(): void
    {
        $this->requirePost();
        $u = $this->guard();
        $id = (int)($_POST['id'] ?? 0);
        $app = Database::one('SELECT id,reference,status,eligibility,rating,selection_comment FROM scholarship_applications WHERE id=?', [$id]);
        if (!$app) { Session::flash('error', 'That application could not be found.'); redirect('admin/scholarships'); }
        $back = 'admin/scholarships/view/' . $id;

        $status = (string)($_POST['status'] ?? $app['status']);
        $eligibility = (string)($_POST['eligibility'] ?? $app['eligibility']);
        $ratingRaw = trim((string)($_POST['rating'] ?? ''));
        $rating = $ratingRaw === '' ? null : (int)$ratingRaw;
        $comment = mb_substr(trim(str_replace("\r", '', (string)($_POST['selection_comment'] ?? ''))), 0, 3000);
        $note = mb_substr(trim(str_replace("\r", '', (string)($_POST['note'] ?? ''))), 0, 3000);

        if (!isset(Scholarship::STATUSES[$status]) || !isset(Scholarship::ELIGIBILITY[$eligibility]) || ($rating !== null && ($rating < 1 || $rating > 5))) {
            Session::flash('error', 'Please choose a valid status, eligibility and rating.');
            redirect($back);
        }
        // Approval is a deliberate decision: the team must first record that the student meets the criteria and explain the decision.
        if ($status === 'approved' && $eligibility !== 'eligible') {
            Session::flash('error', 'Mark the student as “Meets the criteria” before approving the application.');
            redirect($back);
        }
        if (in_array($status, ['approved', 'rejected'], true) && $status !== $app['status'] && $comment === '') {
            Session::flash('error', 'Add a selection comment explaining the decision before you ' . ($status === 'approved' ? 'approve' : 'reject') . ' the application.');
            redirect($back);
        }

        $changes = [];
        $now = date('Y-m-d H:i:s');
        Database::transaction(function () use ($app, $status, $eligibility, $rating, $comment, $note, $u, $now, &$changes): void {
            $uid = (int)$u['id'];
            $aid = (int)$app['id'];
            if ($status !== $app['status']) {
                Database::insert('INSERT INTO scholarship_reviews(application_id,admin_id,kind,old_status,new_status,body,created_at) VALUES(?,?,?,?,?,?,?)', [$aid, $uid, 'status', $app['status'], $status, null, $now]);
                $changes[] = 'status ' . Scholarship::STATUSES[$app['status']] . ' → ' . Scholarship::STATUSES[$status];
            }
            $oldRating = $app['rating'] === null ? null : (int)$app['rating'];
            if ($eligibility !== $app['eligibility'] || $rating !== $oldRating) {
                $parts = [];
                if ($eligibility !== $app['eligibility']) $parts[] = 'Eligibility: ' . Scholarship::ELIGIBILITY[$eligibility];
                if ($rating !== $oldRating) $parts[] = 'Rating: ' . ($rating === null ? 'cleared' : $rating . ' / 5');
                Database::insert('INSERT INTO scholarship_reviews(application_id,admin_id,kind,body,created_at) VALUES(?,?,?,?,?)', [$aid, $uid, 'evaluation', implode(' · ', $parts), $now]);
                $changes[] = 'evaluation';
            }
            if ($comment !== trim((string)($app['selection_comment'] ?? ''))) {
                Database::insert('INSERT INTO scholarship_reviews(application_id,admin_id,kind,body,created_at) VALUES(?,?,?,?,?)', [$aid, $uid, 'comment', $comment !== '' ? $comment : 'Selection comment cleared', $now]);
                $changes[] = 'selection comment';
            }
            if ($note !== '') {
                Database::insert('INSERT INTO scholarship_reviews(application_id,admin_id,kind,body,created_at) VALUES(?,?,?,?,?)', [$aid, $uid, 'note', $note, $now]);
                $changes[] = 'note';
            }
            if ($changes) {
                // decided_at records when the final decision was made; it is cleared if the application is reopened.
                $decidedSql = 'NULL';
                $decidedParams = [];
                if (in_array($status, ['approved', 'rejected'], true)) {
                    if ($status !== $app['status']) { $decidedSql = '?'; $decidedParams = [$now]; }
                    else $decidedSql = 'decided_at';
                }
                Database::exec('UPDATE scholarship_applications SET status=?,eligibility=?,rating=?,selection_comment=?,reviewed_by=?,reviewed_at=?,decided_at=' . $decidedSql . ',updated_at=? WHERE id=?',
                    array_merge([$status, $eligibility, $rating, $comment !== '' ? $comment : null, $uid, $now], $decidedParams, [$now, $aid]));
            }
        });

        if (!$changes) { Session::flash('success', 'No changes to save.'); redirect($back); }
        Audit::write((int)$u['id'], 'SCHOLARSHIP_REVIEW', 'scholarships', (int)$app['id'], 'Application ' . $app['reference'] . ': ' . implode(', ', $changes));
        Session::flash('success', 'Application ' . $app['reference'] . ' updated.');
        redirect($back);
    }

    public function note(): void
    {
        $this->requirePost();
        $u = $this->guard();
        $id = (int)($_POST['id'] ?? 0);
        $app = Database::one('SELECT id,reference FROM scholarship_applications WHERE id=?', [$id]);
        $body = mb_substr(trim(str_replace("\r", '', (string)($_POST['body'] ?? ''))), 0, 3000);
        if (!$app) { Session::flash('error', 'That application could not be found.'); redirect('admin/scholarships'); }
        if (mb_strlen($body) < 2) { Session::flash('error', 'Write the note before saving it.'); redirect('admin/scholarships/view/' . $id . '#notes'); }
        Database::insert('INSERT INTO scholarship_reviews(application_id,admin_id,kind,body,created_at) VALUES(?,?,?,?,?)', [$id, (int)$u['id'], 'note', $body, date('Y-m-d H:i:s')]);
        Audit::write((int)$u['id'], 'SCHOLARSHIP_NOTE', 'scholarships', $id, 'Internal note added to ' . $app['reference']);
        Session::flash('success', 'Note added.');
        redirect('admin/scholarships/view/' . $id . '#notes');
    }

    public function reveal(): void
    {
        $this->requirePost();
        $u = $this->guard();
        $id = (int)($_POST['id'] ?? 0);
        $app = Database::one('SELECT id,reference FROM scholarship_applications WHERE id=?', [$id]);
        if (!$app) { Session::flash('error', 'That application could not be found.'); redirect('admin/scholarships'); }
        Session::put('scholarship_reveal.' . $id, time());
        Database::insert('INSERT INTO scholarship_reviews(application_id,admin_id,kind,body,created_at) VALUES(?,?,?,?,?)', [$id, (int)$u['id'], 'access', 'Full NIC number viewed', date('Y-m-d H:i:s')]);
        Audit::write((int)$u['id'], 'SCHOLARSHIP_NIC_VIEWED', 'scholarships', $id, 'Full NIC number viewed for ' . $app['reference']);
        redirect('admin/scholarships/view/' . $id . '#guardian');
    }

    public function findNic(): void
    {
        $this->requirePost();
        $this->guard();
        $nic = Scholarship::normaliseNic((string)($_POST['nic'] ?? ''));
        if ($nic === null) {
            Session::forget('scholarship_nic_filter');
            Session::flash('error', 'Enter a complete NIC number (9 digits with V or X, or 12 digits) to search.');
            redirect('admin/scholarships');
        }
        Session::put('scholarship_nic_filter', PersonalDataVault::fingerprint($nic));
        redirect('admin/scholarships?nic=match');
    }

    public function settings(): void
    {
        $this->requirePost();
        $u = $this->guard();
        $open = ($_POST['applications_open'] ?? '') === '1';
        Scholarship::saveSettings($open, (string)($_POST['closed_message'] ?? ''), (int)$u['id']);
        Audit::write((int)$u['id'], 'SETTINGS_UPDATE', 'scholarships', null, 'Scholarship applications ' . ($open ? 'opened' : 'closed'));
        Session::flash('success', $open ? 'Scholarship applications are open on the website.' : 'Scholarship applications are closed. Saved applications are not affected.');
        redirect('admin/scholarships');
    }

    public function export(): void
    {
        $u = $this->guard();
        [$where, $params] = $this->filters();
        $rows = Database::all('SELECT a.*,r.name reviewer FROM scholarship_applications a LEFT JOIN users r ON r.id=a.reviewed_by WHERE ' . $where . ' ORDER BY a.created_at DESC,a.id DESC LIMIT 5000', $params);
        Audit::write((int)$u['id'], 'SCHOLARSHIP_EXPORT', 'scholarships', null, 'Exported ' . count($rows) . ' scholarship applications (NIC numbers masked)');
        $cell = static function ($v): string {
            $v = (string)$v;
            // Stop spreadsheet apps from treating cell text as a formula.
            return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
        };
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="scholarship-applications-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Reference', 'Submitted', 'Student', 'Grade / class', 'Education level', 'Parent or guardian', 'NIC (masked)', 'Mobile', 'Address', 'Support requested', 'Status', 'Eligibility', 'Rating', 'Selection comment', 'Possible duplicate', 'Reviewed by', 'Reviewed at']);
        foreach ($rows as $r) {
            fputcsv($out, array_map($cell, [
                $r['reference'], $r['created_at'], $r['student_name'], Scholarship::gradeLabel((string)$r['student_grade']), Scholarship::LEVELS[$r['education_level']] ?? $r['education_level'],
                $r['guardian_name'], Scholarship::maskNic((string)$r['guardian_nic_last4']), Scholarship::formatMobile((string)$r['mobile']), str_replace("\n", ', ', (string)$r['address']),
                Scholarship::PURPOSES[$r['purpose']] ?? $r['purpose'], Scholarship::STATUSES[$r['status']] ?? $r['status'], Scholarship::ELIGIBILITY[$r['eligibility']] ?? $r['eligibility'],
                $r['rating'] ?? '', (string)($r['selection_comment'] ?? ''), (int)$r['possible_duplicate'] ? 'Yes' : 'No', (string)($r['reviewer'] ?? ''), (string)($r['reviewed_at'] ?? ''),
            ]));
        }
        fclose($out);
        exit;
    }

    public function delete(): void
    {
        $this->requirePost();
        $u = $this->guard();
        if (($u['role'] ?? '') !== 'super_admin') { http_response_code(403); exit('Forbidden'); }
        $id = (int)($_POST['id'] ?? 0);
        $app = Database::one('SELECT id,reference FROM scholarship_applications WHERE id=?', [$id]);
        if (!$app) { Session::flash('error', 'That application could not be found.'); redirect('admin/scholarships'); }
        Database::exec('DELETE FROM scholarship_reviews WHERE application_id=?', [$id]);
        Database::exec('DELETE FROM scholarship_applications WHERE id=?', [$id]);
        Session::forget('scholarship_reveal.' . $id);
        Audit::write((int)$u['id'], 'DELETE', 'scholarships', $id, 'Scholarship application ' . $app['reference'] . ' deleted');
        Session::flash('success', 'Application ' . $app['reference'] . ' deleted.');
        redirect('admin/scholarships');
    }
}
