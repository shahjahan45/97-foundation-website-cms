<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\Audit;
use App\Core\Notification;
use App\Core\Upload;
use App\Core\App;

final class MemberAdminController extends Controller
{
    private function guard():array
    {
        $u=Auth::requireAdmin();
        if(!in_array($u['role'],['super_admin','administrator','volunteer_manager'],true)){
            http_response_code(403); exit('Forbidden');
        }
        return $u;
    }

    private function passwordValid(string $password):bool
    {
        $len=(function_exists('mb_strlen') ? mb_strlen($password,'UTF-8') : strlen($password));
        return $len >= (int)App::config('security.member_password_min',8)
            && $len <= (int)App::config('security.member_password_max',12);
    }

    private function normalizeMobile(string $mobile):string
    {
        return preg_replace('/\D+/', '', $mobile) ?? '';
    }

    private function mobileExists(string $mobile,int $exclude=0):bool
    {
        $needle=$this->normalizeMobile($mobile);
        if(strlen($needle)<7)return false;
        $rows=Database::all('SELECT user_id,mobile FROM member_profiles WHERE mobile IS NOT NULL AND mobile<>?',['']);
        foreach($rows as $row){
            if($exclude>0 && (int)$row['user_id']===$exclude)continue;
            if($this->normalizeMobile((string)$row['mobile'])===$needle)return true;
        }
        return false;
    }

    private function fail(string $message,string $path='admin/members'):never
    {
        Session::flash('error',$message);
        redirect($path);
    }

    private function render(bool $registrations=false):void
    {
        $u=$this->guard();
        $search=substr(trim((string)($_GET['q']??'')),0,100);
        $status=substr(trim((string)($_GET['status']??'')),0,20);
        $allowed=['active','pending','rejected','inactive','suspended'];
        $params=[];$where='u.role="member"';
        if($registrations)$where.=' AND u.status IN("pending","rejected")';
        elseif(in_array($status,$allowed,true)){$where.=' AND u.status=?';$params[]=$status;}
        if($search!==''){
            $where.=' AND (u.name LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR m.member_number LIKE ? OR m.nic_number LIKE ? OR m.role_title LIKE ?)';
            $like='%'.$search.'%';array_push($params,$like,$like,$like,$like,$like,$like);
        }
        $total=(int)(Database::one('SELECT COUNT(*) c FROM users u LEFT JOIN member_profiles m ON m.user_id=u.id WHERE '.$where,$params)['c']??0);
        $p=admin_pager($total,18);
        $rows=Database::all('SELECT u.*,m.mobile,m.address,m.member_number,m.joined_at,m.role_title,m.bio,m.verification_token,m.is_public,m.date_of_birth,m.nic_number,m.gender,m.approved_at,m.rejected_at,m.rejection_reason FROM users u LEFT JOIN member_profiles m ON m.user_id=u.id WHERE '.$where.' ORDER BY FIELD(u.status,"pending","active","suspended","inactive","rejected"),u.id DESC LIMIT '.$p['per_page'].' OFFSET '.$p['offset'],$params);
        $counts=Database::one('SELECT COUNT(*) total,SUM(status="pending") pending_count,SUM(status="active") active_count,SUM(status="rejected") rejected_count,SUM(status IN("inactive","suspended")) inactive_count FROM users WHERE role="member"')?:[];
        $this->view('admin/members',['user'=>$u,'members'=>$rows,'pagination'=>$p,'search'=>$search,'status'=>$status,'counts'=>$counts,'registrationMode'=>$registrations,'adminTitle'=>$registrations?'Member Registrations':'Foundation Members'],'admin');
    }

    public function index():void{$this->render(false);}
    public function registrations():void{$this->render(true);}

