<?php
namespace App\Controllers\Admin;
use App\Core\Controller;use App\Core\Auth;use App\Core\Database;use App\Core\Session;use App\Core\Audit;use App\Core\Upload;
final class DonationAdminController extends Controller
{
    public function index(): void
    {
        $u=Auth::requireAdmin();Auth::enforce('view_donations');
        $status=$_GET['status']??'';$q=substr(trim($_GET['q']??''),0,120);$from=trim($_GET['from']??'');$to=trim($_GET['to']??'');$per=(int)($_GET['per_page']??25);$per=in_array($per,[10,20,25,50,100],true)?$per:25;$params=[];$where='1=1';
        if(in_array($status,['pending_verification','approved','rejected','refunded','cancelled'],true)){$where.=' AND d.status=?';$params[]=$status;}
        if($q!==''){$like='%'.$q.'%';$where.=' AND (d.public_id LIKE ? OR d.donor_name LIKE ? OR d.donor_email LIKE ? OR d.donor_phone LIKE ? OR c.title LIKE ? OR da.title LIKE ? OR d.transaction_reference LIKE ?)';array_push($params,$like,$like,$like,$like,$like,$like,$like);}
        if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$from)){$where.=' AND d.created_at>=?';$params[]=$from.' 00:00:00';}else{$from='';}
        if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$to)){$where.=' AND d.created_at<=?';$params[]=$to.' 23:59:59';}else{$to='';}
        $total=(int)(Database::one('SELECT COUNT(*) c FROM donations d LEFT JOIN campaigns c ON c.id=d.campaign_id LEFT JOIN donation_accounts da ON da.id=d.donation_account_id WHERE '.$where,$params)['c']??0);$pagination=admin_pager($total,$per);
        $rows=Database::all('SELECT d.*,c.title campaign_title,da.title account_title,da.bank_name account_bank FROM donations d LEFT JOIN campaigns c ON c.id=d.campaign_id LEFT JOIN donation_accounts da ON da.id=d.donation_account_id WHERE '.$where.' ORDER BY CASE WHEN d.status="pending_verification" THEN 0 ELSE 1 END,d.id DESC LIMIT '.(int)$pagination['per_page'].' OFFSET '.(int)$pagination['offset'],$params);
        $counts=Database::one('SELECT COUNT(*) total,SUM(status="pending_verification") pending,SUM(status="approved") approved,SUM(status="rejected") rejected,SUM(status="refunded") refunded,SUM(status="cancelled") cancelled FROM donations')?:[];
        $this->view('admin/donations',['user'=>$u,'donations'=>$rows,'status'=>$status,'q'=>$q,'from'=>$from,'to'=>$to,'perPage'=>$per,'counts'=>$counts,'pagination'=>$pagination,'adminTitle'=>'Donation Verification'],'admin');
    }
    public function create():void
    {
        $u=Auth::requireAdmin();Auth::enforce('approve_donations');$this->editor($u,null,'Create Donation');
    }
    public function edit(string $id):void
    {
        $u=Auth::requireAdmin();Auth::enforce('approve_donations');$row=Database::one('SELECT * FROM donations WHERE id=? LIMIT 1',[(int)$id]);if(!$row){http_response_code(404);exit('Donation not found.');}$this->editor($u,$row,'Edit Donation');
    }
    private function editor(array $u,?array $row,string $title):void
    {
        $campaigns=Database::all('SELECT id,title,status,is_active FROM campaigns ORDER BY title');$accounts=Database::all('SELECT id,title,bank_name,currency,is_active FROM donation_accounts ORDER BY sort_order,id');
        $this->view('admin/donation-form',['user'=>$u,'donation'=>$row,'campaigns'=>$campaigns,'accounts'=>$accounts,'adminTitle'=>$title],'admin');
    }
    public function details(string $id):void
    {
        $u=Auth::requireAdmin();Auth::enforce('view_donations');$row=Database::one('SELECT d.*,c.title campaign_title,da.title account_title,da.bank_name,da.account_name,da.account_number,u.name verified_by_name FROM donations d LEFT JOIN campaigns c ON c.id=d.campaign_id LEFT JOIN donation_accounts da ON da.id=d.donation_account_id LEFT JOIN users u ON u.id=d.verified_by WHERE d.id=? LIMIT 1',[(int)$id]);if(!$row){http_response_code(404);exit('Donation not found.');}
        $history=Database::all('SELECT dv.*,u.name admin_name FROM donation_verifications dv JOIN users u ON u.id=dv.admin_id WHERE dv.donation_id=? ORDER BY dv.id DESC',[(int)$id]);
        $this->view('admin/donation-view',['user'=>$u,'donation'=>$row,'history'=>$history,'adminTitle'=>'Donation Details'],'admin');
    }
    public function save():void
    {
        $this->requirePost();$u=Auth::requireAdmin();Auth::enforce('approve_donations');$id=(int)($_POST['id']??0);
        $name=substr(trim($_POST['donor_name']??''),0,160);$email=strtolower(substr(trim($_POST['donor_email']??''),0,190));$phone=substr(trim($_POST['donor_phone']??''),0,60);$amount=(float)($_POST['amount']??0);$currency='LKR';
        if(strlen($name)<2||$amount<=0||$amount>10000000||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))){Session::flash('error','Enter a valid donor name, email and donation amount.');$this->returnEditor($id);}
        $campaign=(int)($_POST['campaign_id']??0);$account=(int)($_POST['donation_account_id']??0);if($campaign&&!Database::one('SELECT id FROM campaigns WHERE id=?',[$campaign]))$campaign=0;if($account&&!Database::one('SELECT id FROM donation_accounts WHERE id=?',[$account]))$account=0;
        $method=$_POST['payment_method']??'bank_transfer';if(!in_array($method,['bank_transfer','cash','other'],true))$method='bank_transfer';$status=$_POST['status']??'pending_verification';if(!in_array($status,['pending_verification','approved','rejected','refunded','cancelled'],true))$status='pending_verification';
        $txn=substr(trim($_POST['transaction_reference']??''),0,140)?:null;$message=substr(trim($_POST['message']??''),0,4000)?:null;$note=substr(trim($_POST['internal_note']??''),0,4000)?:null;$anonymous=isset($_POST['is_anonymous'])?1:0;
        $old=$id?Database::one('SELECT * FROM donations WHERE id=? LIMIT 1',[$id]):null;if($id&&!$old){Session::flash('error','Donation not found.');redirect('admin/donations');}
        $proof=$old['proof_path']??null;$original=$old['proof_original_name']??null;$mime=$old['proof_mime']??null;$oldProof=$proof;
        try{if(isset($_FILES['proof'])&&($_FILES['proof']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){$proof=Upload::donationProof($_FILES['proof']);$original=basename((string)$_FILES['proof']['name']);$mime=(new \finfo(FILEINFO_MIME_TYPE))->file(dirname(__DIR__,3).'/storage/private/donation-proofs/'.$proof);}}
        catch(\Throwable $e){Session::flash('error',$e->getMessage());$this->returnEditor($id);}
        if($id){
            Database::transaction(function()use($id,$old,$campaign,$account,$name,$email,$phone,$amount,$currency,$method,$txn,$message,$anonymous,$proof,$original,$mime,$status,$note,$u){$verifiedBy=$status==='pending_verification'?null:(int)$u['id'];$verifiedAt=$status==='pending_verification'?null:date('Y-m-d H:i:s');Database::exec('UPDATE donations SET campaign_id=?,donation_account_id=?,donor_name=?,donor_email=?,donor_phone=?,amount=?,currency=?,payment_method=?,transaction_reference=?,message=?,is_anonymous=?,proof_path=?,proof_original_name=?,proof_mime=?,status=?,verified_by=?,verified_at=?,internal_note=? WHERE id=?',[$campaign?:null,$account?:null,$name,$email?:null,$phone?:null,$amount,$currency,$method,$txn,$message,$anonymous,$proof,$original,$mime,$status,$verifiedBy,$verifiedAt,$note,$id]);if($old['status']!==$status)Database::insert('INSERT INTO donation_verifications(donation_id,admin_id,old_status,new_status,note) VALUES(?,?,?,?,?)',[$id,(int)$u['id'],$old['status'],$status,'Status changed from donation editor']);Audit::write((int)$u['id'],'DONATION_UPDATED','donations',$id,'Donation record updated');});
        }else{
            $public=self::uuid();$verifiedBy=$status==='pending_verification'?null:(int)$u['id'];$verifiedAt=$status==='pending_verification'?null:date('Y-m-d H:i:s');$id=Database::insert('INSERT INTO donations(public_id,campaign_id,donation_account_id,donor_name,donor_email,donor_phone,amount,currency,payment_method,transaction_reference,message,is_anonymous,proof_path,proof_original_name,proof_mime,status,verified_by,verified_at,internal_note) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[$public,$campaign?:null,$account?:null,$name,$email?:null,$phone?:null,$amount,$currency,$method,$txn,$message,$anonymous,$proof,$original,$mime,$status,$verifiedBy,$verifiedAt,$note]);if($status!=='pending_verification')Database::insert('INSERT INTO donation_verifications(donation_id,admin_id,old_status,new_status,note) VALUES(?,?,?,?,?)',[$id,(int)$u['id'],null,$status,'Donation created by administrator']);Audit::write((int)$u['id'],'DONATION_CREATED','donations',$id,'Donation record created by administrator');
        }
        if($oldProof&&$proof!==$oldProof)$this->removeProof($oldProof);Session::flash('success',$old?'Donation updated successfully.':'Donation created successfully.');redirect('admin/donations/view/'.$id);
    }
    private function returnEditor(int $id):never{redirect($id?'admin/donations/edit/'.$id:'admin/donations/create');}
    public function delete():void
    {
        $this->requirePost();$u=Auth::requireAdmin();if(($u['role']??'')!=='super_admin'){http_response_code(403);exit('Only a Super Admin can permanently delete donation records.');}$id=(int)($_POST['id']??0);$row=Database::one('SELECT public_id,proof_path FROM donations WHERE id=? LIMIT 1',[$id]);if(!$row){Session::flash('error','Donation not found.');redirect('admin/donations');}Audit::write((int)$u['id'],'DONATION_DELETED','donations',$id,'Deleted donation '.$row['public_id']);Database::exec('DELETE FROM donations WHERE id=?',[$id]);if($row['proof_path'])$this->removeProof($row['proof_path']);Session::flash('success','Donation deleted permanently.');redirect('admin/donations');
    }
    public function status(): void
    {
        $this->requirePost();$u=Auth::requireAdmin();Auth::enforce('approve_donations');$id=(int)($_POST['id']??0);$new=$_POST['status']??'';if(!in_array($new,['approved','rejected','refunded','cancelled'],true)){Session::flash('error','Invalid status.');redirect('admin/donations');}
        Database::transaction(function()use($id,$new,$u){$d=Database::one('SELECT * FROM donations WHERE id=? FOR UPDATE',[$id]);if(!$d) throw new \RuntimeException('Donation not found.');if($d['status']===$new)return;Database::exec('UPDATE donations SET status=?,verified_by=?,verified_at=NOW(),rejection_reason=? WHERE id=?',[$new,(int)$u['id'],$new==='rejected'?substr(trim($_POST['note']??''),0,500):null,$id]);Database::insert('INSERT INTO donation_verifications(donation_id,admin_id,old_status,new_status,note) VALUES(?,?,?,?,?)',[$id,(int)$u['id'],$d['status'],$new,substr(trim($_POST['note']??''),0,4000)?:null]);Audit::write((int)$u['id'],strtoupper('DONATION_'.$new),'donations',$id,'Donation status changed from '.$d['status'].' to '.$new);});Session::flash('success','Donation status updated. Only approved donations count toward public totals.');redirect('admin/donations');
    }
    public function proof(string $id): void
    {
        $u=Auth::requireAdmin();Auth::enforce('view_donations');$d=Database::one('SELECT proof_path,proof_original_name,proof_mime FROM donations WHERE id=?',[(int)$id]);if(!$d||!$d['proof_path']){http_response_code(404);exit('Proof not found');}$base=realpath(dirname(__DIR__,3).'/storage/private/donation-proofs');$path=$base?realpath($base.'/'.$d['proof_path']):false;if(!$base||!$path||!str_starts_with($path,$base.DIRECTORY_SEPARATOR)){http_response_code(403);exit('Invalid file');}header('Content-Type: '.($d['proof_mime']?:'application/octet-stream'));header('Content-Disposition: inline; filename="'.preg_replace('/[^A-Za-z0-9._-]/','_',basename($d['proof_original_name']?:'proof')).'"');header('X-Content-Type-Options: nosniff');header('Content-Security-Policy: sandbox');readfile($path);exit;
    }
    private function removeProof(string $file):void{$name=basename($file);$path=dirname(__DIR__,3).'/storage/private/donation-proofs/'.$name;if(is_file($path))@unlink($path);}
    private static function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
