<?php
namespace App\Core;

final class Auth
{
    public static function user(): ?array
    {
        $id = Session::get('auth.user_id'); if (!$id) return null;
        return Database::one('SELECT id,name,email,username,role,status,two_factor_enabled,profile_photo,last_login_at FROM users WHERE id=? AND status="active" LIMIT 1', [(int)$id]);
    }
    public static function check(): bool { return self::user() !== null; }
    public static function verifyPrimary(string $identifier, string $password): ?array
    {
        $identifier = strtolower(trim($identifier));
        if (RateLimiter::tooManyAttempts($identifier)) { Session::flash('error','Too many login attempts. Please try again later.'); return null; }
        $u = Database::one('SELECT * FROM users WHERE email=? OR username=? LIMIT 1', [$identifier,$identifier]);
        if (!$u || $u['status'] !== 'active' || !password_verify($password, $u['password_hash'])) {
            RateLimiter::hit($identifier); Audit::write(null,'LOGIN_FAILED','auth',null,'Failed login for '.$identifier); return null;
        }
        return $u;
    }
    public static function completeLogin(array $u, ?string $identifier = null): void
    {
        if($identifier!==null && $identifier!=='') RateLimiter::clear(strtolower(trim($identifier)));
        Session::regenerate(); Session::put('auth.user_id',(int)$u['id']);
        Session::forget('auth.2fa_pending_user_id'); Session::forget('auth.2fa_pending_started');
        try { Database::exec('UPDATE users SET last_login_at=NOW(), last_login_ip=? WHERE id=?', [self::ip(), (int)$u['id']]); }
        catch (\Throwable $e) { error_log('Login metadata update failure: '.$e->getMessage()); }
        Audit::write((int)$u['id'],'LOGIN','auth',(int)$u['id'],'Successful login');
    }
    public static function attempt(string $email, string $password, ?string $otp = null): bool
    {
        $identifier=strtolower(trim($email));$u=self::verifyPrimary($identifier,$password);if(!$u)return false;
        if ((int)$u['two_factor_enabled'] === 1) {
            if (!$otp || !Totp::verify((string)$u['two_factor_secret'], $otp)) { RateLimiter::hit($identifier); Session::flash('error','A valid 2FA code is required.'); return false; }
        }
        self::completeLogin($u,$identifier);return true;
    }
    public static function logout(): void
    {
        $u=self::user(); if($u) Audit::write((int)$u['id'],'LOGOUT','auth',(int)$u['id'],'Logout');
        $_SESSION=[]; if(ini_get('session.use_cookies')){ $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain']??'',(bool)$p['secure'],(bool)$p['httponly']); } session_destroy();
    }
    public static function requireAdmin(): array
    {
        $u=self::user();
        if(!$u || !in_array($u['role'],['super_admin','administrator','finance_manager','volunteer_manager','content_manager','support_agent','event_manager'],true)){ redirect('admin/login'); }
        return $u;
    }
    public static function requirePortal(): array
    {
        $u=self::user(); if(!$u || !in_array($u['role'],['volunteer','member'],true)) redirect('login'); return $u;
    }
    public static function can(string $permission): bool
    {
        $u=self::user(); if(!$u) return false; if($u['role']==='super_admin') return true;
        $map=[
            'administrator'=>['view_donations','approve_donations','manage_campaigns','manage_volunteers','approve_volunteers','manage_content','manage_settings','manage_gallery','manage_messages','view_audit_logs','view_online_payments','manage_payment_gateway','manage_scholarships'],
            'finance_manager'=>['view_donations','approve_donations','manage_campaigns','view_online_payments'],
            'volunteer_manager'=>['manage_volunteers','approve_volunteers','manage_messages'],
            'content_manager'=>['manage_content','manage_gallery'],
            'support_agent'=>['manage_messages'], 'event_manager'=>['manage_content']
        ];
        return in_array($permission,$map[$u['role']]??[],true);
    }
    public static function enforce(string $permission): void { if(!self::can($permission)){ http_response_code(403); exit('Forbidden'); } }
    public static function ip(): string { return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',0,45); }
}