    public function save():void
    {
        $this->requirePost();
        $admin=$this->guard();
        $id=(int)($_POST['id']??0);
        $name=substr(trim((string)($_POST['name']??'')),0,120);
        $email=strtolower(substr(trim((string)($_POST['email']??'')),0,190));
        $username=strtolower(substr(trim((string)($_POST['username']??'')),0,80));
        $status=(string)($_POST['status']??'active');
        $allowed=['active','pending','rejected','inactive','suspended'];
        if(!in_array($status,$allowed,true))$status='active';
        $password=(string)($_POST['password']??'');
        $nic=strtoupper(substr(trim((string)($_POST['nic_number']??'')),0,80));
        $gender=strtolower(substr(trim((string)($_POST['gender']??'')),0,30));
        $dob=trim((string)($_POST['date_of_birth']??''));
        $mobile=substr(trim((string)($_POST['mobile']??'')),0,60);
        $number=substr(trim((string)($_POST['member_number']??'')),0,60);
        $joined=trim((string)($_POST['joined_at']??''));
        $address=substr(trim((string)($_POST['address']??'')),0,500);
        $roleTitle=substr(trim((string)($_POST['role_title']??'')),0,140);
        $bio=substr(trim((string)($_POST['bio']??'')),0,1000);
        $reason=substr(trim((string)($_POST['rejection_reason']??'')),0,500);
        $isPublic=($status==='active' && isset($_POST['is_public']))?1:0;

        if(strlen($name)<2||!filter_var($email,FILTER_VALIDATE_EMAIL))$this->fail('Name and a valid email address are required.');
        if($username!==''&&!preg_match('/^[a-z0-9._-]{4,80}$/',$username))$this->fail('Username must contain 4–80 valid letters, numbers, dots, underscores or hyphens.');
        if($password!==''&&!$this->passwordValid($password))$this->fail('Member passwords must contain between 8 and 12 characters.');
        if($id===0&&$password==='')$this->fail('A password between 8 and 12 characters is required for a new member.');
        if($mobile!==''&&strlen($this->normalizeMobile($mobile))<7)$this->fail('Enter a valid member contact number.');
        if($dob!=='' && (($ts=strtotime($dob))===false || $ts>time() || $ts<strtotime('-120 years')))$this->fail('Enter a valid date of birth.');
        if($joined!=='' && strtotime($joined)===false)$this->fail('Enter a valid joined date.');
        if($gender!==''&&!in_array($gender,['male','female','other','prefer_not_to_say'],true))$this->fail('Select a valid gender value.');

        try{
            if(Database::one('SELECT id FROM users WHERE (email=? OR (?<>? AND username=?)) AND id<>? LIMIT 1',[$email,$username,'',$username,$id]))$this->fail('Email or username is already used by another account.');
            if($nic!==''&&Database::one('SELECT user_id FROM member_profiles WHERE nic_number=? AND user_id<>? LIMIT 1',[$nic,$id]))$this->fail('NIC / National ID is already registered.');
            if($mobile!==''&&$this->mobileExists($mobile,$id))$this->fail('That contact number is already registered to another member.');
            if($number!==''&&Database::one('SELECT user_id FROM member_profiles WHERE member_number=? AND user_id<>? LIMIT 1',[$number,$id]))$this->fail('That Member ID is already assigned to another member.');
        }catch(\Throwable $e){
            $ref=strtoupper(substr(bin2hex(random_bytes(8)),0,10));
            error_log('Admin member pre-save validation failure ['.$ref.']: '.$e->getMessage());
            $this->fail('We could not validate this member record. Please try again. Reference '.$ref.'.');
        }

        $photo=null;
        try{
            if(isset($_FILES['profile_photo'])&&($_FILES['profile_photo']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
                $photo=Upload::publicMemberPhoto($_FILES['profile_photo']);
            }
        }catch(\Throwable $e){$this->fail($e->getMessage());}

        $savedId=0;
        try{
            $savedId=(int)Database::transaction(function()use($admin,$id,$name,$email,$username,$status,$password,$photo,$nic,$gender,$dob,$mobile,$number,$joined,$address,$roleTitle,$bio,$reason,$isPublic){
                if($id>0){
                    $old=Database::one('SELECT * FROM users WHERE id=? AND role="member" FOR UPDATE',[$id]);
                    if(!$old)throw new \RuntimeException('Member record not found.');
                    $finalPhoto=$photo?:$old['profile_photo'];
                    if($password!==''){
                        Database::exec('UPDATE users SET name=?,email=?,username=?,password_hash=?,status=?,profile_photo=? WHERE id=? AND role="member"',[
                            $name,$email,$username?:null,password_hash($password,PASSWORD_ARGON2ID),$status,$finalPhoto,$id
                        ]);
                    }else{
                        Database::exec('UPDATE users SET name=?,email=?,username=?,status=?,profile_photo=? WHERE id=? AND role="member"',[
                            $name,$email,$username?:null,$status,$finalPhoto,$id
                        ]);
                    }
                    $uid=$id;
                }else{
                    $uid=Database::insert('INSERT INTO users(name,email,username,password_hash,role,status,profile_photo) VALUES(?,?,?,? ,"member",?,?)',[
                        $name,$email,$username?:null,password_hash($password,PASSWORD_ARGON2ID),$status,$photo
                    ]);
                }

                $profile=Database::one('SELECT * FROM member_profiles WHERE user_id=? FOR UPDATE',[$uid]);
                $memberNumber=$number!==''?$number:(($profile['member_number']??'')?:sprintf('97M-%s-%06d',date('Y'),$uid));
                $token=(string)(($profile['verification_token']??'')?:bin2hex(random_bytes(32)));
                $joinedAt=$joined!==''?$joined:(($profile['joined_at']??'')?:date('Y-m-d'));
                $approvedAt=$profile['approved_at']??null;
                $approvedBy=$profile['approved_by']??null;
                $rejectedAt=null;
                $rejectionReason=null;
                if($status==='active'){
                    $approvedAt=$approvedAt?:date('Y-m-d H:i:s');
                    $approvedBy=(int)$admin['id'];
                }elseif($status==='rejected'){
                    $rejectedAt=date('Y-m-d H:i:s');
                    $rejectionReason=$reason;
                }

                if($profile){
                    Database::exec('UPDATE member_profiles SET mobile=?,date_of_birth=?,nic_number=?,gender=?,address=?,member_number=?,joined_at=?,role_title=?,bio=?,verification_token=?,is_public=?,approved_by=?,approved_at=?,rejected_at=?,rejection_reason=? WHERE user_id=?',[
                        $mobile?:null,$dob?:null,$nic?:null,$gender?:null,$address?:null,$memberNumber,$joinedAt,$roleTitle?:'Foundation Member',$bio?:null,$token,$isPublic,$approvedBy,$approvedAt,$rejectedAt,$rejectionReason,$uid
                    ]);
                }else{
                    Database::insert('INSERT INTO member_profiles(user_id,mobile,date_of_birth,nic_number,gender,address,member_number,joined_at,role_title,bio,verification_token,is_public,approved_by,approved_at,rejected_at,rejection_reason) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[
                        $uid,$mobile?:null,$dob?:null,$nic?:null,$gender?:null,$address?:null,$memberNumber,$joinedAt,$roleTitle?:'Foundation Member',$bio?:null,$token,$isPublic,$approvedBy,$approvedAt,$rejectedAt,$rejectionReason
                    ]);
                }
                Audit::write((int)$admin['id'],'MEMBER_SAVED','members',$uid,'Foundation member saved; status='.$status.'; public='.$isPublic);
                return $uid;
            });
        }catch(\Throwable $e){
            if($photo) Upload::removePublicImage($photo);
            $ref=strtoupper(substr(bin2hex(random_bytes(8)),0,10));
            error_log('Admin member save failure ['.$ref.'] member='.$id.': '.$e->getMessage());
            $this->fail('We could not save the member record. No partial changes were committed. Please try again. Reference '.$ref.'.');
        }

        if($id===0)Notification::send($savedId,'Member account created','Your 97 Foundation membership account has been created.','success','member/login');
        elseif($status==='active')Notification::send($savedId,'Member profile updated','Your membership is active. You can sign in to the Member Portal.','success','member/login');
        Session::flash('success','Foundation member saved successfully.');
        redirect('admin/members');
    }

