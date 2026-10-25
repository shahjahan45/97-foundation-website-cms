<?php
namespace App\Core;

final class RateLimiter
{
    private static function key(string $email): string { return hash('sha256', strtolower($email).'|'.Auth::ip()); }
    public static function tooManyAttempts(string $email): bool
    {
        try {
            $k=self::key($email); $window=(int)App::config('security.login_lock_minutes',15);
            $row=Database::one('SELECT attempts,last_attempt_at FROM login_attempts WHERE attempt_key=?',[$k]);
            if(!$row) return false;
            if(strtotime($row['last_attempt_at']) < time()-($window*60)){ self::clear($email); return false; }
            return (int)$row['attempts'] >= (int)App::config('security.login_attempts',5);
        } catch (\Throwable $e) {
            error_log('Rate limiter read failure: '.$e->getMessage());
            return false;
        }
    }
    public static function hit(string $email): void
    {
        try {
            $k=self::key($email);
            Database::exec('INSERT INTO login_attempts (attempt_key,attempts,last_attempt_at) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE attempts=attempts+1,last_attempt_at=NOW()',[$k]);
        } catch (\Throwable $e) { error_log('Rate limiter write failure: '.$e->getMessage()); }
    }
    public static function clear(string $email): void
    {
        try { Database::exec('DELETE FROM login_attempts WHERE attempt_key=?',[self::key($email)]); }
        catch (\Throwable $e) { error_log('Rate limiter clear failure: '.$e->getMessage()); }
    }
}
