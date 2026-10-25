<?php
declare(strict_types=1);
/**
 * 97 Foundation V2 upgrade helper.
 *
 * Use this for an existing database created by the earlier
 * 97-foundation-fixed package. It preserves existing records and settings.
 * Run from the project root:
 *   php database/upgrade_v2.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }
$config = require dirname(__DIR__) . '/config.php';
$db = $config['db'];
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset'] ?? 'utf8mb4');
try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
} catch (Throwable $e) { fwrite(STDERR, "Database connection failed: {$e->getMessage()}\n"); exit(1); }
$dbName=$db['name'];
function hasColumn(PDO $pdo,string $db,string $table,string $column):bool{
    $st=$pdo->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1');$st->execute([$db,$table,$column]);return (bool)$st->fetchColumn();
}
function addColumn(PDO $pdo,string $db,string $table,string $column,string $definition):void{
    if(hasColumn($pdo,$db,$table,$column)){echo "✓ {$table}.{$column} already exists\n";return;}
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");echo "+ Added {$table}.{$column}\n";
}
function run(PDO $pdo,string $sql,string $label):void{$pdo->exec($sql);echo "✓ {$label}\n";}

try{
    addColumn($pdo,$dbName,'users','profile_photo','VARCHAR(255) NULL AFTER `status`');
    addColumn($pdo,$dbName,'campaigns','is_visible','TINYINT(1) NOT NULL DEFAULT 1 AFTER `status`');
    addColumn($pdo,$dbName,'content_sections','image_path','VARCHAR(255) NULL AFTER `cta_url`');
    addColumn($pdo,$dbName,'content_sections','sort_order','INT NOT NULL DEFAULT 0 AFTER `is_active`');
    addColumn($pdo,$dbName,'volunteers','address','VARCHAR(500) NULL AFTER `date_of_birth`');
    addColumn($pdo,$dbName,'volunteers','emergency_contact','VARCHAR(220) NULL AFTER `interests`');
    addColumn($pdo,$dbName,'hero_slides','secondary_text','VARCHAR(80) NULL AFTER `button_url`');
    addColumn($pdo,$dbName,'hero_slides','secondary_url','VARCHAR(255) NULL AFTER `secondary_text`');

    run($pdo,"CREATE TABLE IF NOT EXISTS pages (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(120) NOT NULL UNIQUE, title VARCHAR(200) NOT NULL,
      eyebrow VARCHAR(100) NULL, hero_title VARCHAR(240) NULL, hero_subtitle VARCHAR(600) NULL, body MEDIUMTEXT NULL,
      image_path VARCHAR(255) NULL, meta_title VARCHAR(240) NULL, meta_description VARCHAR(500) NULL, answer_summary VARCHAR(900) NULL,
      is_active TINYINT(1) NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0, updated_by BIGINT UNSIGNED NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      CONSTRAINT fk_pages_user FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL, INDEX idx_pages_active_sort (is_active,sort_order)
    ) ENGINE=InnoDB",'Pages CMS table');
    run($pdo,"CREATE TABLE IF NOT EXISTS gallery_items (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,title VARCHAR(180) NOT NULL,caption VARCHAR(700) NULL,image_path VARCHAR(255) NOT NULL,
      category VARCHAR(100) NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,sort_order INT NOT NULL DEFAULT 0,created_by BIGINT UNSIGNED NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      CONSTRAINT fk_gallery_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,INDEX idx_gallery_active_sort(is_active,sort_order)
    ) ENGINE=InnoDB",'Gallery table');
    run($pdo,"CREATE TABLE IF NOT EXISTS campaign_categories (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL UNIQUE,slug VARCHAR(130) NOT NULL UNIQUE,description VARCHAR(600) NULL,
      icon VARCHAR(40) NULL,image_path VARCHAR(255) NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,sort_order INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB",'Cause areas table');
    run($pdo,"CREATE TABLE IF NOT EXISTS member_profiles (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL UNIQUE,mobile VARCHAR(60) NULL,address VARCHAR(500) NULL,
      member_number VARCHAR(60) NULL UNIQUE,joined_at DATE NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,CONSTRAINT fk_member_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",'Member profiles table');
    run($pdo,"CREATE TABLE IF NOT EXISTS messages (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,sender_id BIGINT UNSIGNED NOT NULL,recipient_id BIGINT UNSIGNED NOT NULL,subject VARCHAR(180) NULL,
      body TEXT NOT NULL,read_at DATETIME NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_msg_sender FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE CASCADE,CONSTRAINT fk_msg_recipient FOREIGN KEY(recipient_id) REFERENCES users(id) ON DELETE CASCADE,
      INDEX idx_msg_recipient_read(recipient_id,read_at,created_at),INDEX idx_msg_pair(sender_id,recipient_id,created_at)
    ) ENGINE=InnoDB",'Portal messages table');
    run($pdo,"CREATE TABLE IF NOT EXISTS notifications (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,title VARCHAR(180) NOT NULL,message VARCHAR(1000) NOT NULL,
      type VARCHAR(30) NOT NULL DEFAULT 'info',link_url VARCHAR(255) NULL,read_at DATETIME NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_notif_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,INDEX idx_notif_user_read(user_id,read_at,created_at)
    ) ENGINE=InnoDB",'Notifications table');
    run($pdo,"CREATE TABLE IF NOT EXISTS events (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,title VARCHAR(180) NOT NULL,slug VARCHAR(190) NOT NULL UNIQUE,description MEDIUMTEXT NULL,image_path VARCHAR(255) NULL,
      event_date DATE NOT NULL,start_time TIME NULL,end_time TIME NULL,location VARCHAR(180) NULL,status ENUM('draft','published','cancelled','completed') NOT NULL DEFAULT 'draft',
      is_active TINYINT(1) NOT NULL DEFAULT 1,created_by BIGINT UNSIGNED NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,CONSTRAINT fk_event_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
      INDEX idx_event_date_status(event_date,status,is_active)
    ) ENGINE=InnoDB",'Events table');

    $settings=[
      ['foundation','logo_path','',1],['foundation','logo_enabled','1',1],['foundation','facebook','',1],['foundation','instagram','',1],['foundation','linkedin','',1],['foundation','youtube','',1],
      ['seo','default_title','97 Foundation — Hope. Help. Humanity.',1],['seo','default_description','97 Foundation supports people in need, strengthens communities and creates meaningful change through transparent community action.',1],['seo','social_image','',1]
    ];
    $st=$pdo->prepare('INSERT IGNORE INTO settings(setting_group,setting_key,setting_value,is_public) VALUES(?,?,?,?)');foreach($settings as $row)$st->execute($row);echo "✓ Added missing branding/SEO settings without overwriting existing values\n";

    $pages=[
      ['about','About 97 Foundation','97 FOUNDATION','About 97 Foundation','Hope. Help. Humanity.','97 Foundation is built around transparent support, accountable fundraising and meaningful community action.','demo/hero-children.jpg','About 97 Foundation','Learn about 97 Foundation, its mission, values and community work.','97 Foundation is a community-focused charity platform connecting people who need help with donors and volunteers.',1,1],
      ['causes','Our Causes','OUR CAUSES','Areas Where We Serve','Explore the Foundation areas of support. Fundraising campaigns are managed separately.','Cause areas describe the types of support provided by 97 Foundation.','demo/cause-food.jpg','Our Causes | 97 Foundation','Explore the cause areas supported by 97 Foundation.','Cause areas organise the types of community support; fundraising campaigns are managed separately.',1,2],
      ['campaigns','Fundraising Campaigns','FUNDRAISING','Support a Better Tomorrow','Browse active and upcoming fundraising campaigns.','Public totals include approved donations only.','demo/campaign-families.jpg','Fundraising Campaigns | 97 Foundation','View active and upcoming fundraising campaigns.','Campaign totals count only administrator-approved donations.',1,3],
      ['events','Events','COMMUNITY EVENTS','Upcoming Events & Activities','Join community programs, outreach activities and volunteer initiatives.','Events can be managed from the administration area.','demo/cause-emergency.jpg','Events | 97 Foundation','Discover upcoming 97 Foundation events and community activities.','97 Foundation events bring volunteers and communities together.',1,4],
      ['gallery','Gallery','OUR GALLERY','Stories in Pictures','Moments of service, compassion and community impact.','Every gallery image can be uploaded and managed by an administrator.','demo/campaign-education.jpg','Gallery | 97 Foundation','View photos from 97 Foundation activities and community work.','The gallery documents community service through administrator-managed photos.',1,5],
      ['volunteer','Become a Volunteer','GET INVOLVED','Bring Your Time, Skills and Compassion','Create an account and receive portal access after approval.','Approved volunteers receive a unique ID and QR verification.','demo/cause-education.jpg','Become a Volunteer | 97 Foundation','Apply to become a 97 Foundation volunteer.','Approved volunteers can sign in, message administrators and access an ID with QR verification.',1,6],
      ['donate','Donate','DONATE WITH CONFIDENCE','Your Support Can Create Meaningful Change','View administrator-managed account details and submit your donation for verification.','Donation account and contact details are maintained from admin settings.','demo/campaign-health.jpg','Donate | 97 Foundation','Donate securely to 97 Foundation.','Only administrator-approved donations count toward public campaign totals.',1,7],
      ['contact','Contact Us','WE ARE HERE TO HELP','Contact 97 Foundation','Talk to our team about donations, volunteering, partnerships or support.','Use the secure contact form to reach 97 Foundation.','demo/cause-healthcare.jpg','Contact 97 Foundation','Contact 97 Foundation for donation support, volunteering and assistance.','Contact 97 Foundation by phone, email or secure inquiry form.',1,8],
    ];
    $st=$pdo->prepare('INSERT IGNORE INTO pages(slug,title,eyebrow,hero_title,hero_subtitle,body,image_path,meta_title,meta_description,answer_summary,is_active,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');foreach($pages as $row)$st->execute($row);echo "✓ Added missing CMS pages without overwriting edited content\n";

    $causes=[['Education','education','Learning opportunities and educational support.','▤','demo/cause-education.jpg',1,1],['Healthcare','healthcare','Medical and wellbeing support for communities.','✚','demo/cause-healthcare.jpg',1,2],['Food & Essentials','food-essentials','Food, clean water and essential supplies.','◉','demo/cause-food.jpg',1,3],['Emergency Support','emergency-support','Rapid assistance for urgent community needs.','⚑','demo/cause-emergency.jpg',1,4]];
    $st=$pdo->prepare('INSERT IGNORE INTO campaign_categories(name,slug,description,icon,image_path,is_active,sort_order) VALUES(?,?,?,?,?,?,?)');foreach($causes as $row)$st->execute($row);echo "✓ Added missing cause-area examples\n";

    $gallery=[['Education & Learning','Community learning and educational support.','demo/cause-education.jpg','Education',1,1],['Healthcare Support','Community health and wellbeing support.','demo/cause-healthcare.jpg','Healthcare',1,2],['Food & Essentials','Supporting essential community needs.','demo/cause-food.jpg','Community',1,3],['Emergency Response','Volunteer-led community support activities.','demo/cause-emergency.jpg','Volunteering',1,4]];
    $st=$pdo->prepare('SELECT COUNT(*) FROM gallery_items WHERE title=? AND image_path=?');$ins=$pdo->prepare('INSERT INTO gallery_items(title,caption,image_path,category,is_active,sort_order) VALUES(?,?,?,?,?,?)');foreach($gallery as $row){$st->execute([$row[0],$row[2]]);if(!(int)$st->fetchColumn())$ins->execute($row);}echo "✓ Added demo gallery entries where missing\n";

    echo "\nUpgrade completed successfully. Existing donations, accounts, campaigns and administrator settings were preserved.\n";
    echo "Next: clear browser cache with Ctrl+F5 and open /admin/branding, /admin/content and /admin/gallery.\n";
}catch(Throwable $e){fwrite(STDERR,"Upgrade failed: {$e->getMessage()}\n");exit(1);}
