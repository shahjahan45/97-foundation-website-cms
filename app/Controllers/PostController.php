<?php
namespace App\Controllers;
use App\Core\Controller;use App\Core\Database;use App\Core\Site;
final class PostController extends Controller
{
 public function index():void{$type=in_array($_GET['type']??'',['news','blog'],true)?$_GET['type']:'';$params=[];$where='status="published" AND published_at<=NOW()';if($type){$where.=' AND post_type=?';$params[]=$type;}$rows=Database::all('SELECT * FROM posts WHERE '.$where.' ORDER BY is_featured DESC,published_at DESC,id DESC LIMIT 60',$params);$this->view('posts',['posts'=>$rows,'type'=>$type,'settings'=>Site::settings(),'seo'=>['title'=>'News & Blog | 97 Foundation','description'=>'Latest news, stories and updates from 97 Foundation.']]);}
 public function show(string $slug):void{$p=Database::one('SELECT * FROM posts WHERE slug=? AND status="published" AND published_at<=NOW() LIMIT 1',[$slug]);if(!$p){http_response_code(404);$this->view('404',['settings'=>Site::settings()]);return;}$this->view('post-detail',['post'=>$p,'settings'=>Site::settings(),'seo'=>['title'=>$p['meta_title']?:$p['title'].' | 97 Foundation','description'=>$p['meta_description']?:($p['excerpt']?:'97 Foundation update.'),'image'=>$p['featured_image']??'']]);}
}
