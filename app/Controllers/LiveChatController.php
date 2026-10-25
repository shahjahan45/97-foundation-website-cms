<?php
namespace App\Controllers;
use App\Core\Controller;use App\Core\Database;use App\Core\Notification;use App\Core\RateLimiter;
final class LiveChatController extends Controller
{
    private function json(array $data,int $code=200):never{http_response_code($code);header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
    public function start():void
    {
        $this->requirePost();
        $name=substr(trim($_POST['name']??''),0,120);$email=strtolower(trim($_POST['email']??''));$phone=substr(trim($_POST['phone']??''),0,60);$category=substr(trim($_POST['category']??'general'),0,40);$message=substr(trim($_POST['message']??''),0,3000);
        if(strlen($name)<2||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($message)<2)$this->json(['ok'=>false,'message'=>'Please enter your name, a valid email address and a message.'],422);
        if(!in_array($category,['general','donation','volunteer','campaign','help','other'],true))$category='general';
        $rateKey='live-chat:'.($_SERVER['REMOTE_ADDR']??'0.0.0.0');
        if(RateLimiter::tooManyAttempts($rateKey))$this->json(['ok'=>false,'message'=>'Too many chat attempts. Please wait a few minutes and try again.'],429);
        try{
            $result=Database::transaction(function()use($name,$email,$phone,$category,$message){$token=bin2hex(random_bytes(24));$id=Database::insert('INSERT INTO chat_conversations(public_token,visitor_name,visitor_email,visitor_phone,category,status,last_message_at) VALUES(?,?,?,?,?,"open",NOW())',[$token,$name,$email,$phone,$category]);Database::insert('INSERT INTO chat_messages(conversation_id,sender_type,sender_name,message) VALUES(?,"visitor",?,?)',[$id,$name,$message]);return ['id'=>$id,'token'=>$token];});
            RateLimiter::clear($rateKey);
            try{$admins=Database::all('SELECT id FROM users WHERE role IN("super_admin","administrator","support_agent") AND status="active" LIMIT 25');foreach($admins as $a)Notification::send((int)$a['id'],'New live chat',$name.' started a website chat.','message','admin/live-chat?chat='.$result['id']);}catch(\Throwable){}
            $this->json(['ok'=>true,'token'=>$result['token'],'status'=>'open','messages'=>[['sender_type'=>'visitor','sender_name'=>$name,'message'=>$message,'created_at'=>date('Y-m-d H:i:s')]]]);
        }catch(\Throwable $e){error_log('97 Foundation live chat start failed: '.$e->getMessage());RateLimiter::hit($rateKey);$this->json(['ok'=>false,'message'=>'Live chat is temporarily unavailable. Please refresh the page and try again.'],500);}
    }
    public function poll(string $token):void
    {
        if(!preg_match('/^[a-f0-9]{48}$/',$token))$this->json(['ok'=>false,'message'=>'Chat not found.'],404);
        try{$c=Database::one('SELECT id,status FROM chat_conversations WHERE public_token=? LIMIT 1',[$token]);if(!$c)$this->json(['ok'=>false,'message'=>'Chat not found.'],404);try{Database::exec('UPDATE chat_conversations SET visitor_last_seen_at=NOW() WHERE id=?',[(int)$c['id']]);}catch(\Throwable){}$rows=Database::all('SELECT sender_type,sender_name,message,created_at FROM chat_messages WHERE conversation_id=? ORDER BY id ASC LIMIT 300',[(int)$c['id']]);$this->json(['ok'=>true,'status'=>$c['status'],'messages'=>$rows]);}catch(\Throwable $e){error_log('97 Foundation live chat poll failed: '.$e->getMessage());$this->json(['ok'=>false,'message'=>'Unable to load this chat right now.'],500);}
    }
    public function send(string $token):void
    {
        $this->requirePost();if(!preg_match('/^[a-f0-9]{48}$/',$token))$this->json(['ok'=>false,'message'=>'Chat not found.'],404);$message=substr(trim($_POST['message']??''),0,3000);if(strlen($message)<1)$this->json(['ok'=>false,'message'=>'Message is required.'],422);
        try{$c=Database::one('SELECT * FROM chat_conversations WHERE public_token=? AND status<>"closed" LIMIT 1',[$token]);if(!$c)$this->json(['ok'=>false,'message'=>'This chat has been closed.'],409);Database::transaction(function()use($c,$message){Database::insert('INSERT INTO chat_messages(conversation_id,sender_type,sender_name,message) VALUES(?,"visitor",?,?)',[(int)$c['id'],$c['visitor_name'],$message]);Database::exec('UPDATE chat_conversations SET last_message_at=NOW(),status=IF(status="waiting","open",status) WHERE id=?',[(int)$c['id']]);try{Database::exec('UPDATE chat_conversations SET visitor_last_seen_at=NOW() WHERE id=?',[(int)$c['id']]);}catch(\Throwable){}});$this->json(['ok'=>true]);}catch(\Throwable $e){error_log('97 Foundation live chat send failed: '.$e->getMessage());$this->json(['ok'=>false,'message'=>'Unable to send your message right now. Please try again.'],500);}
    }
}
