<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Audit;
use App\Core\Upload;
use App\Core\Notification;
use App\Core\Site;

final class VolunteerController extends Controller
{
    public function index(): void { $page=Site::page('volunteer'); $this->view('volunteer',['page'=>$page,'settings'=>Site::settings(),'seo'=>['title'=>$page['meta_title']??'Become a Volunteer | 97 Foundation','description'=>$page['meta_description']??'Apply to join 97 Foundation as a volunteer. Approved volunteers receive portal access, a unique ID and verification QR code.']]); }
    public function store(): void
    {
        $this->requirePost();
        $name=trim($_POST['full_name']??''); $email=strtolower(trim($_POST['email']??'')); $mobile=trim($_POST['mobile']??''); $gender=trim($_POST['gender']??'');
        $password=(string)($_POST['password']??''); $confirm=(string)($_POST['password_confirmation']??'');
        if(strlen($name)<2 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($mobile)<6 || $gender==='' || strlen($password)<10 || $password!==$confirm){ Session::flash('error','Please complete all required fields. Passwords must match and contain at least 10 characters.'); redirect('volunteer'); }
        if(Database::one('SELECT id FROM users WHERE email=?',[$email])){ Session::flash('error','An account already exists with this email address.'); redirect('volunteer'); }
        $photo=null; try { if(isset($_FILES['profile_photo']) && ($_FILES['profile_photo']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE) $photo=Upload::publicImage($_FILES['profile_photo'],'profiles'); }
        catch(\Throwable $e){ Session::flash('error',$e->getMessage()); redirect('volunteer'); }
        Database::transaction(function()use($name,$email,$mobile,$gender,$password,$photo){
            $uid=Database::insert('INSERT INTO users(name,email,password_hash,role,status,profile_photo) VALUES(?,?,?,"volunteer","pending",?)',[$name,$email,password_hash($password,PASSWORD_ARGON2ID),$photo]);
            $vid=Database::insert('INSERT INTO volunteers(user_id,full_name,email,mobile,gender,date_of_birth,address,city,country,skills,occupation,interests,emergency_contact,profile_photo,status,verification_token) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,? ,"pending",?)',[
                $uid,$name,$email,$mobile,$gender,$_POST['date_of_birth']?:null,substr(trim($_POST['address']??''),0,500)?:null,substr(trim($_POST['city']??''),0,120)?:null,substr(trim($_POST['country']??''),0,120)?:null,substr(trim($_POST['skills']??''),0,2000)?:null,substr(trim($_POST['occupation']??''),0,160)?:null,substr(trim($_POST['interests']??''),0,2000)?:null,substr(trim($_POST['emergency_contact']??''),0,220)?:null,$photo,bin2hex(random_bytes(32))]);
            Audit::write($uid,'VOLUNTEER_APPLICATION','volunteers',$vid,'Volunteer application submitted');
        });
        Notification::admins('New volunteer application',$name.' has submitted a volunteer application.','volunteer','admin/volunteers');
        Session::flash('success','Application submitted successfully. Your account will become available after administrator approval.'); redirect('volunteer');
    }
}
