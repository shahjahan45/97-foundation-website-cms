<?php
namespace App\Controllers\Admin;
use App\Core\Controller;use App\Core\Auth;use App\Core\Database;use App\Core\Session;use App\Core\Upload;use App\Core\Audit;
final class CampaignAdminController extends Controller
{
 public function index():void
 {
  $u=Auth::requireAdmin();Auth::enforce('manage_campaigns');
  $q=trim($_GET['q']??'');$state=$_GET['state']??'';$params=[];$where='1=1';
  if($q!==''){$like='%'.$q.'%';$where.=' AND (c.title LIKE ? OR c.category LIKE ? OR c.location LIKE ?)';array_push($params,$like,$like,$like);}
  if(in_array($state,['active','inactive'],true)){$where.=' AND c.is_active=?';$params[]=$state==='active'?1:0;}
  $total=(int)(Database::one('SELECT COUNT(*) c FROM campaigns c WHERE '.$where,$params)['c']??0);$pagination=admin_pager($total,12);
  $rows=Database::all('SELECT c.*,COALESCE(SUM(CASE WHEN d.status="approved" THEN d.amount ELSE 0 END),0) raised FROM campaigns c LEFT JOIN donations d ON d.campaign_id=c.id WHERE '.$where.' GROUP BY c.id ORDER BY c.id DESC LIMIT '.(int)$pagination['per_page'].' OFFSET '.(int)$pagination['offset'],$params);
  $this->view('admin/campaigns',['user'=>$u,'campaigns'=>$rows,'pagination'=>$pagination,'q'=>$q,'state'=>$state,'adminTitle'=>'Fundraising Campaigns'],'admin');
 }
 public function save():void
 {
  $this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_campaigns');$id=(int)($_POST['id']??0);$title=substr(trim($_POST['title']??''),0,180);$target=max(0,(float)($_POST['target_amount']??0));$status=in_array($_POST['status']??'draft',['draft','upcoming','active','completed','cancelled'],true)?$_POST['status']:'draft';if(strlen($title)<3){Session::flash('error','Campaign title is required.');redirect('admin/campaigns');}
  $img=null;try{if(isset($_FILES['cover_image'])&&($_FILES['cover_image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$img=Upload::publicImage($_FILES['cover_image'],'campaigns');}catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect('admin/campaigns');}
  $active=isset($_POST['is_active'])?1:0;$visible=isset($_POST['is_visible'])?1:0;
  if($id){$old=Database::one('SELECT cover_image,slug FROM campaigns WHERE id=?',[$id]);$img=$img?:($old['cover_image']??null);Database::exec('UPDATE campaigns SET title=?,short_description=?,description=?,target_amount=?,currency="LKR",category=?,location=?,cover_image=?,status=?,is_active=?,is_visible=?,start_date=?,end_date=? WHERE id=?',[$title,substr(trim($_POST['short_description']??''),0,500),substr(trim($_POST['description']??''),0,30000),$target,substr(trim($_POST['category']??''),0,100),substr(trim($_POST['location']??''),0,160),$img,$status,$active,$visible,$_POST['start_date']?:null,$_POST['end_date']?:null,$id]);}
  else{$slug=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$title),'-')).'-'.substr(bin2hex(random_bytes(3)),0,6);$id=Database::insert('INSERT INTO campaigns(title,slug,short_description,description,target_amount,currency,category,location,cover_image,status,is_active,is_visible,start_date,end_date,created_by) VALUES(?,?,?,?,?,"LKR",?,?,?,?,?,?,?,?,?)',[$title,$slug,substr(trim($_POST['short_description']??''),0,500),substr(trim($_POST['description']??''),0,30000),$target,substr(trim($_POST['category']??''),0,100),substr(trim($_POST['location']??''),0,160),$img,$status,$active,$visible,$_POST['start_date']?:null,$_POST['end_date']?:null,(int)$u['id']]);}
  Audit::write((int)$u['id'],'CAMPAIGN_SAVED','campaigns',$id,'Campaign saved');Session::flash('success','Campaign saved successfully.');redirect('admin/campaigns');
 }
 public function toggle():void{$this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_campaigns');$id=(int)($_POST['id']??0);$r=Database::one('SELECT is_active FROM campaigns WHERE id=?',[$id]);if($r)Database::exec('UPDATE campaigns SET is_active=? WHERE id=?',[(int)!((int)$r['is_active']),$id]);Audit::write((int)$u['id'],'CAMPAIGN_ACTIVE_STATE','campaigns',$id,'Campaign active state toggled');Session::flash('success','Campaign active/inactive state updated.');redirect('admin/campaigns');}
 public function visibility():void{$this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_campaigns');$id=(int)($_POST['id']??0);$r=Database::one('SELECT is_visible FROM campaigns WHERE id=?',[$id]);if($r)Database::exec('UPDATE campaigns SET is_visible=? WHERE id=?',[(int)!((int)$r['is_visible']),$id]);Audit::write((int)$u['id'],'CAMPAIGN_VISIBILITY','campaigns',$id,'Campaign public visibility toggled');Session::flash('success','Campaign public visibility updated.');redirect('admin/campaigns');}
 public function delete():void{$this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_campaigns');$id=(int)($_POST['id']??0);$count=Database::one('SELECT COUNT(*) c FROM donations WHERE campaign_id=?',[$id]);if((int)($count['c']??0)>0){Session::flash('error','This campaign has donation records. Set it inactive instead of deleting it.');redirect('admin/campaigns');}Database::exec('DELETE FROM campaigns WHERE id=?',[$id]);Audit::write((int)$u['id'],'DELETE','campaigns',$id,'Campaign deleted');Session::flash('success','Campaign deleted.');redirect('admin/campaigns');}
}
