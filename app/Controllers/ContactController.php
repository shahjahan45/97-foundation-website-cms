<?php
namespace App\Controllers;
use App\Core\Controller;use App\Core\Database;use App\Core\Session;use App\Core\Site;use App\Core\Notification;
final class ContactController extends Controller
{
    public function index(): void { $page=Site::page('contact'); $this->view('contact',['settings'=>Site::settings(),'page'=>$page,'seo'=>['title'=>$page['meta_title']??'Contact 97 Foundation','description'=>$page['meta_description']??'Contact 97 Foundation for donations, volunteering, partnerships and support.']]); }
    public function store(): void
    {
        $this->requirePost();$n=trim($_POST['full_name']??'');$e=trim($_POST['email']??'');$m=trim($_POST['message']??'');
        if(strlen($n)<2||!filter_var($e,FILTER_VALIDATE_EMAIL)||strlen($m)<10){Session::flash('error','Please complete the contact form correctly.');redirect('contact');}
        $id=Database::insert('INSERT INTO contact_messages(full_name,email,mobile,subject,category,message) VALUES(?,?,?,?,?,?)',[$n,$e,substr(trim($_POST['mobile']??''),0,60),substr(trim($_POST['subject']??'General Inquiry'),0,180),substr(trim($_POST['category']??'general'),0,80),substr($m,0,10000)]);
        Notification::admins('New website inquiry',$n.' submitted a new contact inquiry.','inquiry','admin');
        Session::flash('success','Your message has been received. Reference #'.$id.'. Our team will contact you.');redirect('contact');
    }
}
