<?php
namespace App\Controllers\Admin;
use App\Core\Controller;use App\Core\Auth;use App\Core\Database;use App\Core\Session;use App\Core\Notification;use App\Core\Audit;
final class MessageAdminController extends Controller
{
 private const ADMIN_ROLES=['super_admin','administrator','finance_manager','volunteer_manager','content_manager','support_agent','event_manager'];
 public function index():void
 {
  $u=Auth::requireAdmin();Auth::enforce('manage_messages');
  Database::exec('UPDATE messages SET read_at=NOW() WHERE recipient_id=? AND read_at IS NULL',[(int)$u['id']]);
  $total=(int)(Database::one('SELECT COUNT(*) c FROM messages WHERE sender_id=? OR recipient_id=?',[(int)$u['id'],(int)$u['id']])['c']??0);$pagination=admin_pager($total,30);
  $rows=Database::all('SELECT m.*,s.name sender_name,s.role sender_role,r.name recipient_name,r.role recipient_role FROM messages m JOIN users s ON s.id=m.sender_id JOIN users r ON r.id=m.recipient_id WHERE m.sender_id=? OR m.recipient_id=? ORDER BY m.id DESC LIMIT '.(int)$pagination['per_page'].' OFFSET '.(int)$pagination['offset'],[(int)$u['id'],(int)$u['id']]);
  $people=Database::all('SELECT u.id,u.name,u.email,u.role,v.volunteer_id,m.member_number FROM users u LEFT JOIN volunteers v ON v.user_id=u.id LEFT JOIN member_profiles m ON m.user_id=u.id WHERE u.status="active" AND u.id<>? ORDER BY FIELD(u.role,"member","volunteer","super_admin","administrator","finance_manager","volunteer_manager","content_manager","support_agent","event_manager"),u.name',[(int)$u['id']]);
  $roleCounts=Database::all('SELECT role,COUNT(*) c FROM users WHERE status="active" AND id<>? GROUP BY role',[(int)$u['id']]);$counts=[];foreach($roleCounts as $r)$counts[$r['role']]=(int)$r['c'];
  $audienceCounts=['members'=>$counts['member']??0,'volunteers'=>$counts['volunteer']??0,'administrators'=>0,'portal_users'=>($counts['member']??0)+($counts['volunteer']??0),'all_users'=>array_sum($counts)];foreach(self::ADMIN_ROLES as $role)$audienceCounts['administrators']+=($counts[$role]??0);
  $broadcasts=[];try{$broadcasts=Database::all('SELECT b.*,u.name sender_name FROM message_broadcasts b JOIN users u ON u.id=b.sender_id ORDER BY b.id DESC LIMIT 8');}catch(\Throwable){}
  $this->view('admin/messages',['user'=>$u,'messages'=>$rows,'people'=>$people,'pagination'=>$pagination,'audienceCounts'=>$audienceCounts,'broadcasts'=>$broadcasts,'adminTitle'=>'Portal Messages'],'admin');
 }
 public function send():void
 {
  $this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_messages');
  $audience=(string)($_POST['audience']??'selected');$allowed=['selected','members','volunteers','administrators','portal_users','all_users'];if(!in_array($audience,$allowed,true))$audience='selected';
  $body=substr(trim((string)($_POST['body']??'')),0,5000);$subject=substr(trim((string)($_POST['subject']??'Message from 97 Foundation')),0,180);if(strlen($body)<2){Session::flash('error','Write a message before sending.');redirect('admin/messages');}
  $params=[(int)$u['id']];$where='u.status="active" AND u.id<>?';
  if($audience==='selected'){$ids=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['recipient_ids']??[])))));$ids=array_slice($ids,0,1000);if(!$ids){Session::flash('error','Select at least one recipient.');redirect('admin/messages');}$marks=implode(',',array_fill(0,count($ids),'?'));$where.=' AND u.id IN('.$marks.')';$params=array_merge($params,$ids);}
  elseif($audience==='members')$where.=' AND u.role="member"';
  elseif($audience==='volunteers')$where.=' AND u.role="volunteer"';
  elseif($audience==='portal_users')$where.=' AND u.role IN("member","volunteer")';
  elseif($audience==='administrators')$where.=' AND u.role IN("'.implode('","',self::ADMIN_ROLES).'")';
  $recipients=Database::all('SELECT u.id,u.name,u.role FROM users u WHERE '.$where.' ORDER BY u.id LIMIT 5000',$params);
  if(!$recipients){Session::flash('error','No active recipients matched the selected audience.');redirect('admin/messages');}
  Database::transaction(function($pdo)use($u,$recipients,$subject,$body,$audience){
   $message=$pdo->prepare('INSERT INTO messages(sender_id,recipient_id,subject,body) VALUES(?,?,?,?)');
   $notification=$pdo->prepare('INSERT INTO notifications(user_id,title,message,type,link_url,created_at) VALUES(?,?,?,?,?,NOW())');
   foreach($recipients as $recipient){$message->execute([(int)$u['id'],(int)$recipient['id'],$subject,$body]);$link=in_array($recipient['role'],['member','volunteer'],true)?'portal/messages':'admin/messages';$notification->execute([(int)$recipient['id'],substr($subject,0,180),'You have received a new message from 97 Foundation.','message',$link]);}
   if(count($recipients)>1||$audience!=='selected')try{$stmt=$pdo->prepare('INSERT INTO message_broadcasts(sender_id,audience,subject,body,recipient_count,created_at) VALUES(?,?,?,?,?,NOW())');$stmt->execute([(int)$u['id'],$audience,$subject,$body,count($recipients)]);}catch(\Throwable){}
  });
  $count=count($recipients);Audit::write((int)$u['id'],'MESSAGE_BROADCAST','messages',null,'Sent '.$count.' portal/admin message'.($count===1?'':'s').' to '.$audience);Session::flash('success',$count.' message'.($count===1?'':'s').' sent successfully.');redirect('admin/messages');
 }
}
