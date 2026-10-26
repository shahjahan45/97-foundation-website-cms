<?php
namespace App\Controllers;
use App\Core\Controller;use App\Core\Database;use App\Core\Session;use App\Core\Upload;use App\Core\Audit;use App\Core\Notification;use App\Core\Site;use App\Core\Fundraising;use App\Payments\PaymentConfig;
final class DonationController
{
    public function index(): void
    {
        $accounts=Database::all('SELECT * FROM donation_accounts WHERE is_active=1 ORDER BY sort_order,id');
        $campaigns=Fundraising::enabled()?Database::all('SELECT id,title,target_amount,currency FROM campaigns WHERE status="active" AND is_active=1 AND is_visible=1 ORDER BY title'):[];
        $instructions=Database::one('SELECT setting_value FROM settings WHERE setting_group="donation" AND setting_key="instructions"');
        $selected=(int)($_GET['campaign']??0);$page=Site::page('donate');
        \App\Core\View::render('donate',['page'=>$page,'accounts'=>$accounts,'campaigns'=>$campaigns,'instructions'=>$instructions['setting_value']??'','selectedCampaign'=>$selected,'paymentConfig'=>PaymentConfig::public(),'settings'=>Site::settings(),'fundraisingOn'=>Fundraising::enabled(),'donationsOpen'=>Fundraising::donationsOpen(),'pausedMessage'=>Fundraising::pausedMessage(),'receiptMaxBytes'=>Upload::receiptMaxBytes(),'old'=>is_array($o=flash('donation_old'))?$o:[],'seo'=>['title'=>$page['meta_title']??'Donate | 97 Foundation','description'=>$page['meta_description']??'Donate securely to 97 Foundation. View administrator-managed account details and submit your payment receipt for verification.']],'site');
    }

    public function store(): void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);exit('Method Not Allowed');}\App\Core\Csrf::enforce();
        $name=trim($_POST['donor_name']??'');$email=strtolower(trim($_POST['donor_email']??''));$phone=trim($_POST['donor_phone']??'');$amount=(float)($_POST['amount']??0);$campaign=(int)($_POST['campaign_id']??0);$account=(int)($_POST['donation_account_id']??0);
        $back=function(string $message)use($campaign):never{
            // Keep what the donor typed so a correction never means starting again (the file must be chosen again).
            $keep=[];foreach(['donor_name','donor_email','donor_phone','amount','campaign_id','donation_account_id','payment_method','transaction_reference','message'] as $k)$keep[$k]=mb_substr(trim((string)($_POST[$k]??'')),0,4000);
            $keep['is_anonymous']=isset($_POST['is_anonymous'])?'1':'';
            Session::flash('donation_old',$keep);Session::flash('error',$message);redirect('donate'.($campaign?'?campaign='.$campaign:''));
        };
        if(!Fundraising::donationsOpen())$back(Fundraising::pausedMessage());
        if(strlen($name)<2||$amount<=0||($email&&!filter_var($email,FILTER_VALIDATE_EMAIL)))$back('Please enter a valid donor name, amount and email address.');
        if($amount>10000000)$back('Donation amount is outside the accepted range.');
        if($campaign){
            $row=Database::one('SELECT id,title,status,is_active,is_visible FROM campaigns WHERE id=? LIMIT 1',[$campaign]);
            if(!Fundraising::campaignOpen($row))$back(Fundraising::enabled()?'This campaign is not accepting donations right now. Choose the General Foundation Fund or another active campaign.':Fundraising::pausedMessage());
        }elseif(!Fundraising::generalDonationsOpen()){$back(Fundraising::pausedMessage());}
        if($account && !Database::one('SELECT id FROM donation_accounts WHERE id=? AND is_active=1',[$account])){$account=0;}
        $method=$_POST['payment_method']??'bank_transfer';if(!in_array($method,['bank_transfer','cash','other'],true))$method='bank_transfer';
        // V7.2: a payment receipt is mandatory for every manual donation submission.
        // Submitting a receipt does not verify the payment — an administrator still approves or rejects it.
        if(!isset($_FILES['proof'])||(int)($_FILES['proof']['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)$back('Upload your payment receipt (JPG, PNG, WEBP or PDF) to submit this donation.');
        try{$proof=Upload::donationProof($_FILES['proof']);$original=mb_substr(basename((string)$_FILES['proof']['name']),0,200);$mime=(new \finfo(FILEINFO_MIME_TYPE))->file(dirname(__DIR__,2).'/storage/private/donation-proofs/'.$proof);}
        catch(\Throwable $e){$back($e->getMessage());}
        $public=self::uuid();
        try{
            $id=Database::insert('INSERT INTO donations(public_id,campaign_id,donation_account_id,donor_name,donor_email,donor_phone,amount,currency,payment_method,transaction_reference,message,is_anonymous,proof_path,proof_original_name,proof_mime,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,"pending_verification")',[$public,$campaign?:null,$account?:null,$name,$email?:null,$phone?:null,$amount,'LKR',$method,substr(trim($_POST['transaction_reference']??''),0,140)?:null,substr(trim($_POST['message']??''),0,4000)?:null,isset($_POST['is_anonymous'])?1:0,$proof,$original,$mime]);
        }catch(\Throwable $e){
            // Never leave an orphaned private file behind if the record could not be saved.
            @unlink(dirname(__DIR__,2).'/storage/private/donation-proofs/'.basename($proof));error_log('Donation insert failed: '.$e->getMessage());
            $back('Your donation could not be saved. Please try again in a moment.');
        }
        Audit::write(null,'DONATION_SUBMITTED','donations',$id,'Donation '.$public.' submitted with receipt for verification');Notification::admins('Donation pending verification',$name.' submitted '.money($amount,'LKR').' with a payment receipt.','donation','admin/donations/view/'.$id);
        Session::flash('donation_done',['reference'=>$public,'amount'=>money($amount,'LKR')]);
        Session::flash('success','Thank you. Your donation reference is '.$public.'. Your receipt was received and is pending administrator verification.');redirect('donate');
    }
    private static function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
