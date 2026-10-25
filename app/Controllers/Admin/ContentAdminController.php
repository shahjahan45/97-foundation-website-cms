<?php
namespace App\Controllers\Admin;
use App\Core\Controller;use App\Core\Auth;use App\Core\Database;use App\Core\Session;use App\Core\Audit;use App\Core\Upload;
final class ContentAdminController extends Controller
{
 public function index():void
 {
  $u=Auth::requireAdmin();Auth::enforce('manage_content');
  $sectionTotal=(int)(Database::one('SELECT COUNT(*) c FROM content_sections')['c']??0);$sectionPagination=admin_pager($sectionTotal,20,'sections_page');
  $slideTotal=(int)(Database::one('SELECT COUNT(*) c FROM hero_slides')['c']??0);$slidePagination=admin_pager($slideTotal,20,'slides_page');
  $pageTotal=(int)(Database::one('SELECT COUNT(*) c FROM pages')['c']??0);$pagePagination=admin_pager($pageTotal,20,'pages_page');
  $sections=Database::all('SELECT * FROM content_sections ORDER BY sort_order,section_key LIMIT '.(int)$sectionPagination['per_page'].' OFFSET '.(int)$sectionPagination['offset']);
  $slides=Database::all('SELECT * FROM hero_slides ORDER BY sort_order,id LIMIT '.(int)$slidePagination['per_page'].' OFFSET '.(int)$slidePagination['offset']);
  $pages=Database::all('SELECT * FROM pages ORDER BY sort_order,id LIMIT '.(int)$pagePagination['per_page'].' OFFSET '.(int)$pagePagination['offset']);
  $homeSections=[];foreach(Database::all('SELECT * FROM content_sections WHERE section_key LIKE "home_%" ORDER BY sort_order,section_key') as $hr)$homeSections[$hr['section_key']]=$hr;
  $this->view('admin/content',['user'=>$u,'sections'=>$sections,'slides'=>$slides,'pages'=>$pages,'homeSections'=>$homeSections,'sectionPagination'=>$sectionPagination,'slidePagination'=>$slidePagination,'pagePagination'=>$pagePagination,'adminTitle'=>'Website Content Manager'],'admin');
 }
 public function save():void
 {
  $this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_content');$key=preg_replace('/[^a-z0-9_]/','',strtolower($_POST['section_key']??''));
  if(!$key){Session::flash('error','Section key is required.');redirect('admin/content');}
  $img=null;try{if(isset($_FILES['image'])&&($_FILES['image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$img=Upload::publicImage($_FILES['image'],'content');}catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect('admin/content');}
  $existing=Database::one('SELECT image_path FROM content_sections WHERE section_key=?',[$key]);$img=$img?:($existing['image_path']??null);
  Database::exec('INSERT INTO content_sections(section_key,title,eyebrow,content,cta_text,cta_url,image_path,item_limit,is_active,sort_order,updated_by) VALUES(?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),eyebrow=VALUES(eyebrow),content=VALUES(content),cta_text=VALUES(cta_text),cta_url=VALUES(cta_url),image_path=VALUES(image_path),item_limit=VALUES(item_limit),is_active=VALUES(is_active),sort_order=VALUES(sort_order),updated_by=VALUES(updated_by)',[$key,substr(trim($_POST['title']??''),0,200),substr(trim($_POST['eyebrow']??''),0,100),substr(trim($_POST['content']??''),0,30000),substr(trim($_POST['cta_text']??''),0,100),substr(trim($_POST['cta_url']??''),0,255),$img,max(1,min(12,(int)($_POST['item_limit']??3))),isset($_POST['is_active'])?1:0,(int)($_POST['sort_order']??0),(int)$u['id']]);
  Audit::write((int)$u['id'],'CONTENT_UPDATED','content',null,'Content section '.$key.' updated');Session::flash('success','Content section saved.');$returnTab=preg_replace('/[^a-z]/','',(string)($_POST['return_tab']??''));redirect('admin/content'.($returnTab?'?tab='.$returnTab:''));
 }
 public function delete():void{$this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_content');$key=preg_replace('/[^a-z0-9_]/','',strtolower($_POST['section_key']??''));if($key)Database::exec('DELETE FROM content_sections WHERE section_key=?',[$key]);Audit::write((int)$u['id'],'DELETE','content',null,'Content section '.$key.' deleted');Session::flash('success','Content section deleted.');redirect('admin/content');}
 public function pageSave():void
 {
  $this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_content');$slug=preg_replace('/[^a-z0-9-]/','',strtolower($_POST['slug']??''));$title=substr(trim($_POST['title']??''),0,200);if(!$slug||strlen($title)<2){Session::flash('error','Page slug and title are required.');redirect('admin/content');}
  $img=null;try{if(isset($_FILES['image'])&&($_FILES['image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$img=Upload::publicImage($_FILES['image'],'pages');}catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect('admin/content');}
  $old=Database::one('SELECT image_path FROM pages WHERE slug=?',[$slug]);$img=$img?:($old['image_path']??null);
  Database::exec('INSERT INTO pages(slug,title,eyebrow,hero_title,hero_subtitle,body,image_path,meta_title,meta_description,answer_summary,is_active,sort_order,updated_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),eyebrow=VALUES(eyebrow),hero_title=VALUES(hero_title),hero_subtitle=VALUES(hero_subtitle),body=VALUES(body),image_path=VALUES(image_path),meta_title=VALUES(meta_title),meta_description=VALUES(meta_description),answer_summary=VALUES(answer_summary),is_active=VALUES(is_active),sort_order=VALUES(sort_order),updated_by=VALUES(updated_by)',[$slug,$title,substr(trim($_POST['eyebrow']??''),0,100),substr(trim($_POST['hero_title']??''),0,240),substr(trim($_POST['hero_subtitle']??''),0,600),substr(trim($_POST['body']??''),0,50000),$img,substr(trim($_POST['meta_title']??''),0,240),substr(trim($_POST['meta_description']??''),0,500),substr(trim($_POST['answer_summary']??''),0,900),isset($_POST['is_active'])?1:0,(int)($_POST['sort_order']??0),(int)$u['id']]);Audit::write((int)$u['id'],'PAGE_UPDATED','content',null,'Page '.$slug.' saved');Session::flash('success','Page saved.');redirect('admin/content');
 }
 public function pageDelete():void
 {
  $this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_content');$id=(int)($_POST['id']??0);
  $page=Database::one('SELECT slug FROM pages WHERE id=?',[$id]);
  $reserved=['about','what-we-do','causes','campaigns','events','gallery','volunteer','donate','contact'];
  if($page && in_array((string)$page['slug'],$reserved,true)){Session::flash('error','This is a core website page. Set it inactive or edit its content instead of deleting it.');redirect('admin/content');}
  if($page) Database::exec('DELETE FROM pages WHERE id=?',[$id]);
  Audit::write((int)$u['id'],'DELETE','pages',$id,'Page deleted');Session::flash('success','Page deleted.');redirect('admin/content');
 }
 public function slideSave():void
 {
  $this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_content');$id=(int)($_POST['id']??0);$title=substr(trim($_POST['title']??''),0,180);if(strlen($title)<2){Session::flash('error','Slide title is required.');redirect('admin/content?tab=slides');}
  $desktop=null;$mobile=null;try{if(isset($_FILES['image'])&&($_FILES['image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$desktop=Upload::publicImage($_FILES['image'],'hero');if(isset($_FILES['mobile_image'])&&($_FILES['mobile_image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$mobile=Upload::publicImage($_FILES['mobile_image'],'hero-mobile');}catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect('admin/content?tab=slides');}
  $startAt=trim((string)($_POST['start_at']??''));$endAt=trim((string)($_POST['end_at']??''));$startAt=$startAt!==''?date('Y-m-d H:i:s',strtotime($startAt)):null;$endAt=$endAt!==''?date('Y-m-d H:i:s',strtotime($endAt)):null;if($startAt&&$endAt&&strtotime($endAt)<=strtotime($startAt)){Session::flash('error','Banner end date must be after the start date.');redirect('admin/content?tab=slides');}
  if($id){$old=Database::one('SELECT image_path,mobile_image_path FROM hero_slides WHERE id=?',[$id]);$desktop=$desktop?:($old['image_path']??null);$mobile=$mobile?:($old['mobile_image_path']??null);Database::exec('UPDATE hero_slides SET title=?,subtitle=?,image_path=?,mobile_image_path=?,start_at=?,end_at=?,button_text=?,button_url=?,secondary_text=?,secondary_url=?,is_active=?,sort_order=? WHERE id=?',[$title,substr(trim($_POST['subtitle']??''),0,500),$desktop,$mobile,$startAt,$endAt,substr(trim($_POST['button_text']??''),0,80),substr(trim($_POST['button_url']??''),0,255),substr(trim($_POST['secondary_text']??''),0,80),substr(trim($_POST['secondary_url']??''),0,255),isset($_POST['is_active'])?1:0,(int)($_POST['sort_order']??0),$id]);}
  else{$id=Database::insert('INSERT INTO hero_slides(title,subtitle,image_path,mobile_image_path,start_at,end_at,button_text,button_url,secondary_text,secondary_url,is_active,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',[$title,substr(trim($_POST['subtitle']??''),0,500),$desktop,$mobile,$startAt,$endAt,substr(trim($_POST['button_text']??''),0,80),substr(trim($_POST['button_url']??''),0,255),substr(trim($_POST['secondary_text']??''),0,80),substr(trim($_POST['secondary_url']??''),0,255),isset($_POST['is_active'])?1:0,(int)($_POST['sort_order']??0)]);}
  Audit::write((int)$u['id'],'SLIDE_UPDATED','hero',$id,'Responsive hero slide saved');Session::flash('success','Responsive hero slide saved.');redirect('admin/content?tab=slides');
 }
 public function slideDelete():void{$this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_content');$id=(int)($_POST['id']??0);Database::exec('DELETE FROM hero_slides WHERE id=?',[$id]);Session::flash('success','Hero slide deleted.');redirect('admin/content?tab=slides');}
 public function slideReorder():void{$this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_content');$order=$_POST['order']??[];if(is_array($order)){foreach(array_values($order) as $i=>$id)Database::exec('UPDATE hero_slides SET sort_order=? WHERE id=?',[$i+1,(int)$id]);Audit::write((int)$u['id'],'SLIDES_REORDERED','hero',null,'Hero slides reordered');Session::flash('success','Hero slide order updated.');}redirect('admin/content?tab=slides');}


 /** V7.1 homepage hero banner: content, five background images, circles and display options. */
 public function heroSave():void
 {
  $this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_content');$uid=(int)$u['id'];
  $text=['eyebrow'=>40,'title_line_1'=>60,'title_line_2'=>60,'subtitle'=>320,'primary_text'=>40,'primary_url'=>255,'secondary_text'=>40,'secondary_url'=>255,'supporters_text'=>80,'highlight_1'=>40,'highlight_2'=>40,'highlight_3'=>40,'highlight_4'=>40,'strip_text'=>80];
  for($i=1;$i<=HERO_SLIDE_COUNT;$i++)$text['bg_alt_'.$i]=140;
  $values=[];foreach($text as $k=>$max)$values[$k]=mb_substr(trim((string)($_POST[$k]??'')),0,$max);
  $values['overlay_opacity']=(string)max(40,min(95,(int)($_POST['overlay_opacity']??82)));
  $values['interval_seconds']=(string)max(3,min(15,(int)($_POST['interval_seconds']??6)));
  $values['show_circles']=isset($_POST['show_circles'])?'1':'0';
  $current=hero_settings();
  $file=function(string $name):?string{if(!isset($_FILES[$name])||($_FILES[$name]['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return null;return Upload::publicImage($_FILES[$name],'hero');};
  try{
   for($i=1;$i<=HERO_SLIDE_COUNT;$i++){
    foreach(['bg_image_','bg_mobile_'] as $prefix){$key=$prefix.$i;$new=$file($key);$values[$key]=$new??(string)$current[$key];}
    if(isset($_POST['reset_bg_'.$i])){$values['bg_image_'.$i]='';$values['bg_mobile_'.$i]='';}
   }
   for($c=1;$c<=3;$c++){$key='circle_image_'.$c;$new=$file($key);$values[$key]=$new??(string)$current[$key];if(isset($_POST['reset_circle_'.$c]))$values[$key]='';}
  }catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect('admin/content?tab=slides');}
  foreach($values as $k=>$v){
   if(Database::one('SELECT id FROM settings WHERE setting_group="hero" AND setting_key=? LIMIT 1',[$k]))Database::exec('UPDATE settings SET setting_value=?,is_public=1,updated_by=? WHERE setting_group="hero" AND setting_key=?',[$v,$uid,$k]);
   else Database::exec('INSERT INTO settings(setting_group,setting_key,setting_value,is_public,updated_by) VALUES("hero",?,?,1,?)',[$k,$v,$uid]);
  }
  Audit::write($uid,'CONTENT_UPDATED','content',null,'Homepage hero banner updated');Session::flash('success','Hero banner saved and published.');redirect('admin/content?tab=slides');
 }
}