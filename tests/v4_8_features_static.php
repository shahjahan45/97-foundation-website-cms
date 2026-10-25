<?php
$root=dirname(__DIR__);
$checks=[
 'Account Security route'=>['index.php',"/admin/account-security"],
 'Account Security view'=>['app/Views/admin/account-security.php','Update Security Settings'],
 'Account Security menu'=>['app/Views/layouts/admin.php','Account Security'],
 'Donation create route'=>['index.php',"/admin/donations/create"],
 'Donation edit route'=>['index.php',"/admin/donations/edit/{id}"],
 'Donation view route'=>['index.php',"/admin/donations/view/{id}"],
 'Donation save route'=>['index.php',"/admin/donations/save"],
 'Donation delete route'=>['index.php',"/admin/donations/delete"],
 'Donation CRUD controller create'=>['app/Controllers/Admin/DonationAdminController.php','public function create'],
 'Donation CRUD controller edit'=>['app/Controllers/Admin/DonationAdminController.php','public function edit'],
 'Donation CRUD controller save'=>['app/Controllers/Admin/DonationAdminController.php','public function save'],
 'Donation CRUD controller delete'=>['app/Controllers/Admin/DonationAdminController.php','public function delete'],
 'Donation verification history'=>['app/Views/admin/donation-view.php','Verification History'],
 'Donation professional filters'=>['app/Views/admin/donations.php','donation-filter-bar'],
 'Profile security shortcut'=>['app/Views/admin/profile.php',"admin/account-security"],
];
$crud=[
 'Campaigns'=>['app/Controllers/Admin/CampaignAdminController.php',['public function save','public function delete']],
 'Causes'=>['app/Controllers/Admin/CauseAdminController.php',['public function save','public function delete']],
 'Content'=>['app/Controllers/Admin/ContentAdminController.php',['public function save','public function delete']],
 'Donation Accounts'=>['app/Controllers/Admin/DonationSettingsController.php',['public function save','public function delete']],
 'Events'=>['app/Controllers/Admin/EventAdminController.php',['public function save','public function delete']],
 'Gallery'=>['app/Controllers/Admin/GalleryAdminController.php',['public function save','public function delete']],
 'Members'=>['app/Controllers/Admin/MemberAdminController.php',['public function save','public function delete']],
 'Posts'=>['app/Controllers/Admin/PostAdminController.php',['public function save','public function delete']],
 'Social Media'=>['app/Controllers/Admin/SocialAdminController.php',['public function save','public function delete']],
 'Volunteers'=>['app/Controllers/Admin/VolunteerAdminController.php',['public function save','public function delete']],
 'Live Chat'=>['app/Controllers/Admin/LiveChatAdminController.php',['public function status','public function delete']],
];
$failed=[];
foreach($checks as $name=>[$file,$needle]){$body=@file_get_contents($root.'/'.$file);if($body===false||strpos($body,$needle)===false)$failed[]=$name;}
foreach($crud as $name=>[$file,$needles]){$body=@file_get_contents($root.'/'.$file);foreach($needles as $needle)if($body===false||strpos($body,$needle)===false)$failed[]='CRUD '.$name.' / '.$needle;}
if($failed){fwrite(STDERR,"V4.8 static checks failed:\n - ".implode("\n - ",$failed)."\n");exit(1);}echo "V4.8 account security, Donation CRUD and admin CRUD coverage checks passed.\n";
