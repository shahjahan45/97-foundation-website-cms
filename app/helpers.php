<?php
use App\Core\App;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\Database;

function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function config(string $key, mixed $default = null): mixed { return App::config($key, $default); }
function url(string $path = ''): string
{
    $base = rtrim((string)config('app.url', ''), '/');
    $path = ltrim($path, '/');
    if ($path === '') return $base !== '' ? $base . '/' : '/';
    return ($base !== '' ? $base : '') . '/' . $path;
}
function asset(string $path): string {
    $clean=ltrim($path,'/');
    $url=url('public/assets/'.$clean);
    $file=dirname(__DIR__).'/public/assets/'.$clean;
    $version=is_file($file)?(string)filemtime($file):'';
    return $version!==''?$url.'?v='.rawurlencode($version):$url;
}
function absolute_url(string $path = ''): string {
    $u = url($path);
    if (preg_match('~^https?://~i', $u)) return $u;
    $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost')) ?: 'localhost';
    return $scheme . '://' . $host . (str_starts_with($u, '/') ? $u : '/' . $u);
}
function public_upload(string $path): string {
    $clean=ltrim($path,'/');
    $u=url('public/uploads/'.$clean);
    $file=dirname(__DIR__).'/public/uploads/'.$clean;
    $version=is_file($file)?(string)filemtime($file):'';
    return $version!==''?$u.'?v='.rawurlencode($version):$u;
}
function csrf_field(): string { return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">'; }
function old(string $key, string $default = ''): string { return e((string)Session::get('old.' . $key, $default)); }
function flash(string $key): mixed { return Session::pull('flash.' . $key); }
function money(float|int|string $amount, string $currency = 'LKR'): string { return 'LKR ' . number_format((float)$amount, 2); }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }
function setting(string $group, string $key, string $default=''): string {
    try { $r=Database::one('SELECT setting_value FROM settings WHERE setting_group=? AND setting_key=? LIMIT 1',[$group,$key]); return (string)($r['setting_value']??$default); }
    catch (\Throwable) { return $default; }
}
function current_path(): string {
    $path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
    $base=parse_url((string)config('app.url',''),PHP_URL_PATH)?:'';
    $base=($base==='/')?'':rtrim($base,'/');
    if($base!=='' && ($path===$base || str_starts_with($path,$base.'/'))){$path=substr($path,strlen($base))?:'/';}
    return '/' . trim($path,'/');
}
function nav_active(string $path, bool $prefix=false): string {
    $c=current_path(); $p='/' . trim($path,'/'); if($p==='//')$p='/';
    $active=$prefix ? ($c===$p || str_starts_with($c,$p.'/')) : $c===$p;
    return $active?'active':'';
}
function safe_image(?string $path, string $fallback='images/demo/hero-children.jpg'): string {
    if(!$path) return asset($fallback);
    if(str_starts_with($path,'demo/')) return asset('images/'.$path);
    return public_upload($path);
}


function member_photo_sources(?string $path): array {
    $clean=ltrim((string)$path,'/');
    if($clean==='' || str_contains($clean,'..')) return ['src'=>'','srcset'=>''];
    $root=dirname(__DIR__).'/public/uploads';
    $stem=pathinfo($clean,PATHINFO_FILENAME);$dir=dirname($clean);
    $variants=[];
    foreach([360,720] as $w){
        $rel=$dir.'/optimized/'.$stem.'-'.$w.'.webp';
        if(is_file($root.'/'.$rel)) $variants[$w]=public_upload($rel);
    }
    $src=$variants[720]??$variants[360]??public_upload($clean);
    $srcset='';
    if($variants){
        $parts=[];foreach($variants as $w=>$url)$parts[]=$url.' '.$w.'w';
        $srcset=implode(', ',$parts);
    }
    return ['src'=>$src,'srcset'=>$srcset];
}

