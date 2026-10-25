<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Captcha;
use App\Core\Database;
use App\Core\Session;
use App\Core\Upload;
use App\Core\Notification;
use App\Core\RateLimiter;
use App\Core\Site;
use App\Core\Audit;
use App\Core\App;

final class MemberRegistrationController extends Controller
{
    private function passwordValid(string $password): bool
    {
        $min=(int)App::config('security.member_password_min',8);
        $max=(int)App::config('security.member_password_max',12);
        $length=(function_exists('mb_strlen') ? mb_strlen($password,'UTF-8') : strlen($password));
        return $length >= $min && $length <= $max;
    }

    private function normalizedMobile(string $mobile): string
    {
        return preg_replace('/\D+/', '', $mobile) ?? '';
    }

    private function mobileExists(string $mobile, int $excludeUserId=0): bool
    {
        $needle=$this->normalizedMobile($mobile);
        if(strlen($needle)<7) return false;
        $rows=Database::all('SELECT user_id,mobile FROM member_profiles WHERE mobile IS NOT NULL AND mobile<>?',['']);
        foreach($rows as $row){
            if($excludeUserId>0 && (int)$row['user_id']===$excludeUserId) continue;
            if($this->normalizedMobile((string)$row['mobile'])===$needle) return true;
        }
        return false;
    }

    private function failRegistration(string $message, array $old=[]): never
    {
        Session::flash('error',$message);
        Session::put('member.registration_old',$old);
        Captcha::issue('member-register');
        redirect('member/register');
    }

    public function register():void
    {
        $u=Auth::user();
        if($u&&$u['role']==='member')redirect('portal');
        $old=Session::pull('member.registration_old',[]);
        $this->view('member/register',[
            'captcha'=>Captcha::current('member-register'),
            'registrationOld'=>is_array($old)?$old:[],
            'settings'=>Site::settings(),
            'seo'=>['title'=>'Member Registration | 97 Foundation','description'=>'Securely register as a 97 Foundation member.']
        ]);
    }