    public function status():void
    {
        $this->requirePost();$admin=$this->guard();$id=(int)($_POST['id']??0);$status=(string)($_POST['status']??'');
        if(!in_array($status,['active','pending','rejected','inactive','suspended'],true))$this->fail('Invalid member status.');
        $reason=substr(trim((string)($_POST['reason']??'')),0,500);
        try{
            Database::transaction(function()use($admin,$id,$status,$reason){
                $member=Database::one('SELECT id,status FROM users WHERE id=? AND role="member" FOR UPDATE',[$id]);
                if(!$member)throw new \RuntimeException('Member not found.');
                Database::exec('UPDATE users SET status=? WHERE id=? AND role="member"',[$status,$id]);
                $profile=Database::one('SELECT user_id FROM member_profiles WHERE user_id=? FOR UPDATE',[$id]);
                if(!$profile){
                    $memberNumber=sprintf('97M-%s-%06d',date('Y'),$id);
                    Database::insert('INSERT INTO member_profiles(user_id,member_number,joined_at,role_title,verification_token,is_public) VALUES(?,?,CURDATE(),"Foundation Member",?,0)',[$id,$memberNumber,bin2hex(random_bytes(32))]);
                }
                if($status==='active'){
                    Database::exec('UPDATE member_profiles SET is_public=1,approved_by=?,approved_at=COALESCE(approved_at,NOW()),rejected_at=NULL,rejection_reason=NULL WHERE user_id=?',[(int)$admin['id'],$id]);
                }elseif($status==='rejected'){
                    Database::exec('UPDATE member_profiles SET is_public=0,rejected_at=NOW(),rejection_reason=? WHERE user_id=?',[$reason,$id]);
                }else{
                    Database::exec('UPDATE member_profiles SET is_public=0 WHERE user_id=?',[$id]);
                }
                Audit::write((int)$admin['id'],'MEMBER_STATUS','members',$id,'Member status changed from '.$member['status'].' to '.$status);
            });
        }catch(\Throwable $e){
            $ref=strtoupper(substr(bin2hex(random_bytes(8)),0,10));
            error_log('Admin member status failure ['.$ref.'] member='.$id.': '.$e->getMessage());
            $this->fail('We could not update this member status. Please try again. Reference '.$ref.'.');
        }
        Notification::send($id,'Membership status updated',$status==='active'?'Your 97 Foundation membership has been approved and published. You can now sign in to the Member Portal.':($status==='rejected'?'Your membership registration was not approved. Contact the Foundation if you need clarification.':'Your membership status is now '.ucfirst($status).'.'),'info',$status==='active'?'member/login':'members');
        Session::flash('success',$status==='active'?'Member approved and published successfully.':'Member status updated successfully.');
        redirect('admin/members');
    }

