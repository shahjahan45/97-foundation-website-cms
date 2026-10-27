<?php
namespace App\Core;

/**
 * V7.3 — Student Scholarship & Education Support: shared options and validation rules.
 * The same rules are mirrored in the browser (public/assets/js/app.js → initScholarshipForm).
 */
final class Scholarship
{
    public const PURPOSES = [
        'ol' => 'G.C.E. Ordinary Level (O/L)',
        'al' => 'G.C.E. Advanced Level (A/L)',
        'university' => 'University Education',
        'general' => 'General Education Support',
    ];

    /** Compact labels for tables and exports where space is tight. */
    public const PURPOSE_SHORT = ['ol' => 'O/L', 'al' => 'A/L', 'university' => 'University', 'general' => 'General support'];
    public const ELIGIBILITY_SHORT = ['not_assessed' => 'Not assessed', 'eligible' => 'Meets criteria', 'not_eligible' => 'Does not meet'];

    public const STATUSES = [
        'pending' => 'Pending',
        'under_review' => 'Under Review',
        'shortlisted' => 'Shortlisted',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public const ELIGIBILITY = [
        'not_assessed' => 'Not assessed yet',
        'eligible' => 'Meets the criteria',
        'not_eligible' => 'Does not meet the criteria',
    ];

    public const LEVELS = [
        'primary' => 'Primary (Grades 1–5)',
        'junior' => 'Junior secondary (Grades 6–9)',
        'ol' => 'O/L classes (Grades 10–11)',
        'al' => 'A/L classes (Grades 12–13)',
        'university' => 'University / higher education',
        'other' => 'Other',
    ];

    public const DEFAULT_CLOSED_MESSAGE = 'Applications are closed at the moment. Please check this page again or contact us for help.';

    /** Short descriptions for each type of support (public page and admin). */
    public const PURPOSE_NOTES = [
        'ol' => 'Help for students preparing for the G.C.E. Ordinary Level examination.',
        'al' => 'Help for students in Advanced Level classes and preparing for the A/L examination.',
        'university' => 'Help for students entering or continuing university and higher education.',
        'general' => 'School books, uniforms, stationery and other everyday learning needs.',
    ];

    /** Applications can be paused by an administrator without affecting saved applications. */
    public static function applicationsOpen(): bool { return setting('scholarship', 'applications_open', '1') === '1'; }

    public static function closedMessage(): string
    {
        $m = trim(setting('scholarship', 'closed_message', ''));
        return $m !== '' ? $m : self::DEFAULT_CLOSED_MESSAGE;
    }

    public static function saveSettings(bool $open, string $message, ?int $userId): void
    {
        foreach ([['applications_open', $open ? '1' : '0'], ['closed_message', mb_substr(trim($message), 0, 400)]] as [$key, $value]) {
            Database::exec('INSERT INTO settings(setting_group,setting_key,setting_value,is_public,updated_by) VALUES("scholarship",?,?,1,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),is_public=VALUES(is_public),updated_by=VALUES(updated_by)', [$key, $value, $userId]);
        }
    }

    /** Grade options shown in the form. */
    public static function grades(): array
    {
        $g = [];
        for ($i = 1; $i <= 13; $i++) $g['grade_' . $i] = 'Grade ' . $i;
        $g['university'] = 'University / higher education';
        $g['other'] = 'Other / not in school';
        return $g;
    }

    public static function levelForGrade(string $grade): string
    {
        if ($grade === 'university') return 'university';
        if (!preg_match('/^grade_(\d{1,2})$/', $grade, $m)) return 'other';
        $n = (int)$m[1];
        return $n <= 5 ? 'primary' : ($n <= 9 ? 'junior' : ($n <= 11 ? 'ol' : 'al'));
    }

    public static function gradeLabel(string $grade): string { return self::grades()[$grade] ?? $grade; }

    /** Sri Lankan NIC: old format 9 digits + V/X, or new 12-digit format. Returns the normalised value or null. */
    public static function normaliseNic(string $value): ?string
    {
        $v = strtoupper(preg_replace('/[\s-]+/', '', $value) ?? '');
        if (preg_match('/^(\d{2})(\d{3})(\d{4})[VX]$/', $v, $m)) {
            $day = (int)$m[2];
        } elseif (preg_match('/^(\d{4})(\d{3})(\d{5})$/', $v, $m)) {
            $year = (int)$m[1];
            if ($year < 1900 || $year > (int)date('Y')) return null;
            $day = (int)$m[2];
        } else {
            return null;
        }
        if ($day > 500) $day -= 500;
        return ($day >= 1 && $day <= 366) ? $v : null;
    }

    /** Sri Lankan mobile number → 07XXXXXXXX, or null. */
    public static function normaliseMobile(string $value): ?string
    {
        $v = preg_replace('/[\s().-]+/', '', $value) ?? '';
        if (preg_match('/^(?:\+94|0094|94)(7\d{8})$/', $v, $m)) $v = '0' . $m[1];
        return preg_match('/^07[0-9]\d{7}$/', $v) ? $v : null;
    }

    public static function formatMobile(string $v): string
    {
        return preg_match('/^(07\d)(\d{3})(\d{4})$/', $v, $m) ? $m[1] . ' ' . $m[2] . ' ' . $m[3] : $v;
    }

    /** Names in any script: letters, marks, spaces, dots, apostrophes and hyphens. */
    public static function validName(string $value): bool
    {
        $len = mb_strlen($value);
        return $len >= 3 && $len <= 120 && (bool)preg_match("/^[\\p{L}\\p{M}][\\p{L}\\p{M} .'’-]*$/u", $value);
    }

    public static function maskNic(string $last4, int $length = 10): string
    {
        return str_repeat('•', max(4, $length - 4)) . $last4;
    }

    public static function newReference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $s = '';
        for ($i = 0; $i < 6; $i++) $s .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        return 'SCH-' . date('y') . '-' . $s;
    }