    public function store():void
    {
        $this->requirePost();
        $rateKey='member-registration';
        if(RateLimiter::tooManyAttempts($rateKey)){
            Session::flash('error','Too many registration attempts. Please try again in a few minutes.');
            redirect('member/register');
        }

        $name=substr(trim((string)($_POST['name']??'')),0,120);
        $dob=trim((string)($_POST['date_of_birth']??''));
        $nic=strtoupper(substr(trim((string)($_POST['nic_number']??'')),0,80));
        $gender=trim((string)($_POST['gender']??''));
        $mobile=substr(trim((string)($_POST['mobile']??'')),0,60);
        $username=strtolower(substr(trim((string)($_POST['username']??'')),0,80));
        $email=strtolower(substr(trim((string)($_POST['email']??'')),0,190));
        $password=(string)($_POST['password']??'');
        $confirm=(string)($_POST['password_confirmation']??'');
        $old=['name'=>$name,'date_of_birth'=>$dob,'nic_number'=>$nic,'gender'=>$gender,'mobile'=>$mobile,'username'=>$username,'email'=>$email];

        if(!Captcha::verify((string)($_POST['captcha']??''),'member-register')){
            RateLimiter::hit($rateKey);
            $this->failRegistration('The security number was incorrect. Please enter the new number shown.',$old);
        }

        $genders=['male','female','other','prefer_not_to_say'];
        if(strlen($name)<3) $this->failRegistration('Please enter your full name.',$old);
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)) $this->failRegistration('Please enter a valid email address.',$old);
        if(!preg_match('/^[a-z0-9._-]{4,80}$/',$username)) $this->failRegistration('Username must contain 4–80 letters, numbers, dots, underscores or hyphens.',$old);
        if(!in_array($gender,$genders,true)) $this->failRegistration('Please select a valid gender option.',$old);
        if(strlen($this->normalizedMobile($mobile))<7) $this->failRegistration('Please enter a valid contact number.',$old);
        if(strlen($nic)<5) $this->failRegistration('Please enter a valid NIC / National ID number.',$old);
        if(!$this->passwordValid($password)) $this->failRegistration('Password must contain between 8 and 12 characters.',$old);
        if($password!==$confirm) $this->failRegistration('The password confirmation does not match.',$old);

        $ts=strtotime($dob);
        if(!$ts||$ts>time()||$ts<strtotime('-120 years')) $this->failRegistration('Please enter a valid date of birth.',$old);

        try {
            if(Database::one('SELECT id FROM users WHERE email=? OR username=? LIMIT 1',[$email,$username])) $this->failRegistration('That email address or username is already registered.',$old);
            if(Database::one('SELECT id FROM member_profiles WHERE nic_number=? LIMIT 1',[$nic])) $this->failRegistration('That NIC / National ID number is already registered.',$old);
            if($this->mobileExists($mobile)) $this->failRegistration('That contact number is already registered to another member.',$old);
        } catch (\Throwable $e) {
            $ref=strtoupper(substr(bin2hex(random_bytes(8)),0,10));
            error_log('Member registration duplicate-check failure ['.$ref.']: '.$e->getMessage());
            $this->failRegistration('We could not validate your registration right now. Please try again. Reference '.$ref.'.',$old);
        }

        $photo=null;
        try {
            if(isset($_FILES['profile_photo'])&&($_FILES['profile_photo']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
                $photo=Upload::publicMemberPhoto($_FILES['profile_photo']);
            }
        } catch(\Throwable $e) {
            $this->failRegistration($e->getMessage(),$old);
        }

        try {
            $result=Database::transaction(function()use($name,$email,$username,$password,$photo,$mobile,$dob,$nic,$gender){
                $uid=Database::insert('INSERT INTO users(name,email,username,password_hash,role,status,profile_photo) VALUES(?,?,?,? ,"member","pending",?)',[
                    $name,$email,$username,password_hash($password,PASSWORD_ARGON2ID),$photo
                ]);
                $memberId=sprintf('97M-%s-%06d',date('Y'),$uid);
                $token=bin2hex(random_bytes(32));
                Database::insert('INSERT INTO member_profiles(user_id,mobile,date_of_birth,nic_number,gender,member_number,joined_at,role_title,verification_token,is_public) VALUES(?,?,?,?,?,?,CURDATE(),"Foundation Member",?,0)',[
                    $uid,$mobile,$dob,$nic,$gender,$memberId,$token
                ]);
                return ['user_id'=>$uid,'member_id'=>$memberId,'token'=>$token];
            });
        } catch(\Throwable $e) {
            if($photo) Upload::removePublicImage($photo);
            $ref=strtoupper(substr(bin2hex(random_bytes(8)),0,10));
            error_log('Member registration transaction failure ['.$ref.']: '.$e->getMessage());
            $this->failRegistration("We couldn't complete your registration. Please check your information and try again. Reference {$ref}.",$old);
        }

        Notification::admins('New member registration',$name.' registered as '.$result['member_id'].'. Review and approve the application.','info','admin/members/registrations');
        Audit::write((int)$result['user_id'],'MEMBER_REGISTERED','members',(int)$result['user_id'],'Public member registration submitted');
        RateLimiter::clear($rateKey);
        Session::forget('member.registration_old');
        Session::put('member.registration_success',['member_id'=>$result['member_id'],'token'=>$result['token'],'name'=>$name]);
        redirect('member/registration-success');
    }

    public function success():void
    {
        $data=Session::get('member.registration_success');
        if(!$data)redirect('member/register');
        $this->view('member/register-success',['registration'=>$data,'settings'=>Site::settings(),'seo'=>['title'=>'Registration Submitted | 97 Foundation','description'=>'97 Foundation member registration submitted.']]);
    }

    public function login():void
    {
        $u=Auth::user();
        if($u&&$u['role']==='member')redirect('portal');
        $this->view('member/login',['captcha'=>Captcha::current('member-login'),'settings'=>Site::settings(),'seo'=>['title'=>'Member Login | 97 Foundation','description'=>'Secure 97 Foundation member login portal.']]);
    }

    public function authenticate():void
    {
        $this->requirePost();
        $identifier=strtolower(trim((string)($_POST['identifier']??'')));
        $password=(string)($_POST['password']??'');

        if(!Captcha::verify((string)($_POST['captcha']??''),'member-login')){
            Session::flash('error','The security number was incorrect. Please enter the new number shown.');
            Captcha::issue('member-login');
            redirect('member/login');
        }
        if($identifier===''||$password===''){
            Session::flash('error','Enter your username/email and password.');
            Captcha::issue('member-login');
            redirect('member/login');
        }
        if(RateLimiter::tooManyAttempts($identifier)){
            Session::flash('error','Too many login attempts. Please try again later.');
            Captcha::issue('member-login');
            redirect('member/login');
        }

        try {
            $u=Database::one('SELECT * FROM users WHERE (email=? OR username=?) AND role="member" LIMIT 1',[$identifier,$identifier]);
            if(!$u || !password_verify($password,(string)$u['password_hash'])){
                RateLimiter::hit($identifier);
                Audit::write(null,'LOGIN_FAILED','member_auth',null,'Failed member login for '.$identifier);
                Session::flash('error','The username/email or password is incorrect.');
            } elseif($u['status']==='pending') {
                Session::flash('error','Your membership registration is still awaiting administrator approval.');
            } elseif($u['status']==='rejected') {
                Session::flash('error','Your membership registration is not currently approved. Please contact the Foundation if you need assistance.');
            } elseif($u['status']!=='active') {
                Session::flash('error','Your member account is currently unavailable. Please contact the Foundation.');
            } else {
                Auth::completeLogin($u,$identifier);
                redirect('portal');
            }
        } catch(\Throwable $e) {
            $ref=strtoupper(substr(bin2hex(random_bytes(8)),0,10));
            error_log('Member login failure ['.$ref.']: '.$e->getMessage());
            Session::flash('error','We could not sign you in right now. Please try again. Reference '.$ref.'.');
        }

        Captcha::issue('member-login');
        redirect('member/login');
    }
}
