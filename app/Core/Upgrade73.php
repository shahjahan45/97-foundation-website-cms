<?php
namespace App\Core;

/**
 * 97 Foundation V7.3 database update (idempotent, never deletes records).
 * Runs automatically on the first web request after installation, or with `php database/upgrade_v7_3.php`.
 *  - Student Scholarship & Education Support applications + review history
 *  - Scholarship, Privacy Policy and Terms & Conditions pages (editable in Website Content → Pages)
 */
final class Upgrade73
{
    private const FLAG = '/storage/logs/.v73-schema';

    public static function autoApply(): void
    {
        $flag = dirname(__DIR__, 2) . self::FLAG;
        if (is_file($flag)) return;
        if (Upgrade72::hasTable('scholarship_applications') && Upgrade72::hasTable('scholarship_reviews') && setting('system', 'v73_applied', '0') === '1') {
            @file_put_contents($flag, date('c'));
            return;
        }
        self::run(null);
        @file_put_contents($flag, date('c'));
    }

    /** @return string[] */
    public static function run(?int $userId): array
    {
        $log = [];
        if (!Upgrade72::hasTable('scholarship_applications')) {
            Database::exec("CREATE TABLE IF NOT EXISTS scholarship_applications (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                reference VARCHAR(24) NOT NULL UNIQUE,
                student_name VARCHAR(150) NOT NULL,
                student_grade VARCHAR(30) NOT NULL,
                education_level VARCHAR(20) NOT NULL,
                guardian_name VARCHAR(150) NOT NULL,
                guardian_nic_enc TEXT NOT NULL,
                guardian_nic_hash CHAR(64) NOT NULL,
                guardian_nic_last4 VARCHAR(4) NOT NULL,
                mobile VARCHAR(20) NOT NULL,
                address VARCHAR(600) NOT NULL,
                purpose ENUM('ol','al','university','general') NOT NULL,
                status ENUM('pending','under_review','shortlisted','approved','rejected') NOT NULL DEFAULT 'pending',
                eligibility ENUM('not_assessed','eligible','not_eligible') NOT NULL DEFAULT 'not_assessed',
                rating TINYINT UNSIGNED NULL,
                selection_comment TEXT NULL,
                possible_duplicate TINYINT(1) NOT NULL DEFAULT 0,
                consent_at DATETIME NOT NULL,
                submit_ip_hash CHAR(64) NULL,
                reviewed_by BIGINT UNSIGNED NULL,
                reviewed_at DATETIME NULL,
                decided_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_sch_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_sch_status (status, created_at),
                INDEX idx_sch_purpose (purpose, education_level),
                INDEX idx_sch_nic (guardian_nic_hash),
                INDEX idx_sch_ip (submit_ip_hash, created_at)
            ) ENGINE=InnoDB");
            $log[] = 'Scholarship applications table created';
        }
        if (!Upgrade72::hasTable('scholarship_reviews')) {
            Database::exec("CREATE TABLE IF NOT EXISTS scholarship_reviews (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                application_id BIGINT UNSIGNED NOT NULL,
                admin_id BIGINT UNSIGNED NULL,
                kind ENUM('note','status','evaluation','comment','access') NOT NULL DEFAULT 'note',
                old_status VARCHAR(30) NULL,
                new_status VARCHAR(30) NULL,
                body TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_schr_app FOREIGN KEY(application_id) REFERENCES scholarship_applications(id) ON DELETE CASCADE,
                CONSTRAINT fk_schr_admin FOREIGN KEY(admin_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_schr_app (application_id, created_at)
            ) ENGINE=InnoDB");
            $log[] = 'Scholarship review history table created';
        }
        foreach (self::pages() as $p) {
            if (!Database::one('SELECT id FROM pages WHERE slug=? LIMIT 1', [$p[0]])) {
                Database::exec('INSERT INTO pages(slug,title,eyebrow,hero_title,hero_subtitle,body,image_path,meta_title,meta_description,answer_summary,is_active,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?,1,?)', $p);
                $log[] = '"' . $p[1] . '" page added';
            }
        }
        if (Database::one('SELECT id FROM settings WHERE setting_group="system" AND setting_key="v73_applied" LIMIT 1')) {
            Database::exec('UPDATE settings SET setting_value="1" WHERE setting_group="system" AND setting_key="v73_applied"');
        } else {
            Database::exec('INSERT INTO settings(setting_group,setting_key,setting_value,is_public,updated_by) VALUES("system","v73_applied","1",0,?)', [$userId]);
        }
        if ($userId) Audit::write($userId, 'WEBSITE_UPDATE', 'system', null, 'V7.3 scholarship update applied');
        return $log;
    }

    /** [slug,title,eyebrow,hero_title,hero_subtitle,body,image_path,meta_title,meta_description,answer_summary,sort_order] */
    public static function pages(): array
    {
        return [
            ['scholarships', 'Scholarships & Education Support', 'EDUCATION SUPPORT', 'Scholarships & Education Support',
                'Apply for support with O/L and A/L studies, university education or general school needs. Every application is reviewed by the 97 Foundation team.',
                "97 Foundation supports students from families in Thambala, Polonnaruwa and nearby communities so that financial difficulty does not end a child's education.\n\nApplications are reviewed by the Foundation's selection team. Submitting an application does not guarantee support; the team discusses each application and contacts shortlisted families.",
                'demo/cause-education.jpg', 'Scholarships & Education Support | 97 Foundation',
                'Apply online for 97 Foundation scholarships and education support for O/L, A/L, university and general school needs in Polonnaruwa, Sri Lanka.',
                '97 Foundation offers scholarships and education support for O/L, A/L, university and general school needs. Parents or guardians apply online; the selection team reviews every application and contacts shortlisted families.', 25],
            ['privacy-policy', 'Privacy Policy', 'PRIVACY', 'Privacy Policy', 'How 97 Foundation collects, uses and protects your information.',
                "This policy explains how 97 Foundation (\"we\") handles personal information collected through this website. Please read it together with our Terms & Conditions.\n\n## Information we collect\n\n- Details you give us in forms: names, contact numbers, email and postal addresses, messages and donation details.\n- Scholarship applications: student and parent or guardian names, the parent or guardian's NIC number, contact number, address and the support requested.\n- Payment receipts you upload for bank-transfer donations.\n- Basic technical information needed to keep the website secure, such as a session cookie.\n\n## How we use it\n\n- To process and verify donations and to issue receipts.\n- To review scholarship and education support applications and contact applicants.\n- To manage volunteers and Foundation members.\n- To answer messages and keep records required for transparency and accountability.\n\n## How we protect it\n\nAccess to personal information is limited to authorised Foundation administrators. Payment receipts are stored privately, NIC numbers are encrypted, and sensitive actions are recorded in an audit log. Card payments are handled by our payment provider; we never see or store card numbers.\n\n## Sharing\n\nWe do not sell personal information. We share it only when needed to provide a service you asked for (for example our payment provider for online donations) or when the law requires it.\n\n## How long we keep it\n\nWe keep information only as long as needed for the purpose it was collected and for our records. You can ask us to correct or delete your information.\n\n## Your choices\n\nTo see, correct or delete information we hold about you, contact us using the details on the Contact page.\n\n## Changes\n\nWe may update this policy. The latest version is always on this page.",
                null, 'Privacy Policy | 97 Foundation', 'How 97 Foundation collects, uses, protects and shares personal information, including donation and scholarship application data.', '', 90],
            ['terms', 'Terms & Conditions', 'TERMS', 'Terms & Conditions', 'The terms for using the 97 Foundation website and its services.',
                "By using this website you agree to these terms. If you do not agree, please do not use the website.\n\n## Using the website\n\nUse the website lawfully and do not attempt to access areas or information that are not meant for you.\n\n## Donations\n\n- Bank-transfer donations are recorded after you submit the form with a payment receipt and are counted only after our team verifies the payment.\n- Online card payments are processed securely by our payment provider.\n- If you believe a donation was made in error, contact us as soon as possible.\n\n## Scholarships and education support\n\n- Submitting an application does not guarantee support. The selection team reviews every application and decides at its discretion.\n- Applicants must give accurate information. Applications with false information may be rejected.\n- We contact shortlisted applicants using the details provided.\n\n## Content\n\nText, images and logos on this website belong to 97 Foundation or are used with permission. Please ask before reusing them.\n\n## Liability\n\nWe work to keep the website accurate and available but cannot guarantee it will always be error-free or uninterrupted.\n\n## Changes\n\nWe may update these terms. The latest version is always on this page. These terms are governed by the laws of Sri Lanka.",
                null, 'Terms & Conditions | 97 Foundation', 'The terms for using the 97 Foundation website, donations and scholarship applications.', '', 91],
        ];
    }
}
