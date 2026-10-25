<?php
namespace App\Controllers;
use App\Core\Controller;use App\Core\Database;use App\Core\Site;
final class VerifyController extends Controller
{
    public function volunteer(string $token):void{$v=null;if(preg_match('/^[a-f0-9]{64}$/',$token))$v=Database::one('SELECT volunteer_id,full_name,status,approved_at,profile_photo FROM volunteers WHERE verification_token=? LIMIT 1',[$token]);$this->view('verify',['person'=>$v,'kind'=>'volunteer','settings'=>Site::settings(),'seo'=>['title'=>'Volunteer Verification | 97 Foundation','description'=>'Verify an official 97 Foundation volunteer ID.']]);}
    public function member(string $token):void{$m=null;if(preg_match('/^[a-f0-9]{64}$/',$token))$m=Database::one('SELECT u.name full_name,u.status,u.profile_photo,m.member_number,m.joined_at,m.role_title FROM member_profiles m JOIN users u ON u.id=m.user_id WHERE m.verification_token=? AND u.role="member" LIMIT 1',[$token]);if($m){$m['volunteer_id']=$m['member_number'];$m['approved_at']=$m['joined_at'];}$this->view('verify',['person'=>$m,'kind'=>'member','settings'=>Site::settings(),'seo'=>['title'=>'Member Verification | 97 Foundation','description'=>'Verify an official 97 Foundation member ID.']]);}
}
