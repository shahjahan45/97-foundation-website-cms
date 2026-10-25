<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use App\Core\Database;
use App\Core\Upload;

if(PHP_SAPI!=='cli'){http_response_code(403);exit("CLI only.\n");}
if(!extension_loaded('gd')||!function_exists('imagewebp')){fwrite(STDERR,"[WARN] GD/WebP is not available. Existing images remain usable, but optimized WebP thumbnails cannot be generated on this server.\n");exit(0);}
$rows=Database::all('SELECT id,profile_photo FROM users WHERE role="member" AND profile_photo IS NOT NULL AND profile_photo<>"" ORDER BY id');
$total=count($rows);$ok=0;
echo "Optimizing {$total} member profile image(s)...\n";
foreach($rows as $row){
    try{Upload::optimizeMemberPhoto((string)$row['profile_photo']);$ok++;echo "[OK] Member #{$row['id']}\n";}
    catch(Throwable $e){echo "[WARN] Member #{$row['id']}: {$e->getMessage()}\n";}
}
echo "Done. Optimized {$ok}/{$total} member profile image(s).\n";
