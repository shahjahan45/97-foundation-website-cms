<?php
namespace App\Core;

final class Csrf
{
    private const COOKIE = 'foundation97_csrf_key';

    /**
     * Stateless signed double-submit token bound to a browser-only HttpOnly cookie.
     * This remains valid even when a shared host rotates PHP session files between
     * the GET that renders a form and its subsequent POST.
     */
    public static function token(): string
    {
        $ttl = max(900, (int)App::config('security.csrf_ttl', 7200));
        $secret = self::browserSecret($ttl);
        $expires = time() + $ttl;
        $nonce = bin2hex(random_bytes(24));
        $body = $nonce . '.' . $expires;
        $sig = hash_hmac('sha256', $body, $secret);
        $token = $body . '.' . $sig;

        // Keep the latest token in the session as an additional synchronizer check,
        // but do not depend on the session for shared-hosting reliability.
        Session::put('_csrf', $token);
        Session::put('_csrf_time', time());
        return $token;
    }

    public static function validate(?string $token): bool
    {
        if (!is_string($token) || $token === '') return false;

        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;
        [$nonce, $expires, $sig] = $parts;
        if (!preg_match('/^[a-f0-9]{48}$/', $nonce)) return false;
        if (!ctype_digit($expires) || (int)$expires < time()) return false;
        if (!preg_match('/^[a-f0-9]{64}$/', $sig)) return false;

        $cookie = (string)($_COOKIE[self::COOKIE] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/', $cookie)) {
            // Backward-compatible session validation for a form opened immediately
            // before this release was deployed.
            $sessionToken = (string)Session::get('_csrf', '');
            return $sessionToken !== '' && hash_equals($sessionToken, $token);
        }

        $expected = hash_hmac('sha256', $nonce . '.' . $expires, $cookie);
        if (!hash_equals($expected, $sig)) return false;

        return true;
    }

    public static function enforce(): void
    {
        if (self::validate($_POST['_token'] ?? null)) return;

        $ref = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
        error_log('CSRF validation failed ['.$ref.'] path='.(string)($_SERVER['REQUEST_URI'] ?? '').' ip='.Auth::ip());
        http_response_code(419);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, private');
        $logo = function_exists('foundation_logo_url') ? foundation_logo_url(false) : '';
        $logoHtml = $logo !== ''
            ? '<img src="'.e($logo).'" alt="97 Foundation logo">'
            : '<span>97</span>';
        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Secure session refreshed</title><style>body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,sans-serif;background:radial-gradient(circle at 50% 0,rgba(16,175,91,.11),transparent 34%),#f3f8f5;color:#10251e;display:grid;place-items:center;min-height:100vh;padding:24px}.c{width:min(100%,560px);background:rgba(255,255,255,.94);border:1px solid #dfeae4;border-radius:28px;padding:38px;box-shadow:0 24px 65px rgba(10,70,48,.11);text-align:center}.i{width:74px;height:74px;margin:auto;border-radius:22px;background:#edf8f1;display:grid;place-items:center;overflow:hidden}.i img{width:58px;height:58px;object-fit:contain}.i span{font-size:28px;font-weight:900;color:#087d48}.c h1{font-size:29px;margin:20px 0 8px}.c p{color:#61776e;line-height:1.7;font-size:14px}.c a{display:inline-flex;margin-top:14px;padding:13px 20px;border-radius:13px;background:#07984e;color:white;text-decoration:none;font-weight:850}.r{display:block;margin-top:14px;color:#8a9b94;font-size:11px}@media(max-width:520px){.c{padding:28px 20px;border-radius:22px}.c h1{font-size:25px}}</style></head><body><main class="c"><div class="i">'.$logoHtml.'</div><h1>Your secure form needs a refresh</h1><p>For your protection, this form token is no longer valid. No information was saved. Reload the page to receive a fresh secure form and submit again.</p><a href="javascript:location.reload()">Reload secure page</a><small class="r">Reference: '.e($ref).'</small></main></body></html>';
        exit;
    }

    private static function browserSecret(int $ttl): string
    {
        $cookie = (string)($_COOKIE[self::COOKIE] ?? '');
        if (preg_match('/^[a-f0-9]{64}$/', $cookie)) return $cookie;

        $cookie = bin2hex(random_bytes(32));
        if (!headers_sent()) {
            setcookie(self::COOKIE, $cookie, [
                'expires' => time() + max(86400, $ttl * 6),
                'path' => '/',
                'secure' => Security::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        $_COOKIE[self::COOKIE] = $cookie;
        return $cookie;
    }
}
