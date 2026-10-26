<?php
namespace App\Controllers\Admin;
use App\Core\Controller;use App\Core\Auth;use App\Core\Database;use App\Core\Session;use App\Core\Upload;use App\Core\Audit;
final class EventAdminController extends Controller
{
 public function index():void
 {
  $u=Auth::requireAdmin();if(!Auth::can('manage_content')&&!in_array($u['role'],['event_manager','administrator','super_admin'],true)){http_response_code(403);exit('Forbidden');}
  $when=in_array($_GET['when']??'',['upcoming','past','draft'],true)?$_GET['when']:'';
  $where='1=1';
  if($when==='upcoming')$where='event_date>=CURDATE() AND status<>"draft"';
  elseif($when==='past')$where='event_date<CURDATE()';
  elseif($when==='draft')$where='status="draft"';
  $total=(int)(Database::one('SELECT COUNT(*) c FROM events WHERE '.$where)['c']??0);$pagination=admin_pager($total,12);
  $order=$when==='upcoming'?'event_date ASC,id ASC':'event_date DESC,id DESC';
  $rows=Database::all('SELECT * FROM events WHERE '.$where.' ORDER BY '.$order.' LIMIT '.(int)$pagination['per_page'].' OFFSET '.(int)$pagination['offset']);
  $stats=Database::one('SELECT COUNT(*) total,SUM(CASE WHEN event_date>=CURDATE() AND status<>"draft" THEN 1 ELSE 0 END) upcoming,SUM(CASE WHEN event_date<CURDATE() THEN 1 ELSE 0 END) past,SUM(CASE WHEN status="draft" THEN 1 ELSE 0 END) drafts FROM events')?:[];
  $this->view('admin/events',['user'=>$u,'events'=>$rows,'pagination'=>$pagination,'when'=>$when,'stats'=>$stats,'adminTitle'=>'Event Management'],'admin');
 }
 public function save():void{$this->requirePost();$u=Auth::requireAdmin();if(!Auth::can('manage_content')&&!in_array($u['role'],['event_manager','administrator','super_admin'],true)){http_response_code(403);exit('Forbidden');}$id=(int)($_POST['id']??0);$title=substr(trim($_POST['title']??''),0,180);$date=$_POST['event_date']??'';if(strlen($title)<2||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)){Session::flash('error','Event title and date are required.');redirect('admin/events');}$status=in_array($_POST['status']??'draft',['draft','published','cancelled','completed'],true)?$_POST['status']:'draft';$img=null;try{if(isset($_FILES['image'])&&($_FILES['image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$img=Upload::publicImage($_FILES['image'],'events');}catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect('admin/events');}if($id){$old=Database::one('SELECT image_path FROM events WHERE id=?',[$id]);$img=$img?:($old['image_path']??null);Database::exec('UPDATE events SET title=?,description=?,image_path=?,event_date=?,start_time=?,end_time=?,location=?,status=?,is_active=? WHERE id=?',[$title,substr(trim($_POST['description']??''),0,30000),$img,$date,$_POST['start_time']?:null,$_POST['end_time']?:null,substr(trim($_POST['location']??''),0,180),$status,isset($_POST['is_active'])?1:0,$id]);}else{$slug=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$title),'-')).'-'.substr(bin2hex(random_bytes(3)),0,6);$id=Database::insert('INSERT INTO events(title,slug,description,image_path,event_date,start_time,end_time,location,status,is_active,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?)',[$title,$slug,substr(trim($_POST['description']??''),0,30000),$img,$date,$_POST['start_time']?:null,$_POST['end_time']?:null,substr(trim($_POST['location']??''),0,180),$status,isset($_POST['is_active'])?1:0,(int)$u['id']]);}Audit::write((int)$u['id'],'EVENT_SAVED','events',$id,'Event saved');Session::flash('success','Event saved.');redirect('admin/events');}
 public function delete():void{$this->requirePost();$u=Auth::requireAdmin();if(!Auth::can('manage_content')&&!in_array($u['role'],['event_manager','administrator','super_admin'],true)){http_response_code(403);exit('Forbidden');}$id=(int)($_POST['id']??0);Database::exec('DELETE FROM events WHERE id=?',[$id]);Audit::write((int)$u['id'],'DELETE','events',$id,'Event deleted');Session::flash('success','Event deleted.');redirect('admin/events');}
}
