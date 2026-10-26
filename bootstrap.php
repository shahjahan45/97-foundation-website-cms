<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['app']['timezone']);

$logDir=__DIR__ . '/storage/logs';
if(!is_dir($logDir)) @mkdir($logDir,0750,true);
ini_set('display_errors', $config['app']['debug'] ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', $logDir . '/php-error.log');
error_reporting(E_ALL);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require $file;
});

require_once __DIR__ . '/app/helpers.php';

App\Core\Security::boot($config);
App\Core\Database::configure($config['db']);
App\Core\App::setConfig($config);

// V7.2: apply the small, idempotent database update automatically on the first web request.
if (PHP_SAPI !== 'cli') {
    try { App\Core\Upgrade72::autoApply(); } catch (\Throwable $e) { error_log('V7.2 database update could not run: ' . $e->getMessage()); }
}

// Production-safe exception/fatal handling. Full technical details stay in the
// server log while visitors receive a stable, branded response with a reference.
$renderProductionFailure = static function(string $reference): void {
    if (PHP_SAPI === 'cli') return;
    if (!headers_sent()) {
        http_response_code(500);
        header('Cache-Control: no-store, private');
        $accept=(string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $xhr=(string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        if (stripos($accept,'application/json')!==false || $xhr!=='') {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok'=>false,'message'=>'We could not complete this request. Please try again.','reference'=>$reference]);
            return;
        }
        header('Content-Type: text/html; charset=UTF-8');
    }
    $logo='';
    try { $logo=foundation_logo_url(false); } catch (Throwable) {}
    $mark=$logo!==''?'<img src="'.e($logo).'" alt="97 Foundation logo">':'<span>97</span>';
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>97 Foundation — Request unavailable</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at 50% 0,rgba(16,175,91,.10),transparent 34%),#f3f8f5;color:#10251e;font-family:Inter,system-ui,-apple-system,Segoe UI,sans-serif}.e{width:min(100%,570px);padding:38px;border:1px solid #dfeae4;border-radius:28px;background:rgba(255,255,255,.95);box-shadow:0 26px 70px rgba(10,70,48,.11);text-align:center}.m{width:74px;height:74px;border-radius:22px;display:grid;place-items:center;margin:auto;background:#edf8f1;overflow:hidden}.m img{width:58px;height:58px;object-fit:contain}.m span{font-size:28px;font-weight:900;color:#087d48}h1{font-size:29px;margin:20px 0 8px}p{font-size:14px;line-height:1.7;color:#62776f}a{display:inline-flex;margin-top:12px;padding:13px 19px;border-radius:13px;background:#07984e;color:#fff;text-decoration:none;font-weight:850}.r{display:block;margin-top:15px;color:#8a9b94;font-size:11px}@media(max-width:520px){.e{padding:28px 20px;border-radius:22px}h1{font-size:25px}}</style></head><body><main class="e"><div class="m">'.$mark.'</div><h1>We could not complete this request</h1><p>The request was safely stopped and no technical error details were exposed. Please try again. If the problem continues, provide the reference below to the Foundation administrator.</p><a href="javascript:location.reload()">Try again</a><small class="r">Reference: '.e($reference).'</small></main></body></html>';
};

set_exception_handler(static function(Throwable $e) use ($renderProductionFailure,$config): void {
    $ref='ERR-'.strtoupper(substr(bin2hex(random_bytes(8)),0,12));
    error_log($ref.' uncaught '.get_class($e).': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine()."\n".$e->getTraceAsString());
    $renderProductionFailure($ref);
});

register_shutdown_function(static function() use ($renderProductionFailure,$config): void {
    $last=error_get_last();
    if(!$last || !in_array($last['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR],true)) return;
    $ref='FATAL-'.strtoupper(substr(bin2hex(random_bytes(8)),0,12));
    error_log($ref.' '.$last['message'].' in '.$last['file'].':'.$last['line']);
    if (!$config['app']['debug']) $renderProductionFailure($ref);
});
