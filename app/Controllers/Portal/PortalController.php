<?php
namespace App\Controllers\Portal;
use App\Core\Controller;use App\Core\Auth;use App\Core\Database;use App\Core\Session;use App\Core\Upload;use App\Core\Notification;use App\Core\Site;use App\Core\Audit;
final class PortalController extends Controller
{
    private function base(array $u):array
    {
        $vol=$u['role']==='volunteer'?Database::one('SELECT * FROM volunteers WHERE user_id=? LIMIT 1',[(int)$u['id']]):null;
        $member=$u['role']==='member'?Database::one('SELECT * FROM member_profiles WHERE user_id=? LIMIT 1',[(int)$u['id']]):null;
        $notifs=Database::all('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 12',[(int)$u['id']]);
        $messages=Database::all('SELECT m.*,s.name sender_name,r.name recipient_name FROM messages m JOIN users s ON s.id=m.sender_id JOIN users r ON r.id=m.recipient_id WHERE m.sender_id=? OR m.recipient_id=? ORDER BY m.id DESC LIMIT 30',[(int)$u['id'],(int)$u['id']]);
        // Dashboard content is helpful but non-critical. Keep the member/volunteer portal available
        // even when an optional content module is temporarily unavailable during deployment.
        $events=[];$causes=[];$announcements=[];
        try {
            // The events table stores its image in `image_path` (not `featured_image`).
            $events=Database::all('SELECT id,title,event_date,location,image_path AS featured_image,slug FROM events WHERE is_active=1 AND status="published" AND event_date>=CURDATE() ORDER BY event_date,id LIMIT 3');
        } catch (\Throwable) {}
        try {
            $causes=Database::all('SELECT id,name,description,image_path,slug FROM campaign_categories WHERE is_active=1 ORDER BY sort_order,id LIMIT 3');
        } catch (\Throwable) {}
        try {
            $announcements=Database::all('SELECT id,title,slug,excerpt,published_at FROM posts WHERE status="published" AND published_at<=NOW() ORDER BY is_featured DESC,published_at DESC LIMIT 3');
        } catch (\Throwable) {}
        return ['user'=>$u,'volunteer'=>$vol,'member'=>$member,'notifications'=>$notifs,'messages'=>$messages,'events'=>$events,'causes'=>$causes,'announcements'=>$announcements,'settings'=>Site::settings()];
    }
    public function index():void{$u=Auth::requirePortal();$d=$this->base($u);$d['adminTitle']='My Portal';$this->view('portal/dashboard',$d,'portal');}
    public function profile():void{$u=Auth::requirePortal();$d=$this->base($u);$d['adminTitle']='My Profile';$this->view('portal/profile',$d,'portal');}
    public function saveProfile():void
    {
        $this->requirePost();$u=Auth::requirePortal();$name=substr(trim($_POST['name']??''),0,120);if(strlen($name)<2){Session::flash('error','Please enter your name.');redirect('portal/profile');}
        $photo=$u['profile_photo']??null;try{if(isset($_FILES['profile_photo'])&&($_FILES['profile_photo']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$photo=$u['role']==='member'?Upload::publicMemberPhoto($_FILES['profile_photo']):Upload::publicImage($_FILES['profile_photo'],'profiles');}catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect('portal/profile');}
        Database::transaction(function()use($u,$name,$photo){Database::exec('UPDATE users SET name=?,profile_photo=? WHERE id=?',[$name,$photo,(int)$u['id']]);if($u['role']==='volunteer')Database::exec('UPDATE volunteers SET full_name=?,mobile=?,address=?,city=?,country=?,skills=?,occupation=?,interests=?,profile_photo=? WHERE user_id=?',[$name,substr(trim($_POST['mobile']??''),0,60),substr(trim($_POST['address']??''),0,500),substr(trim($_POST['city']??''),0,120),substr(trim($_POST['country']??''),0,120),substr(trim($_POST['skills']??''),0,2000),substr(trim($_POST['occupation']??''),0,160),substr(trim($_POST['interests']??''),0,2000),$photo,(int)$u['id']]);else Database::exec('UPDATE member_profiles SET mobile=?,address=? WHERE user_id=?',[substr(trim($_POST['mobile']??''),0,60),substr(trim($_POST['address']??''),0,500),(int)$u['id']]);});
        Audit::write((int)$u['id'],'PROFILE_UPDATED','portal',(int)$u['id'],'Portal profile updated');Session::flash('success','Profile updated.');redirect('portal/profile');
    }
    public function messages():void{$u=Auth::requirePortal();Database::exec('UPDATE messages SET read_at=NOW() WHERE recipient_id=? AND read_at IS NULL',[(int)$u['id']]);$d=$this->base($u);$d['adminTitle']='Messages';$this->view('portal/messages',$d,'portal');}
    public function sendMessage():void{$this->requirePost();$u=Auth::requirePortal();$body=trim($_POST['body']??'');if(strlen($body)<2){Session::flash('error','Write a message before sending.');redirect('portal/messages');}$admin=Database::one('SELECT id FROM users WHERE role IN("super_admin","administrator","volunteer_manager","support_agent") AND status="active" ORDER BY FIELD(role,"support_agent","volunteer_manager","administrator","super_admin"),id LIMIT 1');if(!$admin){Session::flash('error','No administrator is currently available.');redirect('portal/messages');}Database::insert('INSERT INTO messages(sender_id,recipient_id,subject,body) VALUES(?,?,?,?)',[(int)$u['id'],(int)$admin['id'],substr(trim($_POST['subject']??'Portal message'),0,180),substr($body,0,5000)]);Notification::send((int)$admin['id'],'New portal message',$u['name'].' sent a portal message.','message','admin/messages');Session::flash('success','Message sent to the Foundation team.');redirect('portal/messages');}
    public function notifications():void{$u=Auth::requirePortal();$d=$this->base($u);$total=(int)(Database::one('SELECT COUNT(*) c FROM notifications WHERE user_id=?',[(int)$u['id']])['c']??0);$pagination=admin_pager($total,30);$d['notifications']=Database::all('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT '.(int)$pagination['per_page'].' OFFSET '.(int)$pagination['offset'],[(int)$u['id']]);$d['pagination']=$pagination;$d['adminTitle']='Notifications';$this->view('portal/notifications',$d,'portal');}
    public function idCard():void{$u=Auth::requirePortal();$d=$this->base($u);$d['adminTitle']=$u['role']==='volunteer'?'My Volunteer ID':'My Member ID';$this->view('portal/id-card',$d,'portal');}
    public function password():void{$u=Auth::requirePortal();$d=$this->base($u);$d['adminTitle']='Change Password';$this->view('portal/password',$d,'portal');}
    public function changePassword():void{$this->requirePost();$u=Auth::requirePortal();$old=(string)($_POST['current_password']??'');$new=(string)($_POST['new_password']??'');$confirm=(string)($_POST['password_confirmation']??'');$row=Database::one('SELECT password_hash FROM users WHERE id=?',[(int)$u['id']]);$len=(function_exists('mb_strlen') ? mb_strlen($new,'UTF-8') : strlen($new));if(!$row||!password_verify($old,$row['password_hash'])||$len<8||$len>12||$new!==$confirm){Session::flash('error','Check your current password. The new password must be 8–12 characters and both entries must match.');redirect('portal/password');}try{Database::exec('UPDATE users SET password_hash=? WHERE id=?',[password_hash($new,PASSWORD_ARGON2ID),(int)$u['id']]);Audit::write((int)$u['id'],'PASSWORD_CHANGED','portal',(int)$u['id'],'Portal password changed');Session::regenerate();Session::flash('success','Password changed successfully.');}catch(\Throwable $e){$ref=strtoupper(substr(bin2hex(random_bytes(8)),0,10));error_log('Portal password change failure ['.$ref.']: '.$e->getMessage());Session::flash('error','We could not update your password. Please try again. Reference '.$ref.'.');}redirect('portal/password');}
}
