<?php
namespace App\Controllers\Admin;
use App\Core\Controller;use App\Core\Auth;use App\Core\Database;use App\Core\Session;use App\Core\Upload;use App\Core\Audit;use App\Core\Upgrade71;use App\Core\BrandImage;
final class BrandingAdminController extends Controller
{
 public function index():void{$u=Auth::requireAdmin();Auth::enforce('manage_settings');$rows=Database::all('SELECT setting_group,setting_key,setting_value FROM settings WHERE setting_group IN("foundation","seo","header")');$s=[];foreach($rows as $r)$s[$r['setting_group']][$r['setting_key']]=$r['setting_value'];$this->view('admin/branding',['user'=>$u,'settingsData'=>$s,'adminTitle'=>'Branding & SEO'],'admin');}
 public function save():void{$this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_settings');$website=substr(trim($_POST['website']??''),0,255);if($website!==''&&!filter_var($website,FILTER_VALIDATE_URL)){Session::flash('error','Enter a valid organization website URL including https://');redirect('admin/branding');}$fields=['name','tagline','email','phone','whatsapp','address','opening_hours','facebook','instagram','linkedin','youtube'];foreach($fields as $k)$this->set('foundation',$k,substr(trim($_POST[$k]??''),0,$k==='address'?500:255),(int)$u['id'],1);$this->set('foundation','website',$website,(int)$u['id'],1);$this->set('foundation','logo_enabled',isset($_POST['logo_enabled'])?'1':'0',(int)$u['id'],1);
 $logo=setting('foundation','logo_path','');$favicon=setting('foundation','favicon_path','');$wordmark=setting('foundation','wordmark_path','');$portal=setting('foundation','portal_login_image_path','');$memberLogin=setting('foundation','member_login_image_path','');$adminBg=setting('foundation','admin_login_background_path','');$social=setting('seo','social_image','');
 try{
   if(isset($_FILES['logo'])&&($_FILES['logo']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$logo=Upload::publicImage($_FILES['logo'],'branding');
   if(isset($_FILES['favicon'])&&($_FILES['favicon']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$favicon=Upload::publicImage($_FILES['favicon'],'branding');
   if(isset($_FILES['wordmark'])&&($_FILES['wordmark']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$wordmark=Upload::publicImage($_FILES['wordmark'],'branding');
   if(isset($_POST['reset_logo']))$logo=Upgrade71::installBrandAsset('logo_path')??$logo;
   if(isset($_POST['reset_wordmark']))$wordmark=Upgrade71::installBrandAsset('wordmark_path')??$wordmark;
   if(isset($_POST['reset_favicon']))$favicon=Upgrade71::installBrandAsset('favicon_path')??$favicon;
   if(isset($_FILES['portal_login_image'])&&($_FILES['portal_login_image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$portal=Upload::publicImage($_FILES['portal_login_image'],'branding');
   if(isset($_FILES['member_login_image'])&&($_FILES['member_login_image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$memberLogin=Upload::publicImage($_FILES['member_login_image'],'branding');
   if(isset($_FILES['admin_login_background'])&&($_FILES['admin_login_background']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$adminBg=Upload::publicImage($_FILES['admin_login_background'],'branding');
   if(isset($_FILES['social_image'])&&($_FILES['social_image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$social=Upload::publicImage($_FILES['social_image'],'branding');
 }catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect('admin/branding');}
 // Header-ready logos: trim empty margins so the artwork fills its size (skipped for the bundled official files).
 $logoNotice='';
 if(isset($_POST['optimize_logo'])){
   foreach(['logo','wordmark'] as $kind){$path=$kind==='logo'?$logo:$wordmark;if($path===''||str_ends_with($path,'-header.png')||str_starts_with($path,'branding/97-foundation-'))continue;$opt=BrandImage::optimize($path);if($opt){if($kind==='logo')$logo=$opt;else $wordmark=$opt;}else $logoNotice=' Empty space could not be trimmed automatically on this server (PHP GD extension unavailable).';}
 }
 $uid=(int)$u['id'];$hd=header_brand_defaults();
 $clamp=fn(string $k,int $min,int $max)=>(string)max($min,min($max,(int)($_POST['header_'.$k]??$hd[$k])));
 $this->set('header','logo_height',$clamp('logo_height',40,110),$uid,1);$this->set('header','logo_height_mobile',$clamp('logo_height_mobile',34,72),$uid,1);$this->set('header','wordmark_height',$clamp('wordmark_height',32,96),$uid,1);
 $this->set('header','show_wordmark',isset($_POST['header_show_wordmark'])?'1':'0',$uid,1);$this->set('header','shrink_on_scroll',isset($_POST['header_shrink_on_scroll'])?'1':'0',$uid,1);
 $this->set('foundation','logo_path',$logo,(int)$u['id'],1);$this->set('foundation','favicon_path',$favicon,(int)$u['id'],1);$this->set('foundation','wordmark_path',$wordmark,(int)$u['id'],1);$this->set('foundation','portal_login_image_path',$portal,(int)$u['id'],1);$this->set('foundation','member_login_image_path',$memberLogin,(int)$u['id'],1);$this->set('foundation','admin_login_background_path',$adminBg,(int)$u['id'],0);
 $this->set('seo','default_title',substr(trim($_POST['default_title']??''),0,240),(int)$u['id'],1);$this->set('seo','default_description',substr(trim($_POST['default_description']??''),0,500),(int)$u['id'],1);$this->set('seo','keywords',substr(trim($_POST['keywords']??''),0,500),(int)$u['id'],1);$this->set('seo','target_country',substr(trim($_POST['target_country']??'Sri Lanka'),0,120),(int)$u['id'],1);$this->set('seo','target_district',substr(trim($_POST['target_district']??'Polonnaruwa'),0,120),(int)$u['id'],1);$this->set('seo','target_locality',substr(trim($_POST['target_locality']??'Thambala'),0,120),(int)$u['id'],1);$this->set('seo','answer_summary',substr(trim($_POST['answer_summary']??''),0,500),(int)$u['id'],1);
 $this->set('seo','social_image',$social,(int)$u['id'],1);Audit::write((int)$u['id'],'SETTINGS_CHANGED','branding',null,'Foundation branding and SEO settings updated');Session::flash('success','Branding, header logo size, portal visuals and SEO settings updated.'.$logoNotice);redirect('admin/branding');}
 private function set(string $g,string $k,string $v,int $uid,int $public):void{Database::exec('INSERT INTO settings(setting_group,setting_key,setting_value,is_public,updated_by) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),is_public=VALUES(is_public),updated_by=VALUES(updated_by)',[$g,$k,$v,$public,$uid]);}

 /** One-click V7.1 website update: official logo + header text image, header contacts, officials table, hero defaults. */
 public function applyUpdate():void{$this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_settings');
  try{$log=Upgrade71::run((int)$u['id']);Session::flash('success','V7.1 website update applied: new logo and header text image, updated email, phone and Facebook page, Officials pages and hero banner are ready.');}
  catch(\Throwable $e){error_log('V7.1 update failed: '.$e->getMessage());Session::flash('error','The update could not be completed. Please run php database/upgrade_v7_1.php or contact the developer.');}
  redirect('admin/branding');}
}
