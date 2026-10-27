<?php
declare(strict_types=1);
/**
 * 97 Foundation V7.3 upgrade — Student Scholarship & Education Support applications, Privacy Policy and Terms pages.
 *
 * The website applies this automatically on the first page load after the files are installed.
 * To run it manually from the project root (safe to run again):
 *   php database/upgrade_v7_3.php
 * XAMPP on Windows:
 *   C:\xampp\php\php.exe database\upgrade_v7_3.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }
require dirname(__DIR__) . '/bootstrap.php';

try {
    foreach (App\Core\Upgrade73::run(null) as $line) echo '✓ ' . $line . "\n";
    @file_put_contents(dirname(__DIR__) . '/storage/logs/.v73-schema', date('c'));
    echo "\nV7.3 upgrade complete.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'V7.3 upgrade failed: ' . $e->getMessage() . "\n");
    exit(1);
}