    public function resetPassword():void
    {
        $this->requirePost();$admin=$this->guard();$id=(int)($_POST['id']??0);$new=(string)($_POST['new_password']??'');$confirm=(string)($_POST['password_confirmation']??'');
        if(!$this->passwordValid($new)||$new!==$confirm)$this->fail('Reset password must be 8–12 characters and both entries must match.','admin/members/view/'.$id);
        try{
            $changed=Database::exec('UPDATE users SET password_hash=? WHERE id=? AND role="member"',[password_hash($new,PASSWORD_ARGON2ID),$id]);
            if($changed<1 && !Database::one('SELECT id FROM users WHERE id=? AND role="member"',[$id]))throw new \RuntimeException('Member not found.');
            Audit::write((int)$admin['id'],'MEMBER_PASSWORD_RESET','members',$id,'Member password reset by administrator');
        }catch(\Throwable $e){
            $ref=strtoupper(substr(bin2hex(random_bytes(8)),0,10));error_log('Member password reset failure ['.$ref.']: '.$e->getMessage());
            $this->fail('We could not reset the member password. Reference '.$ref.'.','admin/members/view/'.$id);
        }
        Notification::send($id,'Password reset','Your member portal password was reset by an authorized administrator.','warning','member/login');
        Session::flash('success','Member password reset securely.');redirect('admin/members/view/'.$id);
    }

    public function details(string $id):void
    {
        $u=$this->guard();$mid=(int)$id;
        $m=Database::one('SELECT u.*,m.mobile,m.address,m.member_number,m.joined_at,m.role_title,m.bio,m.verification_token,m.is_public,m.date_of_birth,m.nic_number,m.gender,m.approved_at,m.rejected_at,m.rejection_reason FROM users u LEFT JOIN member_profiles m ON m.user_id=u.id WHERE u.id=? AND u.role="member" LIMIT 1',[$mid]);
        if(!$m){http_response_code(404);exit('Member not found');}
        $this->view('admin/member-details',['user'=>$u,'member'=>$m,'adminTitle'=>'Member Profile','adminPage'=>'member-details'],'admin');
    }

    public function delete():void
    {
        $this->requirePost();$u=$this->guard();if($u['role']!=='super_admin'){http_response_code(403);exit('Forbidden');}$id=(int)($_POST['id']??0);
        try{Database::exec('DELETE FROM users WHERE id=? AND role="member"',[$id]);Audit::write((int)$u['id'],'DELETE','members',$id,'Foundation member deleted');}
        catch(\Throwable $e){$ref=strtoupper(substr(bin2hex(random_bytes(8)),0,10));error_log('Member delete failure ['.$ref.']: '.$e->getMessage());$this->fail('We could not delete this member. Reference '.$ref.'.');}
        Session::flash('success','Member deleted.');redirect('admin/members');
    }
}
