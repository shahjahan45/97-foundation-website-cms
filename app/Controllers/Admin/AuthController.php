<?php
namespace App\Controllers\Admin;
use App\Core\Controller;use App\Core\Auth;use App\Core\Session;use App\Core\Captcha;use App\Core\Database;use App\Core\Totp;use App\Core\RateLimiter;
final class AuthController extends Controller
{
    private const ADMIN_ROLES=['super_admin','administrator','finance_manager','volunteer_manager','content_manager','support_agent','event_manager'];
    public function login(): void
    {
        if(Auth::check()){ $u=Auth::user(); if($u&&in_array($u['role'],self::ADMIN_ROLES,true)) redirect('admin'); }
        Session::forget('auth.2fa_pending_user_id');Session::forget('auth.2fa_pending_started');
        $this->view('admin/login',['captcha'=>Captcha::current('admin')],'admin-auth');
    }
    public function authenticate(): void
    {
        $this->requirePost();
        if(!Captcha::verify((string)($_POST['captcha']??''),'admin')){Session::flash('error','The security number is incorrect. Please enter the new number shown.');Captcha::issue('admin');redirect('admin/login');}
        $identifier=trim((string)($_POST['email']??''));$password=(string)($_POST['password']??'');$u=Auth::verifyPrimary($identifier,$password);
        if(!$u){if(!Session::get('flash.error'))Session::flash('error','Login failed. Check your credentials or wait before retrying.');Captcha::issue('admin');redirect('admin/login');}
        if(!in_array($u['role'],self::ADMIN_ROLES,true)){RateLimiter::hit(strtolower($identifier));Session::flash('error','This account does not have administration access.');Captcha::issue('admin');redirect('admin/login');}
        if((int)$u['two_factor_enabled']===1){Session::regenerate();Session::put('auth.2fa_pending_user_id',(int)$u['id']);Session::put('auth.2fa_pending_started',time());redirect('admin/2fa');}
        Auth::completeLogin($u,$identifier);redirect('admin');
    }
    public function twoFactor():void
    {
        if(Auth::check())redirect('admin');$id=(int)Session::get('auth.2fa_pending_user_id',0);$started=(int)Session::get('auth.2fa_pending_started',0);
        if(!$id||!$started||time()-$started>300){Session::forget('auth.2fa_pending_user_id');Session::forget('auth.2fa_pending_started');Session::flash('error','Your two-factor verification session expired. Please sign in again.');redirect('admin/login');}
        $this->view('admin/two-factor-challenge',[],'admin-auth');
    }
    public function verifyTwoFactor():void
    {
        $this->requirePost();$id=(int)Session::get('auth.2fa_pending_user_id',0);$started=(int)Session::get('auth.2fa_pending_started',0);$otp=trim((string)($_POST['otp']??''));
        if(!$id||!$started||time()-$started>300){Session::flash('error','Your two-factor verification session expired. Please sign in again.');redirect('admin/login');}
        $u=Database::one('SELECT * FROM users WHERE id=? AND status="active" LIMIT 1',[$id]);
        if(!$u||!in_array($u['role'],self::ADMIN_ROLES,true)||(int)$u['two_factor_enabled']!==1||!Totp::verify((string)$u['two_factor_secret'],$otp)){Session::flash('error','The authenticator code is not valid. Please try again.');redirect('admin/2fa');}
        Auth::completeLogin($u,(string)$u['email']);redirect('admin');
    }
    public function cancelTwoFactor():void{$this->requirePost();Session::forget('auth.2fa_pending_user_id');Session::forget('auth.2fa_pending_started');redirect('admin/login');}
    public function logout(): void { $this->requirePost(); Auth::logout(); redirect('admin/login'); }
}
