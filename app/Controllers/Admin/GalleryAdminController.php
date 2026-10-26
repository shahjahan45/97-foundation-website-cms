<?php
namespace App\Controllers\Admin;
use App\Core\Controller;use App\Core\Auth;use App\Core\Database;use App\Core\Session;use App\Core\Upload;use App\Core\Audit;
final class GalleryAdminController extends Controller
{
 public function index():void
 {
  $u=Auth::requireAdmin();Auth::enforce('manage_gallery');
  $q=mb_substr(trim((string)($_GET['q']??'')),0,100);$state=in_array($_GET['state']??'',['active','hidden'],true)?$_GET['state']:'';$cat=mb_substr(trim((string)($_GET['category']??'')),0,100);
  $where='1=1';$params=[];
  if($q!==''){$like='%'.$q.'%';$where.=' AND (title LIKE ? OR caption LIKE ?)';array_push($params,$like,$like);}
  if($state!==''){$where.=' AND is_active=?';$params[]=$state==='active'?1:0;}
  if($cat!==''){$where.=' AND category=?';$params[]=$cat;}
  $total=(int)(Database::one('SELECT COUNT(*) c FROM gallery_items WHERE '.$where,$params)['c']??0);$pagination=admin_pager($total,24);
  $rows=Database::all('SELECT * FROM gallery_items WHERE '.$where.' ORDER BY sort_order,id DESC LIMIT '.(int)$pagination['per_page'].' OFFSET '.(int)$pagination['offset'],$params);
  $stats=Database::one('SELECT COUNT(*) total,SUM(CASE WHEN is_active=1 THEN 1 ELSE 0 END) active FROM gallery_items')?:[];
  $categories=array_values(array_filter(array_map(static fn($r)=>(string)$r['category'],Database::all('SELECT DISTINCT category FROM gallery_items WHERE category IS NOT NULL AND category<>"" ORDER BY category'))));
  $this->view('admin/gallery',['user'=>$u,'items'=>$rows,'pagination'=>$pagination,'q'=>$q,'state'=>$state,'categoryFilter'=>$cat,'categories'=>$categories,'stats'=>$stats,'adminTitle'=>'Gallery Manager'],'admin');
 }
 /** Show or hide one photo on the website without opening the editor. */
 public function toggle():void{$this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_gallery');$id=(int)($_POST['id']??0);$r=Database::one('SELECT title,is_active FROM gallery_items WHERE id=?',[$id]);if(!$r){Session::flash('error','Photo not found.');redirect('admin/gallery');}$to=(int)!((int)$r['is_active']);Database::exec('UPDATE gallery_items SET is_active=? WHERE id=?',[$to,$id]);Audit::write((int)$u['id'],'GALLERY_VISIBILITY','gallery',$id,$r['title'].($to?' shown':' hidden'));Session::flash('success','"'.$r['title'].'" is now '.($to?'visible on the website.':'hidden from the website.'));$back=(string)($_POST['return']??'');redirect(str_starts_with($back,'admin/gallery')?$back:'admin/gallery');}
 public function save():void{$this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_gallery');$title=substr(trim($_POST['title']??''),0,180);if(strlen($title)<2){Session::flash('error','Gallery title is required.');redirect('admin/gallery');}$path=null;try{if(isset($_FILES['image'])&&($_FILES['image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$path=Upload::publicImage($_FILES['image'],'gallery');}catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect('admin/gallery');}$id=(int)($_POST['id']??0);if($id){$old=Database::one('SELECT image_path FROM gallery_items WHERE id=?',[$id]);$path=$path?:($old['image_path']??null);Database::exec('UPDATE gallery_items SET title=?,caption=?,image_path=?,category=?,is_active=?,sort_order=? WHERE id=?',[$title,substr(trim($_POST['caption']??''),0,700),$path,substr(trim($_POST['category']??''),0,100),isset($_POST['is_active'])?1:0,(int)($_POST['sort_order']??0),$id]);}else{if(!$path){Session::flash('error','Please upload an image.');redirect('admin/gallery');}$id=Database::insert('INSERT INTO gallery_items(title,caption,image_path,category,is_active,sort_order,created_by) VALUES(?,?,?,?,?,?,?)',[$title,substr(trim($_POST['caption']??''),0,700),$path,substr(trim($_POST['category']??''),0,100),isset($_POST['is_active'])?1:0,(int)($_POST['sort_order']??0),(int)$u['id']]);}Audit::write((int)$u['id'],'GALLERY_SAVED','gallery',$id,'Gallery item saved');Session::flash('success','Gallery item saved.');redirect('admin/gallery');}
 public function delete():void{$this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_gallery');$id=(int)($_POST['id']??0);Database::exec('DELETE FROM gallery_items WHERE id=?',[$id]);Audit::write((int)$u['id'],'DELETE','gallery',$id,'Gallery item deleted');Session::flash('success','Gallery item deleted.');redirect('admin/gallery');}
}
