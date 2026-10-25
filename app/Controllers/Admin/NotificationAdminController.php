<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

final class NotificationAdminController extends Controller
{
    public function feed(): void
    {
        $u=Auth::requireAdmin();
        $this->jsonFeed((int)$u['id']);
    }

    public function read(): void
    {
        $this->requirePost();
        $u=Auth::requireAdmin();
        $id=max(0,(int)($_POST['id']??0));
        if($id>0){
            Database::exec('UPDATE notifications SET read_at=COALESCE(read_at,NOW()) WHERE id=? AND user_id=?',[$id,(int)$u['id']]);
        }
        $this->json(['ok'=>true,'unread_count'=>$this->unread((int)$u['id'])]);
    }

    public function readAll(): void
    {
        $this->requirePost();
        $u=Auth::requireAdmin();
        Database::exec('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL',[(int)$u['id']]);
        $this->json(['ok'=>true,'unread_count'=>0]);
    }

    private function jsonFeed(int $userId): void
    {
        $page=max(1,(int)($_GET['page']??1));
        $perPage=25;
        $total=(int)(Database::one('SELECT COUNT(*) c FROM notifications WHERE user_id=?',[$userId])['c']??0);
        $pages=max(1,(int)ceil($total/$perPage));
        $page=min($page,$pages);
        $offset=($page-1)*$perPage;
        $rows=Database::all('SELECT id,title,message,type,link_url,read_at,created_at FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT '.$perPage.' OFFSET '.$offset,[$userId]);
        $items=array_map(fn(array $n)=>$this->serialize($n),$rows);
        $this->json([
            'ok'=>true,
            'items'=>$items,
            'unread_count'=>$this->unread($userId),
            'page'=>$page,
            'pages'=>$pages,
            'total'=>$total,
            'has_more'=>$page<$pages,
        ]);
    }

    private function unread(int $userId): int
    {
        return (int)(Database::one('SELECT COUNT(*) c FROM notifications WHERE user_id=? AND read_at IS NULL',[$userId])['c']??0);
    }

    private function serialize(array $n): array
    {
        $created=(string)($n['created_at']??'');
        return [
            'id'=>(int)$n['id'],
            'title'=>(string)$n['title'],
            'message'=>(string)$n['message'],
            'type'=>(string)($n['type']?:'info'),
            'link_url'=>$n['link_url']?(preg_match('~^https?://~i',(string)$n['link_url'])?(string)$n['link_url']:url((string)$n['link_url'])):null,
            'read_at'=>$n['read_at'],
            'created_at'=>$created,
            'created_label'=>$created?date('d M Y · g:i A',strtotime($created)):'',
        ];
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        echo json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }
}
