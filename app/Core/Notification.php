<?php
namespace App\Core;
final class Notification
{
    public static function send(int $userId,string $title,string $message,string $type='info',?string $link=null): void
    {
        try {
            Database::insert('INSERT INTO notifications(user_id,title,message,type,link_url,created_at) VALUES(?,?,?,?,?,NOW())',[$userId,substr($title,0,180),substr($message,0,1000),substr($type,0,30),$link?substr($link,0,255):null]);
        } catch (\Throwable $e) { error_log('Notification send failure: '.$e->getMessage()); }
    }
    public static function admins(string $title,string $message,string $type='info',?string $link=null): void
    {
        try {
            $roles=match($type){
                'donation'=>['super_admin','administrator','finance_manager'],
                'volunteer'=>['super_admin','administrator','volunteer_manager'],
                'inquiry'=>['super_admin','administrator','support_agent'],
                default=>['super_admin','administrator']
            };
            $marks=implode(',',array_fill(0,count($roles),'?'));
            $rows=Database::all('SELECT id FROM users WHERE role IN('.$marks.') AND status="active"',$roles);
            foreach($rows as $r) self::send((int)$r['id'],$title,$message,$type,$link);
        } catch (\Throwable $e) { error_log('Admin notification failure: '.$e->getMessage()); }
    }
}
