<?php
namespace App\Controllers\Admin;
use App\Core\Controller;use App\Core\Auth;use App\Core\Database;use App\Core\Session;use App\Core\Audit;use App\Core\Notification;use App\Core\Upload;
final class VolunteerAdminController extends Controller
{
 public function index():void{$u=Auth::requireAdmin();Auth::enforce('manage_volunteers');$status=trim($_GET['status']??'');$q=trim($_GET['q']??'');$where='1=1';$params=[];if(in_array($status,['pending','approved','active','suspended','rejected','inactive'],true)){$where.=' AND v.status=?';$params[]=$status;}if($q!==''){$where.=' AND (v.full_name LIKE ? OR v.email LIKE ? OR v.mobile LIKE ? OR v.volunteer_id LIKE ?)';$like='%'.$q.'%';array_push($params,$like,$like,$like,$like);}$total=(int)(Database::one('SELECT COUNT(*) c FROM volunteers v WHERE '.$where,$params)['c']??0);$pagination=admin_pager($total,20);$rows=Database::all('SELECT v.*,u.status user_status FROM volunteers v LEFT JOIN users u ON u.id=v.user_id WHERE '.$where.' ORDER BY v.id DESC LIMIT '.(int)$pagination['per_page'].' OFFSET '.(int)$pagination['offset'],$params);$this->view('admin/volunteers',['user'=>$u,'volunteers'=>$rows,'pagination'=>$pagination,'status'=>$status,'q'=>$q,'adminTitle'=>'Volunteer Management'],'admin');}
 public function applications():void
 {
  $u=Auth::requireAdmin();Auth::enforce('manage_volunteers');$q=trim($_GET['q']??'');$params=[];$where='v.status="pending"';
  if($q!==''){$like='%'.$q.'%';$where.=' AND (v.full_name LIKE ? OR v.email LIKE ? OR v.mobile LIKE ?)';array_push($params,$like,$like,$like);}
  $total=(int)(Database::one('SELECT COUNT(*) c FROM volunteers v WHERE '.$where,$params)['c']??0);$pagination=admin_pager($total,20);
  $rows=Database::all('SELECT v.*,u.status user_status FROM volunteers v LEFT JOIN users u ON u.id=v.user_id WHERE '.$where.' ORDER BY v.id ASC LIMIT '.(int)$pagination['per_page'].' OFFSET '.(int)$pagination['offset'],$params);
  $this->view('admin/volunteer-applications',['user'=>$u,'volunteers'=>$rows,'pagination'=>$pagination,'q'=>$q,'adminTitle'=>'Registration Management'],'admin');
 }
 public function save():void
 {
  $this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_volunteers');$id=(int)($_POST['id']??0);$v=Database::one('SELECT * FROM volunteers WHERE id=?',[$id]);if(!$v){Session::flash('error','Volunteer not found.');redirect('admin/volunteers');}
  $name=substr(trim($_POST['full_name']??''),0,160);$email=strtolower(trim($_POST['email']??''));$mobile=substr(trim($_POST['mobile']??''),0,60);if(strlen($name)<2||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($mobile)<3){Session::flash('error','Name, valid email and mobile are required.');redirect('admin/volunteers');}
  $photo=$v['profile_photo'];try{if(isset($_FILES['profile_photo'])&&($_FILES['profile_photo']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$photo=Upload::publicImage($_FILES['profile_photo'],'profiles');}catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect('admin/volunteers');}
  try{Database::transaction(function()use($id,$v,$name,$email,$mobile,$photo,$u){Database::exec('UPDATE volunteers SET full_name=?,email=?,mobile=?,gender=?,date_of_birth=?,address=?,city=?,country=?,skills=?,occupation=?,interests=?,emergency_contact=?,profile_photo=? WHERE id=?',[$name,$email,$mobile,substr(trim($_POST['gender']??$v['gender']),0,40),($_POST['date_of_birth']??'')?:null,substr(trim($_POST['address']??''),0,500),substr(trim($_POST['city']??''),0,120),substr(trim($_POST['country']??''),0,120),substr(trim($_POST['skills']??''),0,4000),substr(trim($_POST['occupation']??''),0,160),substr(trim($_POST['interests']??''),0,4000),substr(trim($_POST['emergency_contact']??''),0,220),$photo,$id]);if(!empty($v['user_id']))Database::exec('UPDATE users SET name=?,email=?,profile_photo=? WHERE id=? AND role="volunteer"',[$name,$email,$photo,(int)$v['user_id']]);Audit::write((int)$u['id'],'VOLUNTEER_UPDATED','volunteers',$id,'Volunteer profile updated by administrator');});}catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect('admin/volunteers');}
  Session::flash('success','Volunteer profile updated.');redirect('admin/volunteers');
 }
 public function delete():void
 {
  $this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_volunteers');if($u['role']!=='super_admin'){http_response_code(403);exit('Forbidden');}$id=(int)($_POST['id']??0);$v=Database::one('SELECT user_id,full_name FROM volunteers WHERE id=?',[$id]);if(!$v){Session::flash('error','Volunteer not found.');redirect('admin/volunteers');}
  Database::transaction(function()use($id,$v,$u){$uid=(int)($v['user_id']??0);Database::exec('DELETE FROM volunteers WHERE id=?',[$id]);if($uid)Database::exec('DELETE FROM users WHERE id=? AND role="volunteer"',[$uid]);Audit::write((int)$u['id'],'DELETE','volunteers',$id,'Volunteer record deleted by Super Administrator');});Session::flash('success','Volunteer record deleted.');redirect('admin/volunteers');
 }
 public function status():void
 {
  $this->requirePost();$u=Auth::requireAdmin();Auth::enforce('approve_volunteers');$id=(int)($_POST['id']??0);$status=$_POST['status']??'';
  if(!in_array($status,['approved','active','suspended','rejected','inactive'],true)){Session::flash('error','Invalid volunteer status.');redirect('admin/volunteers');}
  $portalPassword=(string)($_POST['portal_password']??'');$returnTo=($_POST['return_to']??'')==='applications'?'admin/volunteers/applications':'admin/volunteers';
  try{Database::transaction(function()use($id,$status,$u,$portalPassword){
   $v=Database::one('SELECT * FROM volunteers WHERE id=? FOR UPDATE',[$id]);if(!$v)throw new \RuntimeException('Volunteer not found');
   $userId=(int)($v['user_id']??0);
   if(in_array($status,['approved','active'],true)&&!$userId){
      $existing=Database::one('SELECT id,role FROM users WHERE email=? LIMIT 1',[$v['email']]);
      if($existing){if($existing['role']!=='volunteer')throw new \RuntimeException('This email already belongs to a non-volunteer portal account. Resolve the duplicate email before approval.');$userId=(int)$existing['id'];}
      else{if(strlen($portalPassword)<10)throw new \RuntimeException('Legacy application: set a portal password of at least 10 characters before approving.');$userId=Database::insert('INSERT INTO users(name,email,password_hash,role,status,profile_photo) VALUES(?,?,?,"volunteer","active",?)',[$v['full_name'],$v['email'],password_hash($portalPassword,PASSWORD_ARGON2ID),$v['profile_photo']]);}
      Database::exec('UPDATE volunteers SET user_id=? WHERE id=?',[$userId,$id]);
   }
   $volId=$v['volunteer_id'];$token=$v['verification_token']?:bin2hex(random_bytes(32));
   if(in_array($status,['approved','active'],true)&&!$volId){$year=date('Y');$volId=sprintf('97V-%s-%06d',$year,$id);}
   Database::exec('UPDATE volunteers SET status=?,volunteer_id=COALESCE(volunteer_id,?),verification_token=?,approved_by=?,approved_at=IF(? IN ("approved","active"),NOW(),approved_at),rejection_reason=? WHERE id=?',[$status,$volId,$token,(int)$u['id'],$status,$status==='rejected'?substr(trim($_POST['note']??''),0,500):null,$id]);
   if($userId){
      $userStatus=in_array($status,['approved','active'],true)?'active':($status==='suspended'?'suspended':'inactive');
      Database::exec('UPDATE users SET status=? WHERE id=?',[$userStatus,$userId]);
      $title=in_array($status,['approved','active'],true)?'Volunteer application approved':'Volunteer status updated';
      $message=in_array($status,['approved','active'],true)?'Your volunteer application is approved. You can now sign in to your portal and access your Volunteer ID and QR verification.':'Your volunteer status is now '.str_replace('_',' ',$status).'.';
      Notification::send($userId,$title,$message,in_array($status,['approved','active'],true)?'success':'info','portal');
   }
   Audit::write((int)$u['id'],'VOLUNTEER_'.strtoupper($status),'volunteers',$id,'Volunteer status changed to '.$status);
  });}catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect($returnTo);}
  Session::flash('success','Volunteer status and portal access updated.');redirect($returnTo);
 }
}