function foundation_logo_url(bool $requireEnabled=true): string {
    $logo=setting('foundation','logo_path','');
    $enabled=setting('foundation','logo_enabled','1')==='1';
    if(!$logo) return '';
    if($requireEnabled && !$enabled) return '';
    return public_upload($logo);
}
function foundation_favicon_url(): string {
    $favicon=setting('foundation','favicon_path','');
    if($favicon) return public_upload($favicon);
    $logo=foundation_logo_url(false);
    return $logo;
}
function campaign_fallback_image(array $campaign=[], int $index=0): string {
    if(!empty($campaign['cover_image'])) return public_upload((string)$campaign['cover_image']);
    $hay=strtolower(trim(($campaign['title']??'').' '.($campaign['category']??'').' '.($campaign['location']??'')));
    if(str_contains($hay,'educ')) return asset('images/demo/campaign-education.jpg');
    if(str_contains($hay,'health')||str_contains($hay,'medical')) return asset('images/demo/campaign-health.jpg');
    $fallbacks=['images/demo/campaign-families.jpg','images/demo/campaign-education.jpg','images/demo/campaign-health.jpg'];
    return asset($fallbacks[$index%count($fallbacks)]);
}
function brand_target_area(): array {
    return [
        'country'=>setting('seo','target_country','Sri Lanka'),
        'district'=>setting('seo','target_district','Polonnaruwa'),
        'locality'=>setting('seo','target_locality','Thambala'),
    ];
}
function seo_json(array $data): string { return json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?: '{}'; }
function unread_notifications(?int $userId): int {
    if(!$userId) return 0;
    try { $r=Database::one('SELECT COUNT(*) c FROM notifications WHERE user_id=? AND read_at IS NULL',[$userId]); return (int)($r['c']??0); }
    catch(\Throwable){ return 0; }
}


function notification_panel_shell(string $scope, int $unread = 0): string {
    $scope=$scope==='portal'?'portal':'admin';
    $base=$scope.'/notifications';
    $title=$scope==='portal'?'Member Notifications':'Admin Notifications';
    $token=\App\Core\Csrf::token();
    $count=$unread>99?'99+':(string)$unread;
    return '<div class="notification-center-overlay" data-notification-center data-feed-url="'.e(url($base.'/feed')).'" data-read-url="'.e(url($base.'/read')).'" data-read-all-url="'.e(url($base.'/read-all')).'" aria-hidden="true">'
        .'<button class="notification-center-backdrop" type="button" data-notification-close aria-label="Close notifications"></button>'
        .'<aside class="notification-center-panel" role="dialog" aria-modal="true" aria-labelledby="notification-center-title">'
        .'<input type="hidden" data-notification-token value="'.e($token).'">'
        .'<header class="notification-center-head"><div class="notification-center-heading"><span class="notification-center-head-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></span><div><h2 id="notification-center-title">Notifications</h2><p><b data-notification-unread-label>'.($unread?e($count).' unread':'All caught up').'</b><span> · '.e($title).'</span></p></div></div><button class="notification-center-x" type="button" data-notification-close aria-label="Close notifications">×</button></header>'
        .'<div class="notification-center-toolbar"><div class="notification-filter-tabs" role="tablist" aria-label="Notification filters"><button type="button" class="active" data-notification-filter="all">All</button><button type="button" data-notification-filter="unread">Unread</button></div><button class="notification-mark-all" type="button" data-notification-mark-all>✓ Mark all as read</button></div>'
        .'<div class="notification-center-list" data-notification-list role="feed" aria-live="polite"><div class="notification-center-loading"><i></i><span>Loading notifications…</span></div></div>'
        .'<footer class="notification-center-foot"><button type="button" class="notification-load-more" data-notification-load-more hidden>Load older notifications</button><a href="'.e(url($base)).'"'.($scope==='portal'?' data-portal-link':'').'>Open notification centre →</a></footer>'
        .'</aside></div>';
}

