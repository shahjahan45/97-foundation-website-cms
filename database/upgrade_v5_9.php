<?php
require dirname(__DIR__).'/bootstrap.php';
use App\Core\Database;
$settings=[
 ['foundation','portal_login_image_path','',1],
 ['foundation','member_login_image_path','',1],
 ['foundation','admin_login_background_path','',0],
 ['seo','keywords','97 Foundation, Sri Lanka charity, Polonnaruwa foundation, Thambala community support, volunteer in Polonnaruwa',1],
 ['seo','target_country','Sri Lanka',1],
 ['seo','target_district','Polonnaruwa',1],
 ['seo','target_locality','Thambala',1],
 ['seo','answer_summary','97 Foundation is a Sri Lanka community-focused foundation that supports people in need through campaigns, volunteering and transparent donations, especially for Polonnaruwa and Thambala communities.',1],
];
foreach($settings as [$g,$k,$v,$public]) Database::exec('INSERT INTO settings(setting_group,setting_key,setting_value,is_public) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE is_public=VALUES(is_public)',[$g,$k,$v,$public]);
try{
 $r=Database::one('SELECT setting_value FROM settings WHERE setting_group="seo" AND setting_key="default_title" LIMIT 1');$v=trim((string)($r['setting_value']??''));
 if($v===''||$v==='97 Foundation — Hope. Help. Humanity.') Database::exec('UPDATE settings SET setting_value=? WHERE setting_group="seo" AND setting_key="default_title"',['97 Foundation Sri Lanka | Community Support in Polonnaruwa Thambala']);
 $r=Database::one('SELECT setting_value FROM settings WHERE setting_group="seo" AND setting_key="default_description" LIMIT 1');$v=trim((string)($r['setting_value']??''));
 if($v===''||str_contains($v,'transparent community action')) Database::exec('UPDATE settings SET setting_value=? WHERE setting_group="seo" AND setting_key="default_description"',['97 Foundation is a Sri Lanka community support and charity platform serving Polonnaruwa, Thambala and nearby areas through campaigns, donations, volunteering and social support.']);
}catch(Throwable $e){}
echo "V5.9 branding, login visual, favicon and Sri Lanka SEO/AEO upgrade completed.\n";
