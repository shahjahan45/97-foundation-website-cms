<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$required=['index.php','bootstrap.php','config.php','database/schema.sql','app/Core/Auth.php','app/Controllers/DonationController.php','app/Controllers/Admin/DonationSettingsController.php','app/Views/donate.php','public/assets/css/app.css'];
foreach($required as $file){if(!is_file($root.'/'.$file)){fwrite(STDERR,"Missing: $file\n");exit(1);}}
echo "Smoke file checks passed.\n";