function admin_pager(int $total, int $perPage = 20, string $param = 'page'): array {
    $total=max(0,$total);
    $param=preg_replace('/[^a-zA-Z0-9_]/','',$param) ?: 'page';
    $perParam=$param==='page'?'per_page':(preg_replace('/_page$/','_per_page',$param) ?: $param.'_per_page');
    $requested=(int)($_GET[$perParam]??0);
    if(in_array($requested,[10,20,25,50,100],true)) $perPage=$requested;
    $perPage=max(5,min(100,$perPage));
    $pages=max(1,(int)ceil($total/$perPage));
    $page=max(1,min($pages,(int)($_GET[$param]??1)));
    return [
        'page'=>$page,'per_page'=>$perPage,'pages'=>$pages,'total'=>$total,
        'offset'=>($page-1)*$perPage,'param'=>$param,'per_param'=>$perParam
    ];
}
function admin_pagination(array $p): string {
    if(!$p) return '';
    $page=max(1,(int)($p['page']??1));$pages=max(1,(int)($p['pages']??1));$total=max(0,(int)($p['total']??0));
    $perPage=max(1,(int)($p['per_page']??20));$param=(string)($p['param']??'page');$perParam=(string)($p['per_param']??($param==='page'?'per_page':'per_page'));
    $start=$total?((($page-1)*$perPage)+1):0;$end=$total?min($total,$page*$perPage):0;
    $qs=$_GET;
    $build=function(int $n)use($qs,$param){$q=$qs;$q[$param]=$n;return e(url(ltrim(current_path(),'/')).'?'.http_build_query($q));};
    $html='<nav class="admin-pagination" aria-label="Pagination">';
    $html.='<div class="pagination-summary"><b>Showing '.number_format($start).'–'.number_format($end).'</b><span>of '.number_format($total).' record'.($total===1?'':'s').'</span></div>';
    $html.='<div class="pagination-pages">';
    $html.=$page>1?'<a href="'.$build($page-1).'" aria-label="Previous page">‹</a>':'<span class="disabled" aria-hidden="true">‹</span>';
    $from=max(1,$page-2);$to=min($pages,$page+2);
    if($from>1)$html.='<a href="'.$build(1).'">1</a>'.($from>2?'<i>…</i>':'');
    for($n=$from;$n<=$to;$n++)$html.='<a class="'.($n===$page?'active':'').'" href="'.$build($n).'">'.$n.'</a>';
    if($to<$pages)$html.=($to<$pages-1?'<i>…</i>':'').'<a href="'.$build($pages).'">'.$pages.'</a>';
    $html.=$page<$pages?'<a href="'.$build($page+1).'" aria-label="Next page">›</a>':'<span class="disabled" aria-hidden="true">›</span>';
    $html.='</div>';
    $action=e(url(ltrim(current_path(),'/')));$html.='<form class="pagination-size" method="get" action="'.$action.'"><label><span>Rows</span><select name="'.e($perParam).'" onchange="this.form.submit()">';
    $sizes=[10,20,25,50,100];if(!in_array($perPage,$sizes,true)){$sizes[]=$perPage;sort($sizes);}foreach($sizes as $size)$html.='<option value="'.$size.'"'.($size===$perPage?' selected':'').'>'.$size.' / page</option>';
    $html.='</select></label>';
    foreach($_GET as $key=>$value){if($key===$param||$key===$perParam||is_array($value))continue;$html.='<input type="hidden" name="'.e((string)$key).'" value="'.e((string)$value).'">';}
    $html.='</form><span class="pagination-page-meta">Page '.$page.' of '.$pages.'</span></nav>';
    return $html;
}


