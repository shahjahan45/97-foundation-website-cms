<?php
require dirname(__DIR__).'/bootstrap.php';
use App\Core\Database;
function column_exists_v54(string $table,string $column):bool {
    try{return (bool)Database::one('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1',[$table,$column]);}
    catch(Throwable){return false;}
}
if(!column_exists_v54('content_sections','eyebrow')) Database::exec('ALTER TABLE content_sections ADD COLUMN eyebrow VARCHAR(100) NULL AFTER title');
if(!column_exists_v54('content_sections','item_limit')) Database::exec('ALTER TABLE content_sections ADD COLUMN item_limit TINYINT UNSIGNED NOT NULL DEFAULT 3 AFTER image_path');
$rows=[
 ['home_quick_help','I Need Help','SUPPORT','Get information, support and assistance from our team.','Get Help','contact',null,3,1,1],
 ['home_quick_donate','I Want to Help','DONATE','Your donation can bring hope and meaningful change.','Donate Now','donate',null,3,1,2],
 ['home_quick_volunteer','I Want to Get Involved','VOLUNTEER','Join the volunteer community and contribute your skills.','Join Now','volunteer',null,3,1,3],
 ['home_about','Together, We Can Make a Difference','ABOUT 97 FOUNDATION','97 Foundation supports people in need, strengthens communities and creates meaningful change through compassion, community and action.','Learn About Us','about','demo/hero-children.jpg',3,1,10],
 ['home_causes','We Work For a Better Tomorrow','OUR CAUSES','Explore the Foundation areas of support.','Explore All Causes','causes',null,4,1,20],
 ['home_campaigns','Support Our Recent Campaigns','FEATURED CAMPAIGNS','Support active fundraising campaigns with verified donation totals.','View All Campaigns','campaigns',null,3,1,30],
 ['home_gallery','Moments That Matter','OUR GALLERY','Stories in pictures from Foundation activities and community impact.','Open Gallery','gallery',null,6,1,40],
 ['home_events','Join Our Next Community Activities','UPCOMING EVENTS','New published events are shown here automatically.','View All Events','events',null,3,1,50],
 ['home_impact','Real stories. Real impact.','OUR IMPACT','Every approved donation, campaign and volunteer action is handled through a controlled workflow designed for transparency and accountability.','See Our Impact','causes',null,3,1,60],
 ['home_news','Latest From 97 Foundation','NEWS & BLOG','New published news and blog posts are shown here automatically.','View All News & Blog','news',null,3,1,70],
 ['home_newsletter','Stay Connected with 97 Foundation','STAY CONNECTED','Follow campaigns, events, volunteer activities and community updates.','Contact Us','contact',null,3,1,80],
];
foreach($rows as $r){
    Database::exec('INSERT INTO content_sections(section_key,title,eyebrow,content,cta_text,cta_url,image_path,item_limit,is_active,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE eyebrow=COALESCE(NULLIF(eyebrow,\'\'),VALUES(eyebrow)),item_limit=IF(item_limit<1,VALUES(item_limit),item_limit)', $r);
}
echo "V5.4 website content upgrade completed.\n";
