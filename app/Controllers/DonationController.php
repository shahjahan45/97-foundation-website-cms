<?php
namespace App\Controllers;
use App\Core\Controller;use App\Core\Database;use App\Core\Session;use App\Core\Upload;use App\Core\Audit;use App\Core\Notification;use App\Core\Site;use App\Payments\PaymentConfig;
final class DonationController
{
    public function index(): void
    {
        $accounts=Database::all('SELECT * FROM donation_accounts WHERE is_active=1 ORDER BY sort_order,id');
        $campaigns=Database::all('SELECT id,title,target_amount,currency FROM campaigns WHERE status="active" AND is_active=1 AND is_visible=1 ORDER BY title');
        $instructions=Database::one('SELECT setting_value FROM settings WHERE setting_group="donation" AND setting_key="instructions"');
        $selected=(int)($_GET['campaign']??0);$page=Site::page('donate');
        \App\Core\View::render('donate',['page'=>$page,'accounts'=>$accounts,'campaigns'=>$campaigns,'instructions'=>$instructions['setting_value']??'','selectedCampaign'=>$selected,'paymentConfig'=>PaymentConfig::public(),'settings'=>Site::settings(),'seo'=>['title'=>$page['meta_title']??'Donate | 97 Foundation','description'=>$page['meta_description']??'Donate securely to 97 Foundation. View administrator-managed account details and submit payment proof for verification.']],'site');
    }
    public function store(): void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);exit('Method Not Allowed');}\App\Core\Csrf::enforce();
        $name=trim($_POST['donor_name']??'');$email=strtolower(trim($_POST['donor_email']??''));$phone=trim($_POST['donor_phone']??'');$amount=(float)($_POST['amount']??0);$campaign=(int)($_POST['campaign_id']??0);$account=(int)($_POST['donation_account_id']??0);
        if(strlen($name)<2||$amount<=0||($email&&!filter_var($email,FILTER_VALIDATE_EMAIL))){Session::flash('error','Please enter a valid donor name, amount and email address.');redirect('donate');}
        if($amount>10000000){Session::flash('error','Donation amount is outside the accepted range.');redirect('donate');}
        if($campaign && !Database::one('SELECT id FROM campaigns WHERE id=? AND status="active" AND is_active=1 AND is_visible=1',[$campaign])){$campaign=0;}
        if($account && !Database::one('SELECT id FROM donation_accounts WHERE id=? AND is_active=1',[$account])){$account=0;}
        $method=$_POST['payment_method']??'bank_transfer';if(!in_array($method,['bank_transfer','cash','other'],true))$method='bank_transfer';
        $proof=null;$original=null;$mime=null;
        try{if(isset($_FILES['proof'])&&($_FILES['proof']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){$proof=Upload::donationProof($_FILES['proof']);$original=basename((string)$_FILES['proof']['name']);$mime=(new \finfo(FILEINFO_MIME_TYPE))->file(dirname(__DIR__,2).'/storage/private/donation-proofs/'.$proof);}}
        catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect('donate');}
        $public=self::uuid();$id=Database::insert('INSERT INTO donations(public_id,campaign_id,donation_account_id,donor_name,donor_email,donor_phone,amount,currency,payment_method,transaction_reference,message,is_anonymous,proof_path,proof_original_name,proof_mime,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,"pending_verification")',[$public,$campaign?:null,$account?:null,$name,$email?:null,$phone?:null,$amount,'LKR',$method,substr(trim($_POST['transaction_reference']??''),0,140)?:null,substr(trim($_POST['message']??''),0,4000)?:null,isset($_POST['is_anonymous'])?1:0,$proof,$original,$mime]);
        Audit::write(null,'DONATION_SUBMITTED','donations',$id,'Donation '.$public.' submitted for verification');Notification::admins('Donation pending verification',$name.' submitted '.money($amount,'LKR').' for verification.','donation','admin/donations');
        Session::flash('success','Thank you. Your donation reference is '.$public.'. It is pending administrator verification.');redirect('donate');
    }
    private static function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
