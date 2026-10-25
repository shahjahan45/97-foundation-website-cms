<?php
namespace App\Controllers\Admin;
use App\Core\Controller;use App\Core\Auth;use App\Core\Database;use App\Core\Session;use App\Core\Upload;use App\Core\Audit;
final class ProfileAdminController extends Controller
{
 public function index():void{$u=Auth::requireAdmin();$full=Database::one('SELECT * FROM users WHERE id=?',[(int)$u['id']]);$social=Database::all('SELECT platform,label,url,is_active FROM social_links ORDER BY sort_order,id LIMIT 8');$this->view('admin/profile',['user'=>$u,'profile'=>$full,'socialLinks'=>$social,'adminTitle'=>'My Profile'],'admin');}
 public function accountSecurity():void{$u=Auth::requireAdmin();$full=Database::one('SELECT * FROM users WHERE id=?',[(int)$u['id']]);$this->view('admin/account-security',['user'=>$u,'profile'=>$full,'adminTitle'=>'Account Security'],'admin');}
 public function save():void
 {
  $this->requirePost();$u=Auth::requireAdmin();$name=substr(trim($_POST['name']??''),0,120);if(strlen($name)<2){Session::flash('error','Name is required.');redirect('admin/profile');}
  $photo=$u['profile_photo']??null;try{if(isset($_FILES['profile_photo'])&&($_FILES['profile_photo']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$photo=Upload::publicImage($_FILES['profile_photo'],'profiles');}catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect('admin/profile');}
  Database::exec('UPDATE users SET name=?,profile_photo=? WHERE id=?',[$name,$photo,(int)$u['id']]);Audit::write((int)$u['id'],'PROFILE_UPDATED','admin',(int)$u['id'],'Admin profile updated');Session::flash('success','Profile updated.');redirect('admin/profile');
 }
 public function account():void
 {
  $this->requirePost();$u=Auth::requireAdmin();$current=(string)($_POST['current_password']??'');$email=strtolower(trim((string)($_POST['email']??'')));$new=(string)($_POST['new_password']??'');$confirm=(string)($_POST['password_confirmation']??'');
  $row=Database::one('SELECT email,password_hash FROM users WHERE id=? LIMIT 1',[(int)$u['id']]);
  if(!$row||!password_verify($current,(string)$row['password_hash'])){Session::flash('error','Your current password is required to change account credentials.');redirect('admin/account-security');}
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190){Session::flash('error','Enter a valid administrator email address.');redirect('admin/account-security');}
  $duplicate=Database::one('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1',[$email,(int)$u['id']]);if($duplicate){Session::flash('error','That email address is already used by another account.');redirect('admin/account-security');}
  if($new!==''&&(strlen($new)<12||$new!==$confirm)){Session::flash('error','The new password must be at least 12 characters and both password fields must match.');redirect('admin/account-security');}
  if($new!=='')Database::exec('UPDATE users SET email=?,password_hash=? WHERE id=?',[$email,password_hash($new,PASSWORD_ARGON2ID),(int)$u['id']]);else Database::exec('UPDATE users SET email=? WHERE id=?',[$email,(int)$u['id']]);
  Session::regenerate();Audit::write((int)$u['id'],'ACCOUNT_CREDENTIALS_UPDATED','admin',(int)$u['id'],$new!==''?'Administrator email/password updated':'Administrator email updated');Session::flash('success',$new!==''?'Administrator email and password updated securely.':'Administrator email updated securely.');redirect('admin/account-security');
 }
 public function notifications():void{$u=Auth::requireAdmin();$total=(int)(Database::one('SELECT COUNT(*) c FROM notifications WHERE user_id=?',[(int)$u['id']])['c']??0);$pagination=admin_pager($total,30);$rows=Database::all('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT '.(int)$pagination['per_page'].' OFFSET '.(int)$pagination['offset'],[(int)$u['id']]);$this->view('admin/notifications',['user'=>$u,'notifications'=>$rows,'pagination'=>$pagination,'adminTitle'=>'Notifications'],'admin');}
}
