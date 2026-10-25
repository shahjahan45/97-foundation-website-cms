<?php
declare(strict_types=1);
/**
 * 97 Foundation V7.1 upgrade — header, brand, navigation and hero update.
 *
 * Run once from the project root (safe to run again):
 *   php database/upgrade_v7_1.php
 * XAMPP on Windows:
 *   C:\xampp\php\php.exe database\upgrade_v7_1.php
 *
 * The same update can be applied from Admin → Content & Website → Branding & SEO.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }
require dirname(__DIR__) . '/bootstrap.php';

try {
    foreach (App\Core\Upgrade71::run(null) as $line) echo '✓ ' . $line . "\n";
    echo "\nV7.1 upgrade complete.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'V7.1 upgrade failed: ' . $e->getMessage() . "\n");
    exit(1);
}