function social_platform_key(string $platform, string $url=''): string {
    $v=strtolower(trim($platform.' '.$url));
    $map=[
        'facebook'=>'facebook','instagram'=>'instagram','linkedin'=>'linkedin','youtube'=>'youtube',
        'twitter'=>'x','x.com'=>'x','whatsapp'=>'whatsapp','tiktok'=>'tiktok','telegram'=>'telegram',
        'github'=>'github','threads'=>'threads'
    ];
    foreach($map as $needle=>$key) if(str_contains($v,$needle)) return $key;
    return 'link';
}
function social_icon_svg(string $platform, string $url='', string $class=''): string {
    $key=social_platform_key($platform,$url);$cls=trim('social-svg '.$class);
    $paths=[
      'facebook'=>'<path d="M13.6 21v-8h2.8l.4-3.1h-3.2V8c0-.9.3-1.5 1.6-1.5H17V3.7c-.7-.1-1.5-.2-2.3-.2-2.3 0-3.9 1.4-3.9 4.1v2.3H8.2V13h2.6v8h2.8Z"/>',
      'instagram'=>'<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4.1"/><circle cx="17.6" cy="6.7" r="1" class="fill-dot"/>',
      'linkedin'=>'<rect x="3.4" y="9.2" width="3.2" height="10.4" rx=".4"/><circle cx="5" cy="5.6" r="1.8" class="fill-dot"/><path d="M10 19.6V9.2h3.1v1.4c1-1.3 2.2-1.8 3.7-1.8 2.8 0 4.2 1.8 4.2 5.2v5.6h-3.2v-5.2c0-1.8-.6-2.8-2-2.8-1.8 0-2.6 1.2-2.6 3.5v4.5H10Z"/>',
      'youtube'=>'<path d="M21 8.2c-.2-1.6-.9-2.3-2.4-2.5C16.8 5.5 14.5 5.4 12 5.4s-4.8.1-6.6.3C3.9 5.9 3.2 6.6 3 8.2c-.2 1.1-.3 2.4-.3 3.8s.1 2.7.3 3.8c.2 1.6.9 2.3 2.4 2.5 1.8.2 4.1.3 6.6.3s4.8-.1 6.6-.3c1.5-.2 2.2-.9 2.4-2.5.2-1.1.3-2.4.3-3.8s-.1-2.7-.3-3.8Z"/><path d="m10 9 5 3-5 3V9Z" class="play-cut"/>',
      'x'=>'<path d="M5 4h3.8l3.9 5.2L17.2 4H19l-5.5 6.4L19.5 20h-3.8l-4.2-5.7L6.6 20H4.8l5.8-6.9L5 4Zm3 1.5L16.4 18H17L8.6 5.5H8Z"/>',
      'whatsapp'=>'<path d="M12 3.2a8.5 8.5 0 0 0-7.3 12.9L3.5 21l5-1.3A8.5 8.5 0 1 0 12 3.2Z"/><path d="M8.6 7.4c.3-.1.6 0 .8.4l1 2c.2.4.1.8-.2 1.1l-.8.8c.7 1.5 1.8 2.7 3.4 3.5l.8-1c.3-.3.7-.4 1.1-.2l2 .9c.4.2.5.5.4.9-.3 1.4-1.3 2.4-2.8 2.4-3.6-.1-7.9-4.2-8-7.9 0-1.5.9-2.6 2.3-2.9Z" class="inner-cut"/>',
      'telegram'=>'<path d="M21 4.5 17.8 20c-.2 1-1 1.2-1.8.7l-4.8-3.5-2.3 2.2c-.3.3-.5.5-1 .5l.3-4.9 8.9-8c.4-.3-.1-.5-.6-.2L5.5 13.7.8 12.2c-1-.3-1-1 .2-1.5L19.5 3.6c.9-.3 1.7.2 1.5.9Z"/>',
      'tiktok'=>'<path d="M15.5 3h3c.3 2.1 1.5 3.5 3.5 4.1v3.1c-1.4 0-2.7-.4-3.8-1.1v6.1c0 3.6-2.5 5.8-5.7 5.8-3.4 0-5.8-2.5-5.8-5.6 0-3.4 2.8-5.8 6.2-5.5V13c-1.6-.2-3 .8-3 2.4 0 1.4 1.1 2.5 2.6 2.5 1.8 0 3-1.2 3-3.2V3Z"/>',
      'github'=>'<path d="M12 3a9 9 0 0 0-2.8 17.6c.5.1.7-.2.7-.5v-1.8c-2.8.6-3.4-1.2-3.4-1.2-.5-1.2-1.1-1.5-1.1-1.5-.9-.6.1-.6.1-.6 1 0 1.6 1 1.6 1 .9 1.6 2.4 1.1 3 .9.1-.7.4-1.1.7-1.3-2.3-.3-4.6-1.1-4.6-5a3.9 3.9 0 0 1 1-2.7 3.6 3.6 0 0 1 .1-2.7s.9-.3 2.8 1A9.7 9.7 0 0 1 12 6c.9 0 1.8.1 2.6.3 2-1.3 2.8-1 2.8-1a3.6 3.6 0 0 1 .1 2.7 3.9 3.9 0 0 1 1 2.7c0 3.9-2.4 4.7-4.6 5 .4.3.7.9.7 1.8v2.6c0 .3.2.6.7.5A9 9 0 0 0 12 3Z"/>',
      'threads'=>'<path d="M12 3c5 0 8.5 3.4 8.5 8.3 0 5.5-3.3 9.2-8.4 9.2-4.7 0-8-3.1-8-7.6 0-3.5 2.2-6.1 5.6-6.1 3.1 0 5.5 1.8 6.4 4.8.7.2 1.4.5 1.9.9-.4-4.2-2.7-6.7-6-6.7-3.8 0-6.3 2.8-6.3 7.1 0 3.7 2.5 6.2 6.3 6.2 3.6 0 5.9-2.2 5.9-5.8-.6-.5-1.3-.8-2.1-1-.1 2.9-1.5 4.7-4.1 4.7-2.2 0-3.7-1.3-3.7-3.2 0-2.1 1.8-3.4 4.5-3.4.7 0 1.4.1 2 .2-.7-1.5-1.9-2.3-3.6-2.3-1.2 0-2.2.4-3.1 1.2l-1.2-1.6C8.1 3.8 9.8 3 12 3Zm-.2 9.2c-1.4 0-2.2.6-2.2 1.5 0 .8.7 1.4 1.8 1.4 1.5 0 2.4-1 2.5-2.7-.7-.1-1.4-.2-2.1-.2Z"/>',
      'link'=>'<path d="M9.7 14.3a4.2 4.2 0 0 0 5.9 0l3-3a4.2 4.2 0 0 0-5.9-5.9l-1.5 1.5"/><path d="M14.3 9.7a4.2 4.2 0 0 0-5.9 0l-3 3a4.2 4.2 0 0 0 5.9 5.9l1.5-1.5"/>'
    ];
    $body=$paths[$key]??$paths['link'];
    $fillKeys=['facebook','linkedin','youtube','x','telegram','tiktok','github','threads'];
    $fill=in_array($key,$fillKeys,true)?' fill="currentColor"':' fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"';
    return '<svg class="'.e($cls).'" data-social-kind="'.e($key).'" viewBox="0 0 24 24" aria-hidden="true"'.$fill.'>'.$body.'</svg>';
}

