<?php
declare(strict_types=1);
/**
 * 97 Foundation V7.2 upgrade — News & Blog categories and the fundraising switch.
 *
 * The website applies this automatically on the first page load after the files are installed.
 * To run it manually from the project root (safe to run again):
 *   php database/upgrade_v7_2.php
 * XAMPP on Windows:
 *   C:\xampp\php\php.exe database\upgrade_v7_2.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }
require dirname(__DIR__) . '/bootstrap.php';

try {
    foreach (App\Core\Upgrade72::run(null) as $line) echo '✓ ' . $line . "\n";
    @file_put_contents(dirname(__DIR__) . '/storage/logs/.v72-schema', date('c'));
    echo "\nV7.2 upgrade complete.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'V7.2 upgrade failed: ' . $e->getMessage() . "\n");
    exit(1);
}
