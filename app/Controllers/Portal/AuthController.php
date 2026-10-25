<?php
namespace App\Controllers\Portal;
use App\Core\Controller;use App\Core\Auth;use App\Core\Captcha;use App\Core\Session;use App\Core\Site;
final class AuthController extends Controller
{
    public function login(): void
    {
        $u=Auth::user(); if($u && in_array($u['role'],['volunteer','member'],true)) redirect('portal');
        $captcha=Captcha::current('portal'); $this->view('portal/login',['captcha'=>$captcha,'settings'=>Site::settings(),'seo'=>['title'=>'Member & Volunteer Login | 97 Foundation','description'=>'Secure member and approved volunteer portal login.']]);
    }
    public function authenticate(): void
    {
        $this->requirePost();
        if(!Captcha::verify((string)($_POST['captcha']??''),'portal')){Session::flash('error','The security number was incorrect. Please enter the new number shown.');Captcha::issue('portal');redirect('login');}
        $email=trim($_POST['email']??'');$password=(string)($_POST['password']??'');
        if(Auth::attempt($email,$password,null)){
            $u=Auth::user(); if($u && in_array($u['role'],['volunteer','member'],true)) redirect('portal');
            Auth::logout(); Session::flash('error','This login is reserved for approved volunteers and members.'); redirect('login');
        }
        if(!Session::get('flash.error')) Session::flash('error','Login failed. Your application may still be pending approval, or the credentials are incorrect.'); Captcha::issue('portal'); redirect('login');
    }
    public function logout(): void { $this->requirePost(); $u=Auth::user(); $target=($u&&($u['role']??'')==='member')?'member/login':'login'; Auth::logout(); redirect($target); }
}