function contact_icon_svg(string $kind, string $class=''): string {
    $key=strtolower(trim($kind));$cls=trim('contact-svg '.$class);
    $paths=[
      'address'=>'<path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11Z"/><circle cx="12" cy="10" r="2.2"/>',
      'phone'=>'<path d="M7.2 3.8 9.8 7c.4.5.3 1.2-.2 1.6l-1.5 1.2c1.2 2.4 3.1 4.3 5.5 5.5l1.2-1.5c.4-.5 1.1-.6 1.6-.2l3.2 2.6c.5.4.6 1.1.3 1.6-.9 1.7-2.5 2.7-4.4 2.4C9.4 19.2 4.8 14.6 3.8 8.5c-.3-1.9.7-3.5 2.4-4.4.5-.3 1.2-.2 1.6.3Z"/>',
      'email'=>'<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m5 8 7 5 7-5"/>',
      'website'=>'<circle cx="12" cy="12" r="9"/><path d="M3.5 12h17M12 3c2.2 2.4 3.3 5.4 3.3 9S14.2 18.6 12 21M12 3C9.8 5.4 8.7 8.4 8.7 12s1.1 6.6 3.3 9"/>',
      'hours'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.4 2"/>'
    ];
    $body=$paths[$key]??$paths['website'];
    return '<svg class="'.e($cls).'" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$body.'</svg>';
}


require_once __DIR__ . '/site_ui.php';
require_once __DIR__ . '/admin_ui.php';
require_once __DIR__ . '/content_ui.php';