    /**
     * Validate a submitted application. Returns [cleanData, errors] where errors is field => message.
     */
    public static function validate(array $in): array
    {
        $e = [];
        $student = trim(preg_replace('/\s+/u', ' ', (string)($in['student_name'] ?? '')) ?? '');
        $guardian = trim(preg_replace('/\s+/u', ' ', (string)($in['guardian_name'] ?? '')) ?? '');
        $grade = (string)($in['student_grade'] ?? '');
        $purpose = (string)($in['purpose'] ?? '');
        $address = trim(preg_replace('/[ \t]+/', ' ', str_replace("\r", '', (string)($in['address'] ?? ''))) ?? '');
        $nic = self::normaliseNic((string)($in['guardian_nic'] ?? ''));
        $mobile = self::normaliseMobile((string)($in['mobile'] ?? ''));

        if ($student === '') $e['student_name'] = "Enter the student's full name.";
        elseif (!self::validName($student)) $e['student_name'] = 'Use letters only (3–120 characters). Dots, apostrophes and hyphens are fine.';
        if (!isset(self::grades()[$grade])) $e['student_grade'] = "Choose the student's current grade.";
        if ($guardian === '') $e['guardian_name'] = "Enter the parent or guardian's full name.";
        elseif (!self::validName($guardian)) $e['guardian_name'] = 'Use letters only (3–120 characters). Dots, apostrophes and hyphens are fine.';
        if (trim((string)($in['guardian_nic'] ?? '')) === '') $e['guardian_nic'] = "Enter the parent or guardian's NIC number.";
        elseif ($nic === null) $e['guardian_nic'] = 'Enter a valid NIC: 9 digits followed by V or X (e.g. 853202937V), or 12 digits (e.g. 198532002937).';
        if (trim((string)($in['mobile'] ?? '')) === '') $e['mobile'] = 'Enter a contact mobile number.';
        elseif ($mobile === null) $e['mobile'] = 'Enter a Sri Lankan mobile number, e.g. 071 234 5678 or +94 71 234 5678.';
        if ($address === '') $e['address'] = 'Enter the residential address.';
        elseif (mb_strlen($address) < 10 || mb_strlen($address) > 500) $e['address'] = 'The address should be between 10 and 500 characters.';
        if (!isset(self::PURPOSES[$purpose])) $e['purpose'] = 'Choose the type of scholarship or support.';
        if (empty($in['consent'])) $e['consent'] = 'Please confirm the declaration to submit the application.';

        return [[
            'student_name' => $student, 'student_grade' => $grade, 'education_level' => self::levelForGrade($grade),
            'guardian_name' => $guardian, 'guardian_nic' => (string)$nic, 'mobile' => (string)$mobile,
            'address' => mb_substr($address, 0, 500), 'purpose' => $purpose,
        ], $e];
    }
}
