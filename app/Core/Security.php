<?php
namespace App\Core;

final class Security
{
    public static function boot(array $config): void
    {
        $https = self::isHttps();
        $lifetime = max(900, (int)($config['app']['session_lifetime'] ?? 7200));

        // Keep application sessions inside protected project storage on shared hosting.
        // This avoids intermittent session loss when the host's global PHP session path
        // is cleaned aggressively or is not writable by the active PHP handler.
        $sessionDir = dirname(__DIR__, 2) . '/storage/private/sessions';
        if (!is_dir($sessionDir)) @mkdir($sessionDir, 0700, true);
        if (is_dir($sessionDir) && is_writable($sessionDir)) {
            @chmod($sessionDir, 0700);
            session_save_path($sessionDir);
        }

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', (string)$lifetime);
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        if ($https) ini_set('session.cookie_secure', '1');

        session_name((string)$config['app']['session_name']);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!@session_start()) {
                error_log('97 Foundation: PHP session could not be started.');
                http_response_code(500);
                exit('Secure session could not be started. Please contact the site administrator.');
            }
        }

        // All dynamic application pages contain session/CSRF state. Prevent a proxy/CDN
        // from serving one visitor an HTML page containing another session's form token.
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
        header('Pragma: no-cache');
        header('Surrogate-Control: no-store');
        header('Vary: Cookie, Authorization');
        header('Expires: 0');

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
        header('Content-Security-Policy: ' . $config['security']['csp']);
        if ($https) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') return true;
        if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) return true;

        $forwarded = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
        if ($forwarded === 'https') return true;
        if (strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on') return true;
        if (strtolower((string)($_SERVER['HTTP_FRONT_END_HTTPS'] ?? '')) === 'on') return true;

        $cf = (string)($_SERVER['HTTP_CF_VISITOR'] ?? '');
        if ($cf !== '' && stripos($cf, 'https') !== false) return true;
        return false;
    }
}
