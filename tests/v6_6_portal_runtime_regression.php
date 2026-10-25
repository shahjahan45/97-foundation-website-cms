<?php
$root=dirname(__DIR__);
$portal=file_get_contents($root.'/app/Controllers/Portal/PortalController.php');
$schema=file_get_contents($root.'/database/schema.sql');
$fail=[];
if(str_contains($portal,'location,featured_image,slug FROM events')) $fail[]='Portal still queries non-existent events.featured_image column.';
if(!str_contains($portal,'image_path AS featured_image')) $fail[]='Portal event query does not alias the real image_path column.';
if(!str_contains($schema,'CREATE TABLE IF NOT EXISTS events')) $fail[]='Events schema missing.';
if(!preg_match('/CREATE TABLE IF NOT EXISTS events[\s\S]*?image_path VARCHAR\(255\)/',$schema)) $fail[]='events.image_path not present in schema.';
if(substr_count($portal,'catch (\\Throwable)')<3) $fail[]='Optional portal dashboard queries are not isolated from deployment-time DB errors.';
if($fail){fwrite(STDERR,"V6.6 portal regression FAILED:\n- ".implode("\n- ",$fail)."\n");exit(1);} 
echo "V6.6 portal regression passed.\n";
