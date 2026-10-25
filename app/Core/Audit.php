<?php
namespace App\Core;

final class Audit
{
    public static function write(?int $userId,string $action,string $module,?int $recordId,string $description): void
    {
        try { Database::exec('INSERT INTO audit_logs(user_id,action,module,record_id,description,ip_address,user_agent,created_at) VALUES(?,?,?,?,?,?,?,NOW())',[$userId,$action,$module,$recordId,substr($description,0,500),Auth::ip(),substr($_SERVER['HTTP_USER_AGENT']??'',0,500)]); } catch(\Throwable $e) { error_log('Audit failure: '.$e->getMessage()); }
    }
}
