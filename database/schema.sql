CREATE DATABASE IF NOT EXISTS foundation97 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE foundation97;

SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  username VARCHAR(80) NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin','administrator','finance_manager','volunteer_manager','content_manager','support_agent','event_manager','volunteer','member') NOT NULL DEFAULT 'member',
  status ENUM('active','inactive','suspended','pending','rejected') NOT NULL DEFAULT 'active',
  profile_photo VARCHAR(255) NULL,
  two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
  two_factor_secret VARCHAR(128) NULL,
  last_login_at DATETIME NULL,
  last_login_ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role_status (role,status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_group VARCHAR(80) NOT NULL,
  setting_key VARCHAR(120) NOT NULL,
  setting_value MEDIUMTEXT NULL,
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  updated_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_setting (setting_group,setting_key),
  CONSTRAINT fk_settings_user FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  title VARCHAR(200) NOT NULL,
  eyebrow VARCHAR(100) NULL,
  hero_title VARCHAR(240) NULL,
  hero_subtitle VARCHAR(600) NULL,
  body MEDIUMTEXT NULL,
  image_path VARCHAR(255) NULL,
  meta_title VARCHAR(240) NULL,
  meta_description VARCHAR(500) NULL,
  answer_summary VARCHAR(900) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pages_user FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_pages_active_sort (is_active,sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS hero_slides (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  subtitle VARCHAR(500) NULL,
  image_path VARCHAR(255) NULL,
  mobile_image_path VARCHAR(255) NULL,
  start_at DATETIME NULL,
  end_at DATETIME NULL,
  button_text VARCHAR(80) NULL,
  button_url VARCHAR(255) NULL,
  secondary_text VARCHAR(80) NULL,
  secondary_url VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_slide_active_sort (is_active,sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS content_sections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_key VARCHAR(120) NOT NULL UNIQUE,
  title VARCHAR(200) NULL,
  eyebrow VARCHAR(100) NULL,
  content MEDIUMTEXT NULL,
  cta_text VARCHAR(100) NULL,
  cta_url VARCHAR(255) NULL,
  image_path VARCHAR(255) NULL,
  item_limit TINYINT UNSIGNED NOT NULL DEFAULT 3,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  updated_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_cs_user FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS gallery_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  caption VARCHAR(700) NULL,
  image_path VARCHAR(255) NOT NULL,
  category VARCHAR(100) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_gallery_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_gallery_active_sort (is_active,sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS campaign_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  slug VARCHAR(130) NOT NULL UNIQUE,
  description VARCHAR(600) NULL,
  icon VARCHAR(40) NULL,
  image_path VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS campaigns (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  short_description VARCHAR(500) NULL,
  description MEDIUMTEXT NULL,
  target_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'LKR',
  category VARCHAR(100) NULL,
  location VARCHAR(160) NULL,
  cover_image VARCHAR(255) NULL,
  status ENUM('draft','upcoming','active','completed','cancelled') NOT NULL DEFAULT 'draft',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  start_date DATE NULL,
  end_date DATE NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_campaign_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_campaign_status_dates (status,is_active,is_visible,start_date,end_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS donation_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(140) NOT NULL,
  bank_name VARCHAR(160) NOT NULL,
  account_name VARCHAR(160) NOT NULL,
  account_number VARCHAR(120) NOT NULL,
  iban VARCHAR(120) NULL,
  swift_code VARCHAR(60) NULL,
  branch_name VARCHAR(120) NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'LKR',
  instructions TEXT NULL,
  contact_name VARCHAR(140) NULL,
  contact_position VARCHAR(120) NULL,
  contact_phone VARCHAR(50) NULL,
  contact_email VARCHAR(190) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_da_created FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_da_updated FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_da_active_sort (is_active,sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS donations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  public_id CHAR(36) NOT NULL UNIQUE,
  campaign_id BIGINT UNSIGNED NULL,
  donation_account_id BIGINT UNSIGNED NULL,
  donor_name VARCHAR(160) NOT NULL,
  donor_email VARCHAR(190) NULL,
  donor_phone VARCHAR(60) NULL,
  amount DECIMAL(14,2) NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'LKR',
  payment_method ENUM('bank_transfer','cash','other') NOT NULL DEFAULT 'bank_transfer',
  transaction_reference VARCHAR(140) NULL,
  message TEXT NULL,
  is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
  proof_path VARCHAR(255) NULL,
  proof_original_name VARCHAR(255) NULL,
  proof_mime VARCHAR(100) NULL,
  status ENUM('pending_verification','approved','rejected','refunded','cancelled') NOT NULL DEFAULT 'pending_verification',
  verified_by BIGINT UNSIGNED NULL,
  verified_at DATETIME NULL,
  rejection_reason VARCHAR(500) NULL,
  internal_note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_donation_campaign FOREIGN KEY(campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
  CONSTRAINT fk_donation_account FOREIGN KEY(donation_account_id) REFERENCES donation_accounts(id) ON DELETE SET NULL,
  CONSTRAINT fk_donation_verified FOREIGN KEY(verified_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_donations_status_date (status,created_at),
  INDEX idx_donations_campaign_status (campaign_id,status),
  INDEX idx_donations_email (donor_email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS donation_verifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donation_id BIGINT UNSIGNED NOT NULL,
  admin_id BIGINT UNSIGNED NOT NULL,
  old_status VARCHAR(40) NULL,
  new_status VARCHAR(40) NOT NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_dv_donation FOREIGN KEY(donation_id) REFERENCES donations(id) ON DELETE CASCADE,
  CONSTRAINT fk_dv_admin FOREIGN KEY(admin_id) REFERENCES users(id) ON DELETE RESTRICT,
  INDEX idx_dv_donation (donation_id,created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS volunteers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL UNIQUE,
  volunteer_id VARCHAR(40) NULL UNIQUE,
  full_name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL,
  mobile VARCHAR(60) NOT NULL,
  gender VARCHAR(40) NOT NULL,
  date_of_birth DATE NULL,
  address VARCHAR(500) NULL,
  city VARCHAR(120) NULL,
  country VARCHAR(120) NULL,
  skills TEXT NULL,
  occupation VARCHAR(160) NULL,
  interests TEXT NULL,
  emergency_contact VARCHAR(220) NULL,
  profile_photo VARCHAR(255) NULL,
  status ENUM('pending','approved','active','suspended','rejected','inactive') NOT NULL DEFAULT 'pending',
  approved_by BIGINT UNSIGNED NULL,
  approved_at DATETIME NULL,
  rejection_reason VARCHAR(500) NULL,
  verification_token CHAR(64) NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_vol_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_vol_admin FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_vol_status (status),
  INDEX idx_vol_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS member_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  mobile VARCHAR(60) NULL,
  date_of_birth DATE NULL,
  nic_number VARCHAR(80) NULL UNIQUE,
  gender VARCHAR(30) NULL,
  address VARCHAR(500) NULL,
  member_number VARCHAR(60) NULL UNIQUE,
  joined_at DATE NULL,
  role_title VARCHAR(140) NULL,
  bio VARCHAR(1000) NULL,
  verification_token CHAR(64) NULL UNIQUE,
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  approved_by BIGINT UNSIGNED NULL,
  approved_at DATETIME NULL,
  rejected_at DATETIME NULL,
  rejection_reason VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_member_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sender_id BIGINT UNSIGNED NOT NULL,
  recipient_id BIGINT UNSIGNED NOT NULL,
  subject VARCHAR(180) NULL,
  body TEXT NOT NULL,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_msg_sender FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_recipient FOREIGN KEY(recipient_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_msg_recipient_read (recipient_id,read_at,created_at),
  INDEX idx_msg_pair (sender_id,recipient_id,created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS message_broadcasts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sender_id BIGINT UNSIGNED NOT NULL,
  audience VARCHAR(40) NOT NULL,
  subject VARCHAR(180) NOT NULL,
  body TEXT NOT NULL,
  recipient_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_broadcast_sender FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_broadcast_created (created_at,id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  message VARCHAR(1000) NOT NULL,
  type VARCHAR(30) NOT NULL DEFAULT 'info',
  link_url VARCHAR(255) NULL,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notif_user_read (user_id,read_at,created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  description MEDIUMTEXT NULL,
  image_path VARCHAR(255) NULL,
  event_date DATE NOT NULL,
  start_time TIME NULL,
  end_time TIME NULL,
  location VARCHAR(180) NULL,
  status ENUM('draft','published','cancelled','completed') NOT NULL DEFAULT 'draft',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_event_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_event_date_status (event_date,status,is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contact_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL,
  mobile VARCHAR(60) NULL,
  subject VARCHAR(180) NOT NULL,
  category VARCHAR(80) NOT NULL DEFAULT 'general',
  message TEXT NOT NULL,
  status ENUM('new','open','in_progress','replied','closed') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_contact_status_date (status,created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  module VARCHAR(80) NOT NULL,
  record_id BIGINT UNSIGNED NULL,
  description VARCHAR(500) NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_action_date (action,created_at),
  INDEX idx_audit_user_date (user_id,created_at)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS social_links (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  platform VARCHAR(80) NOT NULL,
  label VARCHAR(100) NULL,
  url VARCHAR(500) NOT NULL,
  icon VARCHAR(30) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_social_created FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_social_updated FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_social_active_sort (is_active,sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS post_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  description VARCHAR(400) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_post_categories_active (is_active,sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS posts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_type ENUM('news','blog') NOT NULL DEFAULT 'news',
  category_id BIGINT UNSIGNED NULL,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  excerpt VARCHAR(800) NULL,
  content MEDIUMTEXT NULL,
  featured_image VARCHAR(255) NULL,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  published_at DATETIME NULL,
  meta_title VARCHAR(240) NULL,
  meta_description VARCHAR(500) NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_posts_created FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_posts_updated FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_posts_category FOREIGN KEY(category_id) REFERENCES post_categories(id) ON DELETE SET NULL,
  INDEX idx_posts_category (category_id,status,published_at),
  INDEX idx_posts_public (status,published_at,post_type),
  INDEX idx_posts_featured (is_featured,status,published_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chat_conversations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  public_token CHAR(48) NOT NULL UNIQUE,
  visitor_name VARCHAR(120) NOT NULL,
  visitor_email VARCHAR(190) NOT NULL,
  visitor_phone VARCHAR(60) NULL,
  category VARCHAR(40) NOT NULL DEFAULT 'general',
  status ENUM('open','waiting','closed') NOT NULL DEFAULT 'open',
  assigned_admin_id BIGINT UNSIGNED NULL,
  admin_notes TEXT NULL,
  admin_last_read_at DATETIME NULL,
  visitor_last_seen_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_chat_admin FOREIGN KEY(assigned_admin_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_chat_status_date (status,last_message_at),
  INDEX idx_chat_presence (status,visitor_last_seen_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chat_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  sender_type ENUM('visitor','admin','system') NOT NULL,
  sender_user_id BIGINT UNSIGNED NULL,
  sender_name VARCHAR(120) NULL,
  message TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_chatmsg_conversation FOREIGN KEY(conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_chatmsg_user FOREIGN KEY(sender_user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_chatmsg_conversation (conversation_id,id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
  attempt_key CHAR(64) PRIMARY KEY,
  attempts INT NOT NULL DEFAULT 0,
  last_attempt_at DATETIME NOT NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS=1;

INSERT INTO settings(setting_group,setting_key,setting_value,is_public) VALUES
('foundation','name','97 Foundation',1),
('foundation','tagline','Hope. Help. Humanity.',1),
('foundation','email','97found@gmail.com',1),
('foundation','phone','+94 71 258 3411',1),
('foundation','address','Thambala, Polonnaruwa, Sri Lanka',1),
('foundation','opening_hours','Mon - Fri: 8:00 AM - 6:00 PM',1),
('foundation','logo_path','',1),
('foundation','logo_enabled','1',1),
('foundation','favicon_path','',1),
('foundation','facebook','https://www.facebook.com/share/19hyNTG2oZ/',1),
('foundation','wordmark_path','',1),
('foundation','instagram','',1),
('foundation','linkedin','',1),
('foundation','youtube','',1),
('foundation','portal_login_image_path','',1),
('foundation','member_login_image_path','',1),
('foundation','admin_login_background_path','',0),
('fundraising','enabled','1',1),
('fundraising','general_when_paused','1',1),
('system','v72_applied','1',0),
('donation','instructions','Transfer to one of the accounts below, keep your payment reference, then submit the donation form with proof for verification.',1),
('seo','default_title','97 Foundation Sri Lanka | Community Support in Polonnaruwa Thambala',1),
('seo','social_image','',1),
('seo','default_description','97 Foundation is a Sri Lanka community support and charity platform serving Polonnaruwa, Thambala and nearby areas through campaigns, donations, volunteering and social support.',1),
('seo','keywords','97 Foundation, Sri Lanka charity, Polonnaruwa foundation, Thambala community support, volunteer in Polonnaruwa',1),
('seo','target_country','Sri Lanka',1),
('seo','target_district','Polonnaruwa',1),
('seo','target_locality','Thambala',1),
('seo','answer_summary','97 Foundation is a Sri Lanka community-focused foundation that supports people in need through campaigns, volunteering and transparent donations, especially for Polonnaruwa and Thambala communities.',1),
('security','session_timeout','120',0)
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

INSERT INTO pages(slug,title,eyebrow,hero_title,hero_subtitle,body,image_path,meta_title,meta_description,answer_summary,is_active,sort_order) VALUES
('about','About 97 Foundation','97 FOUNDATION','About 97 Foundation','Hope. Help. Humanity.','97 Foundation is built around transparent support, accountable fundraising and meaningful community action. Use the admin CMS to replace this demo content with your organisation story, mission, vision, values and leadership information.','demo/hero-children.jpg','About 97 Foundation','Learn about 97 Foundation, its mission, values and community work.','97 Foundation is a community-focused charity platform designed to connect people who need help with donors, volunteers and accountable fundraising.',1,1),
('causes','Our Causes','OUR CAUSES','Areas Where We Serve','Explore the Foundation areas of support. Fundraising campaigns are managed separately for clarity and transparency.','Cause areas describe the types of support provided by 97 Foundation. Add, edit, activate and reorder individual cause cards from Admin > Cause Areas.','demo/cause-food.jpg','Our Causes | 97 Foundation','Explore education, healthcare, food, essentials and emergency support cause areas at 97 Foundation.','97 Foundation cause areas organise the types of community support offered; fundraising campaigns are managed separately.',1,2),
('campaigns','Fundraising Campaigns','FUNDRAISING','Support a Better Tomorrow','Browse active and upcoming fundraising campaigns. Public raised totals include approved donations only.','Campaigns have individual goals, dates, images and visibility controls. Administrators can activate or hide each campaign without deleting donation history.','demo/campaign-families.jpg','Fundraising Campaigns | 97 Foundation','View active and upcoming 97 Foundation fundraising campaigns with verified public totals.','97 Foundation campaign totals count only administrator-approved donations, while pending transfers remain excluded until verification.',1,3),
('events','Events','COMMUNITY EVENTS','Upcoming Events & Activities','Join community programs, outreach activities and volunteer initiatives.','Events can be created and managed from the administration area.','demo/cause-emergency.jpg','Events | 97 Foundation','Discover upcoming 97 Foundation events and community activities.','97 Foundation events bring volunteers, beneficiaries and community partners together for practical service and support.',1,4),
('gallery','Gallery','OUR GALLERY','Stories in Pictures','Moments of service, compassion and community impact.','Every gallery image can be uploaded, reordered, activated or removed by an administrator.','demo/campaign-education.jpg','Gallery | 97 Foundation','View photos from 97 Foundation activities, campaigns and community work.','The 97 Foundation gallery documents community activities, volunteer service and campaign impact through administrator-managed photos.',1,5),
('volunteer','Become a Volunteer','GET INVOLVED','Bring Your Time, Skills and Compassion','Create a volunteer account. After administrator approval, sign in to your portal, communicate with the Foundation and access your verified Volunteer ID and QR.','Volunteer applications remain pending until administrator approval. Approved volunteers receive a unique permanent ID, secure portal access and a public verification QR link.','demo/cause-education.jpg','Become a Volunteer | 97 Foundation','Apply to become a 97 Foundation volunteer and receive secure portal access after approval.','Approved 97 Foundation volunteers can sign in, message administrators, manage their profile and access a unique ID with public QR verification.',1,6),
('donate','Donate','DONATE WITH CONFIDENCE','Your Support Can Create Meaningful Change','View administrator-managed donation account details, choose a campaign if desired and submit your donation for verification.','Donation account information and contact persons are controlled by administrators. Payment proofs are kept private and public campaign totals increase only after approval.','demo/campaign-health.jpg','Donate | 97 Foundation','Donate securely to 97 Foundation using administrator-managed account details and verification workflows.','97 Foundation displays administrator-managed donation accounts and counts a donation toward campaign totals only after administrator approval.',1,7),
('what-we-do','What We Do','WHAT WE DO','What We Do','Education, family support, student empowerment and social work for communities across Polonnaruwa and Thambala.','97 Foundation delivers practical, accountable support through four connected programme areas: education support, family support, student empowerment and social work. Every programme is planned with local volunteers, reviewed by Foundation officials and reported transparently.','demo/cause-education.jpg','What We Do | 97 Foundation','Discover the programmes and community support delivered by 97 Foundation in Sri Lanka.','97 Foundation supports communities through education support, family support, student empowerment and social work.',1,2),
('contact','Contact Us','WE ARE HERE TO HELP','Contact 97 Foundation','Talk to our team about donations, volunteering, partnerships or support.','Use the secure contact form to reach 97 Foundation.','demo/cause-healthcare.jpg','Contact 97 Foundation','Contact 97 Foundation for donation support, volunteering, partnerships and community assistance.','You can contact 97 Foundation by phone, email or the secure website inquiry form for donations, volunteering and support.',1,8)
ON DUPLICATE KEY UPDATE title=VALUES(title),hero_title=VALUES(hero_title),hero_subtitle=VALUES(hero_subtitle),body=VALUES(body),image_path=COALESCE(NULLIF(image_path,''),VALUES(image_path)),meta_title=VALUES(meta_title),meta_description=VALUES(meta_description),answer_summary=VALUES(answer_summary);

INSERT INTO content_sections(section_key,title,eyebrow,content,cta_text,cta_url,image_path,item_limit,is_active,sort_order) VALUES
('home_quick_help','I Need Help','SUPPORT','Get information, support and assistance from our team.','Get Help','contact',NULL,3,1,1),
('home_quick_donate','I Want to Help','DONATE','Your donation can bring hope and meaningful change.','Donate Now','donate',NULL,3,1,2),
('home_quick_volunteer','I Want to Get Involved','VOLUNTEER','Join the volunteer community and contribute your skills.','Join Now','volunteer',NULL,3,1,3),
('home_about','Together, We Can Make a Difference','ABOUT 97 FOUNDATION','97 Foundation supports people in need, strengthens communities and creates meaningful change through compassion, community and action.','Learn About Us','about','demo/hero-children.jpg',3,1,10),
('home_causes','We Work For a Better Tomorrow','OUR CAUSES','Explore the Foundation areas of support.','Explore All Causes','causes',NULL,4,1,20),
('home_campaigns','Support Our Recent Campaigns','FEATURED CAMPAIGNS','Support active fundraising campaigns with verified donation totals.','View All Campaigns','campaigns',NULL,3,1,30),
('home_gallery','Moments That Matter','OUR GALLERY','Stories in pictures from Foundation activities and community impact.','Open Gallery','gallery',NULL,6,1,40),
('home_events','Join Our Next Community Activities','UPCOMING EVENTS','New published events are shown here automatically.','View All Events','events',NULL,3,1,50),
('home_impact','Real stories. Real impact.','OUR IMPACT','Every approved donation, campaign and volunteer action is handled through a controlled workflow designed for transparency and accountability.','See Our Impact','causes',NULL,3,1,60),
('home_news','Latest From 97 Foundation','NEWS & BLOG','New published news and blog posts are shown here automatically.','View All News & Blog','news',NULL,3,1,70),
('home_newsletter','Stay Connected with 97 Foundation','STAY CONNECTED','Follow campaigns, events, volunteer activities and community updates.','Contact Us','contact',NULL,3,1,80)
ON DUPLICATE KEY UPDATE title=VALUES(title),eyebrow=VALUES(eyebrow),content=VALUES(content),cta_text=VALUES(cta_text),cta_url=VALUES(cta_url),image_path=COALESCE(content_sections.image_path,VALUES(image_path)),item_limit=VALUES(item_limit);

INSERT INTO hero_slides(title,subtitle,image_path,button_text,button_url,secondary_text,secondary_url,is_active,sort_order) VALUES
('Together, We Can Make a Difference','97 Foundation is dedicated to supporting people in need, strengthening communities and creating meaningful change.','demo/hero-children.jpg','Donate Now','donate','Get Help','contact',1,1),
('Small Acts. Greater Impact.','Join our volunteer community and turn compassion into meaningful action.','demo/hero-children.jpg','Become a Volunteer','volunteer','Member Login','login',1,2)
ON DUPLICATE KEY UPDATE title=VALUES(title),subtitle=VALUES(subtitle);

INSERT IGNORE INTO campaign_categories(name,slug,description,icon,image_path,is_active,sort_order) VALUES
('Education','education','Learning opportunities and educational support.','▤','demo/cause-education.jpg',1,1),
('Healthcare','healthcare','Medical and wellbeing support for communities.','✚','demo/cause-healthcare.jpg',1,2),
('Food & Essentials','food-essentials','Food, clean water and essential supplies.','◉','demo/cause-food.jpg',1,3),
('Emergency Support','emergency-support','Rapid assistance for urgent community needs.','⚑','demo/cause-emergency.jpg',1,4);


INSERT IGNORE INTO gallery_items(title,caption,image_path,category,is_active,sort_order) VALUES
('Education & Learning','Community learning and educational support.','demo/cause-education.jpg','Education',1,1),
('Healthcare Support','Community health and wellbeing support.','demo/cause-healthcare.jpg','Healthcare',1,2),
('Food & Essentials','Supporting essential community needs.','demo/cause-food.jpg','Community',1,3),
('Emergency Response','Volunteer-led community support activities.','demo/cause-emergency.jpg','Volunteering',1,4),
('Families & Community','Campaigns focused on families and practical assistance.','demo/campaign-families.jpg','Campaigns',1,5),
('Education Campaign','Helping create opportunities through education.','demo/campaign-education.jpg','Campaigns',1,6);

-- V6.2 Online Donation / Payment Gateway
ALTER TABLE donations MODIFY payment_method ENUM('bank_transfer','cash','other','online_payment') NOT NULL DEFAULT 'bank_transfer';

CREATE TABLE IF NOT EXISTS payment_gateway_configs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(60) NOT NULL UNIQUE,
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  mode ENUM('sandbox','live') NOT NULL DEFAULT 'sandbox',
  merchant_id VARCHAR(160) NULL,
  merchant_secret_enc MEDIUMTEXT NULL,
  api_app_id VARCHAR(190) NULL,
  api_app_secret_enc MEDIUMTEXT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'LKR',
  min_amount DECIMAL(14,2) NOT NULL DEFAULT 100.00,
  max_amount DECIMAL(14,2) NOT NULL DEFAULT 1000000.00,
  receipt_prefix VARCHAR(20) NOT NULL DEFAULT '97D',
  receipt_email_enabled TINYINT(1) NOT NULL DEFAULT 0,
  last_test_status VARCHAR(30) NOT NULL DEFAULT 'never',
  last_test_message VARCHAR(500) NULL,
  last_test_at DATETIME NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_payment_gateway_updated_by FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payment_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  public_id VARCHAR(64) NOT NULL UNIQUE,
  donation_id BIGINT UNSIGNED NULL UNIQUE,
  campaign_id BIGINT UNSIGNED NULL,
  gateway VARCHAR(60) NOT NULL,
  gateway_order_id VARCHAR(120) NOT NULL UNIQUE,
  gateway_payment_id VARCHAR(120) NULL UNIQUE,
  status ENUM('pending','processing','successful','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  amount DECIMAL(14,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'LKR',
  donor_name VARCHAR(160) NOT NULL,
  donor_email VARCHAR(190) NULL,
  donor_phone VARCHAR(60) NOT NULL,
  donor_address VARCHAR(500) NULL,
  donor_city VARCHAR(120) NULL,
  donor_country VARCHAR(120) NOT NULL DEFAULT 'Sri Lanka',
  payment_method_preference VARCHAR(40) NULL,
  payment_method VARCHAR(80) NULL,
  gateway_status_code VARCHAR(30) NULL,
  receipt_number VARCHAR(80) NULL UNIQUE,
  callback_signature_hash CHAR(64) NULL,
  callback_received_at DATETIME NULL,
  paid_at DATETIME NULL,
  failed_at DATETIME NULL,
  cancelled_at DATETIME NULL,
  refunded_at DATETIME NULL,
  last_verified_at DATETIME NULL,
  retry_count INT UNSIGNED NOT NULL DEFAULT 0,
  metadata_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_payment_transaction_donation FOREIGN KEY(donation_id) REFERENCES donations(id) ON DELETE SET NULL,
  CONSTRAINT fk_payment_transaction_campaign FOREIGN KEY(campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
  INDEX idx_payment_status_date(status,created_at),
  INDEX idx_payment_donor_email(donor_email),
  INDEX idx_payment_gateway_date(gateway,created_at),
  INDEX idx_payment_paid_at(paid_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payment_webhook_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  gateway VARCHAR(60) NOT NULL,
  event_hash CHAR(64) NOT NULL UNIQUE,
  order_id VARCHAR(120) NOT NULL,
  status_code VARCHAR(20) NULL,
  verified TINYINT(1) NOT NULL DEFAULT 0,
  payload_json JSON NULL,
  received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_payment_webhook_order(order_id,received_at)
) ENGINE=InnoDB;

INSERT INTO payment_gateway_configs(provider,enabled,mode,currency,min_amount,max_amount,receipt_prefix,receipt_email_enabled)
VALUES('payhere',0,'sandbox','LKR',100.00,1000000.00,'97D',0)
ON DUPLICATE KEY UPDATE provider=VALUES(provider);

-- V7.1 Foundation officials (current and past office bearers)
CREATE TABLE IF NOT EXISTS foundation_officials (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(160) NOT NULL,
  position VARCHAR(140) NOT NULL,
  photo_path VARCHAR(255) NULL,
  term_start SMALLINT UNSIGNED NULL,
  term_end SMALLINT UNSIGNED NULL,
  status ENUM('current','past') NOT NULL DEFAULT 'current',
  bio VARCHAR(1200) NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(60) NULL,
  show_contact TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_officials_public (status,is_active,sort_order),
  CONSTRAINT fk_officials_creator FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO post_categories(name,slug,description,sort_order,is_active) VALUES
('Announcements','announcements','Official notices and Foundation updates.',10,1),
('Community Stories','community-stories','Stories from the people and families we support.',20,1),
('Programmes & Activities','programmes-activities','Education, family support and volunteer activities.',30,1);
