<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Notification;
use App\Core\PersonalDataVault;
use App\Core\Scholarship;
use App\Core\Session;
use App\Core\Site;

/**
 * V7.3 — public Student Scholarship & Education Support application.
 * Applications are stored as "Pending" and are only ever decided by an administrator.
 */
final class ScholarshipController extends Controller
{
    private const MAX_PER_HOUR = 5;

    public function index(): void
    {
        $page = Site::page('scholarships') ?: [
            'title' => 'Scholarships & Education Support', 'eyebrow' => 'EDUCATION SUPPORT', 'hero_title' => 'Scholarships & Education Support',
            'hero_subtitle' => 'Apply for support with O/L and A/L studies, university education or general school needs.',
            'body' => '', 'image_path' => 'demo/cause-education.jpg', 'answer_summary' => '',
        ];
        $faq = self::faq();
        $this->view('scholarships', [
            'settings' => Site::settings(),
            'page' => $page,
            'faq' => $faq,
            'open' => Scholarship::applicationsOpen(),
            'closedMessage' => Scholarship::closedMessage(),
            'errors' => (array)(flash('scholarship_errors') ?? []),
            'old' => (array)(flash('scholarship_old') ?? []),
            'formError' => (string)(flash('scholarship_error') ?? ''),
            'seo' => [
                'title' => $page['meta_title'] ?? 'Scholarships & Education Support | 97 Foundation',
                'description' => $page['meta_description'] ?? 'Apply online for 97 Foundation scholarships and education support in Polonnaruwa, Sri Lanka.',
                'breadcrumbs' => [['Home', ''], ['Scholarships & Education Support', 'scholarships']],
            ],
        ]);
    }

    public function apply(): void
    {
        $this->requirePost();
        // Bots fill every field; people never see this one.
        if (trim((string)($_POST['website'] ?? '')) !== '') {
            Session::flash('scholarship_done', ['reference' => 'SCH-' . date('y') . '-RECEIVED', 'purpose' => '', 'at' => date('c')]);
            redirect('scholarships/submitted');
        }
        if (!Scholarship::applicationsOpen()) {
            Session::flash('scholarship_error', Scholarship::closedMessage());
            redirect('scholarships');
        }

        [$data, $errors] = Scholarship::validate($_POST);
        if ($errors) {
            $this->back($errors, 'Please correct the highlighted fields and submit again.');
        }
        if (!PersonalDataVault::ready()) {
            error_log('Scholarship application rejected: secure storage key unavailable.');
            $this->back([], 'Applications cannot be accepted right now. Please try again later or contact us.');
        }

        $ipHash = PersonalDataVault::fingerprint('ip|' . \App\Core\Auth::ip());
        $since = date('Y-m-d H:i:s', time() - 3600);
        $recent = (int)(Database::one('SELECT COUNT(*) c FROM scholarship_applications WHERE submit_ip_hash=? AND created_at>=?', [$ipHash, $since])['c'] ?? 0);
        if ($recent >= self::MAX_PER_HOUR) {
            $this->back([], 'We have received several applications from this connection in the last hour. Please wait a while before submitting another one.');
        }

        $nicHash = PersonalDataVault::fingerprint($data['guardian_nic']);
        $duplicate = 0;
        foreach (Database::all('SELECT student_name FROM scholarship_applications WHERE guardian_nic_hash=?', [$nicHash]) as $row) {
            if (mb_strtolower(trim((string)$row['student_name'])) === mb_strtolower($data['student_name'])) { $duplicate = 1; break; }
        }

        $now = date('Y-m-d H:i:s');
        $id = 0;
        $reference = '';
        for ($attempt = 0; $attempt < 5 && !$id; $attempt++) {
            $reference = Scholarship::newReference();
            if (Database::one('SELECT id FROM scholarship_applications WHERE reference=?', [$reference])) continue;
            $id = (int)Database::insert(
                'INSERT INTO scholarship_applications(reference,student_name,student_grade,education_level,guardian_name,guardian_nic_enc,guardian_nic_hash,guardian_nic_last4,mobile,address,purpose,status,eligibility,possible_duplicate,consent_at,submit_ip_hash,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$reference, $data['student_name'], $data['student_grade'], $data['education_level'], $data['guardian_name'],
                 PersonalDataVault::encrypt($data['guardian_nic']), $nicHash, substr($data['guardian_nic'], -4), $data['mobile'], $data['address'],
                 $data['purpose'], 'pending', 'not_assessed', $duplicate, $now, $ipHash, $now, $now]
            );
        }
        if (!$id) {
            $this->back([], 'Your application could not be saved. Please try again.');
        }

        // Logs and notifications carry the reference only — never names or NIC numbers.
        Audit::write(null, 'SCHOLARSHIP_SUBMITTED', 'scholarships', $id, 'Application ' . $reference . ' submitted (' . Scholarship::PURPOSES[$data['purpose']] . ')');
        Notification::admins('New scholarship application', 'Application ' . $reference . ' is waiting for review (' . Scholarship::PURPOSES[$data['purpose']] . ').', 'scholarship', 'admin/scholarships/view/' . $id);

        Session::flash('scholarship_done', ['reference' => $reference, 'purpose' => $data['purpose'], 'at' => $now]);
        redirect('scholarships/submitted');
    }

    public function submitted(): void
    {
        $done = flash('scholarship_done');
        if (!is_array($done) || empty($done['reference'])) redirect('scholarships');
        header('Cache-Control: no-store, private');
        $this->view('scholarship-submitted', [
            'settings' => Site::settings(),
            'done' => $done,
            'seo' => ['title' => 'Application submitted | 97 Foundation', 'description' => 'Your scholarship application was received.', 'noindex' => true],
        ]);
    }

    private function back(array $errors, string $message): never
    {
        $old = [];
        foreach (['student_name', 'student_grade', 'guardian_name', 'guardian_nic', 'mobile', 'address', 'purpose'] as $k) {
            $old[$k] = mb_substr(trim((string)($_POST[$k] ?? '')), 0, 600);
        }
        $old['consent'] = !empty($_POST['consent']) ? '1' : '';
        Session::flash('scholarship_old', $old);
        Session::flash('scholarship_errors', $errors);
        Session::flash('scholarship_error', $message);
        redirect('scholarships');
    }

    /** Questions shown on the page and published as FAQ structured data. */
    public static function faq(): array
    {
        return [
            ['Who can apply?', 'Students from families who need financial help to continue their education — from school pupils to university students. A parent or guardian completes the application.'],
            ['Is there a fee to apply?', 'No. Applying is free. 97 Foundation never asks for money to process an application.'],
            ['Is my application approved automatically?', 'No. Every application is read by the selection team. The team discusses eligibility, may call you to verify details, and then decides which students to support.'],
            ['Why do you need the parent or guardian’s NIC number?', 'It confirms the applicant’s identity and helps us avoid duplicate applications. The number is stored encrypted and only authorised Foundation administrators can view it.'],
            ['How will I hear back?', 'Keep your reference number. If your application is shortlisted, the team will contact you on the mobile number you provided.'],
            ['Can I apply for more than one child?', 'Yes. Submit a separate application for each student.'],
        ];
    }
}
