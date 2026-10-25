<?php
/**
 * 97 Foundation application configuration.
 *
 * Production-safe precedence:
 *   1. Built-in safe defaults / automatic base URL detection
 *   2. Optional private config.local.php (or storage/private/config.local.php)
 *   3. Server environment variables (highest priority)
 *
 * IMPORTANT: the release ZIP intentionally does NOT contain config.local.php.
 * Create it once on the live server and keep it when deploying future updates.
 */

$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$autoBaseUrl = '';
if ($scriptName !== '' && basename($scriptName) === 'index.php') {
    $scriptDir = str_replace('\\', '/', dirname($scriptName));
    $autoBaseUrl = ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '\\')
        ? ''
        : rtrim($scriptDir, '/');
}

$config = [
    'app' => [
        'name' => '97 Foundation',
        'env' => 'production',
        'debug' => false,
        'url' => $autoBaseUrl,
        'timezone' => 'Asia/Colombo',
        'session_name' => 'foundation97_session',
        'session_lifetime' => 7200,
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'foundation97',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'payments' => [
        'encryption_key' => '',
    ],
    'security' => [
        'csrf_ttl' => 7200,
        'max_upload_bytes' => 8 * 1024 * 1024,
        'login_attempts' => 5,
        'login_lock_minutes' => 15,
        'member_password_min' => 8,
        'member_password_max' => 12,
        'csp' => "default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self' https://www.payhere.lk https://sandbox.payhere.lk",
    ],
];

// Persistent live configuration. Keep this file on the server between releases.
$localCandidates = [
    __DIR__ . '/config.local.php',
    __DIR__ . '/storage/private/config.local.php',
];
foreach ($localCandidates as $localFile) {
    if (!is_file($localFile)) continue;
    $local = require $localFile;
    if (is_array($local)) $config = array_replace_recursive($config, $local);
    break;
}

// Environment variables intentionally override the local file when supplied.
$env = static function(string $key): ?string {
    $value = getenv($key);
    if ($value === false) return null;
    $value = trim((string)$value);
    return $value === '' ? null : $value;
};

if (($v=$env('APP_NAME')) !== null) $config['app']['name']=$v;
if (($v=$env('APP_ENV')) !== null) $config['app']['env']=$v;
if (($v=$env('APP_DEBUG')) !== null) $config['app']['debug']=filter_var($v,FILTER_VALIDATE_BOOL);
if (($v=$env('APP_URL')) !== null) $config['app']['url']=rtrim($v,'/');
if (($v=$env('APP_TIMEZONE')) !== null) $config['app']['timezone']=$v;
if (($v=$env('SESSION_NAME')) !== null) $config['app']['session_name']=$v;
if (($v=$env('SESSION_LIFETIME')) !== null) $config['app']['session_lifetime']=max(900,(int)$v);

if (($v=$env('DB_HOST')) !== null) $config['db']['host']=$v;
if (($v=$env('DB_PORT')) !== null) $config['db']['port']=$v;
if (($v=$env('DB_DATABASE')) !== null) $config['db']['name']=$v;
if (($v=$env('DB_USERNAME')) !== null) $config['db']['user']=$v;
// Password may intentionally be empty locally; only override when the variable exists.
$dbPass=getenv('DB_PASSWORD');
if ($dbPass !== false) $config['db']['pass']=(string)$dbPass;

if (($v=$env('PAYMENT_ENCRYPTION_KEY')) !== null) $config['payments']['encryption_key']=$v;
elseif (($v=$env('APP_KEY')) !== null) $config['payments']['encryption_key']=$v;

return $config;
