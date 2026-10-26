<?php
namespace App\Payments;

use App\Core\Database;
use App\Core\Audit;
use App\Core\Notification;
use App\Core\Session;
use RuntimeException;

final class PaymentService
{
    public static function create(array $input):array
    {
        $cfg=PaymentConfig::public();if(!$cfg['enabled']||!PaymentConfig::configured())throw new RuntimeException('Online payments are currently unavailable. Please use bank transfer or contact the Foundation.');
        $amount=(float)$input['amount'];if($amount<$cfg['min_amount']||$amount>$cfg['max_amount'])throw new RuntimeException('Donation amount must be between LKR '.number_format($cfg['min_amount'],2).' and LKR '.number_format($cfg['max_amount'],2).'.');
        $name=trim((string)$input['donor_name']);$email=strtolower(trim((string)$input['donor_email']));$phone=trim((string)$input['donor_phone']);if(mb_strlen($name)<2)throw new RuntimeException('Please enter your full name.');if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Please enter a valid email address.');if(mb_strlen($phone)<7)throw new RuntimeException('Please enter a valid contact number.');
        $campaign=(int)($input['campaign_id']??0);
        // V7.2 fundraising switch: never start a gateway payment for a paused or closed campaign.
        if(!\App\Core\Fundraising::donationsOpen())throw new \RuntimeException(\App\Core\Fundraising::pausedMessage());
        if($campaign){$row=Database::one('SELECT id,status,is_active,is_visible FROM campaigns WHERE id=? LIMIT 1',[$campaign]);if(!\App\Core\Fundraising::campaignOpen($row))throw new \RuntimeException(\App\Core\Fundraising::enabled()?'This campaign is not accepting donations right now. Choose the General Foundation Fund or another active campaign.':\App\Core\Fundraising::pausedMessage());}
        elseif(!\App\Core\Fundraising::generalDonationsOpen())throw new \RuntimeException(\App\Core\Fundraising::pausedMessage());
        $public=self::publicId();$order='97D-'.date('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(7)),0,12));$preference=$input['payment_preference']??'any';if(!in_array($preference,['any','card','wallet','banking'],true))$preference='any';
        $id=Database::insert('INSERT INTO payment_transactions(public_id,gateway,gateway_order_id,status,amount,currency,donor_name,donor_email,donor_phone,donor_address,donor_city,donor_country,campaign_id,payment_method_preference,created_at,updated_at) VALUES(?,?,?,"pending",?,?,?,?,?,?,?,?,?,?,NOW(),NOW())',[$public,'payhere',$order,$amount,'LKR',$name,$email?:null,$phone,substr(trim((string)($input['donor_address']??'')),0,500)?:null,substr(trim((string)($input['donor_city']??'Polonnaruwa')),0,120)?:'Polonnaruwa','Sri Lanka',$campaign?:null,$preference]);
        $token=bin2hex(random_bytes(24));Session::put('payment.'.$public,hash('sha256',$token));
        $tx=self::byPublic($public);$tx['session_token']=$token;Audit::write(null,'ONLINE_DONATION_STARTED','payment_transactions',$id,'Online donation checkout initiated: '.$public);return $tx;
    }
    public static function own(array $tx,string $token=''):bool{$stored=(string)Session::get('payment.'.$tx['public_id'],'');return $stored!==''&&$token!==''&&hash_equals($stored,hash('sha256',$token));}
    public static function byPublic(string $public):?array{return Database::one('SELECT pt.*,c.title campaign_title FROM payment_transactions pt LEFT JOIN campaigns c ON c.id=pt.campaign_id WHERE pt.public_id=? LIMIT 1',[$public]);}
    public static function callback(array $payload):array
    {
        $order=substr(trim((string)($payload['order_id']??'')),0,100);if($order==='')throw new RuntimeException('Missing order reference.');
        return Database::transaction(function()use($payload,$order){$tx=Database::one('SELECT * FROM payment_transactions WHERE gateway_order_id=? FOR UPDATE',[$order]);if(!$tx)throw new RuntimeException('Payment transaction not found.');$gateway=PaymentGatewayFactory::make($tx['gateway']);$result=$gateway->verifyCallback($payload,$tx);$safe=['merchant_id'=>$payload['merchant_id']??'','order_id'=>$payload['order_id']??'','payment_id'=>$payload['payment_id']??'','payhere_amount'=>$payload['payhere_amount']??'','payhere_currency'=>$payload['payhere_currency']??'','status_code'=>$payload['status_code']??''];$eventHash=hash('sha256',implode('|',$safe).'|'.($payload['md5sig']??''));if(Database::one('SELECT id FROM payment_webhook_events WHERE event_hash=? LIMIT 1',[$eventHash]))return ['duplicate'=>true,'valid'=>$result['valid'],'status'=>$tx['status']];Database::insert('INSERT INTO payment_webhook_events(gateway,event_hash,order_id,status_code,verified,payload_json,received_at) VALUES(?,?,?,?,?,?,NOW())',[$tx['gateway'],$eventHash,$order,substr((string)($payload['status_code']??''),0,20),$result['valid']?1:0,json_encode($safe,JSON_UNESCAPED_SLASHES)]);
            if(!$result['valid']){Audit::write(null,'PAYMENT_CALLBACK_REJECTED','payment_transactions',(int)$tx['id'],'Rejected payment callback for '.$tx['public_id'].': '.$result['reason']);return $result;}
            $new=$result['status'];$current=$tx['status'];if($current==='refunded'){$new='refunded';}elseif($current==='successful'&&!in_array($new,['successful','refunded'],true)){$new='successful';}
            $metadata=['verification'=>'payhere_callback_checksum','status_code'=>$result['status_code'],'chargeback'=>(bool)($result['chargeback']??false)];
            Database::exec('UPDATE payment_transactions SET status=?,gateway_payment_id=COALESCE(NULLIF(?,""),gateway_payment_id),gateway_status_code=?,payment_method=COALESCE(NULLIF(?,""),payment_method),callback_received_at=NOW(),callback_signature_hash=?,metadata_json=?,paid_at=IF(?="successful",COALESCE(paid_at,NOW()),paid_at),failed_at=IF(?="failed",COALESCE(failed_at,NOW()),failed_at),cancelled_at=IF(?="cancelled",COALESCE(cancelled_at,NOW()),cancelled_at),refunded_at=IF(?="refunded",COALESCE(refunded_at,NOW()),refunded_at),last_verified_at=NOW(),updated_at=NOW() WHERE id=?',[$new,$result['payment_id'],$result['status_code'],$result['method'],hash('sha256',(string)($payload['md5sig']??'')),json_encode($metadata,JSON_UNESCAPED_SLASHES),$new,$new,$new,$new,(int)$tx['id']]);
            if($new==='successful'&&!$tx['donation_id'])self::finalizeSuccess((int)$tx['id']);if($new==='refunded'&&$tx['donation_id'])Database::exec('UPDATE donations SET status="refunded",verified_at=NOW(),internal_note=CONCAT(COALESCE(internal_note,""),"\nPayment gateway reported refund/chargeback.") WHERE id=?',[(int)$tx['donation_id']]);
            Audit::write(null,'PAYMENT_CALLBACK_VERIFIED','payment_transactions',(int)$tx['id'],'Verified '.$tx['gateway'].' callback: '.$tx['public_id'].' -> '.$new);return array_merge($result,['status'=>$new]);});
    }
    private static function finalizeSuccess(int $id):void
    {
        $tx=Database::one('SELECT * FROM payment_transactions WHERE id=? FOR UPDATE',[$id]);if(!$tx||$tx['donation_id'])return;$receipt=PaymentConfig::public()['receipt_prefix'].'-'.date('Ymd').'-'.str_pad((string)$id,6,'0',STR_PAD_LEFT);$donationPublic=self::uuid();
        $donationId=Database::insert('INSERT INTO donations(public_id,campaign_id,donor_name,donor_email,donor_phone,amount,currency,payment_method,transaction_reference,message,is_anonymous,status,verified_at,internal_note) VALUES(?,?,?,?,?,?,"LKR","online_payment",?,?,0,"approved",NOW(),?)',[$donationPublic,$tx['campaign_id']?:null,$tx['donor_name'],$tx['donor_email'],$tx['donor_phone'],$tx['amount'],$tx['gateway_payment_id']?:$tx['gateway_order_id'],'Online donation via '.ucfirst($tx['gateway']),'Automatically verified by secure payment gateway callback checksum.']);
        Database::exec('UPDATE payment_transactions SET donation_id=?,receipt_number=?,updated_at=NOW() WHERE id=?',[$donationId,$receipt,$id]);
        Notification::admins('Online donation received',$tx['donor_name'].' donated '.money($tx['amount'],'LKR').' online. Reference '.$tx['public_id'].'.','donation','admin/online-payments/view/'.$id);Audit::write(null,'ONLINE_DONATION_SUCCESS','payment_transactions',$id,'Verified online donation '.$tx['public_id'].' receipt '.$receipt);
        $tx['receipt_number']=$receipt;PaymentMailer::receipt($tx);
    }
    public static function markCancelled(string $public):void{Database::exec('UPDATE payment_transactions SET status="cancelled",cancelled_at=COALESCE(cancelled_at,NOW()),updated_at=NOW() WHERE public_id=? AND status IN("pending","processing")',[$public]);}
    public static function sync(int $id):array
    {
        $tx=Database::one('SELECT * FROM payment_transactions WHERE id=?',[$id]);
        if(!$tx) throw new RuntimeException('Payment not found.');
        $gateway=PaymentGatewayFactory::make($tx['gateway']);$r=$gateway->retrieve($tx['gateway_order_id']);
        if((int)($r['status']??-99)===1&&!empty($r['data'])&&is_array($r['data'])){
            $match=null;
            foreach($r['data'] as $candidate){
                $remoteCurrency=strtoupper((string)($candidate['amount_detail']['currency']??$candidate['currency']??''));
                $remoteAmount=(float)($candidate['amount_detail']['gross']??$candidate['amount']??0);
                if($remoteCurrency==='LKR'&&abs($remoteAmount-(float)$tx['amount'])<0.01){$match=$candidate;break;}
            }
            if(!$match) throw new RuntimeException('Gateway status response did not match the original LKR amount. No local status was changed.');
            $remote=strtoupper((string)($match['status']??''));
            $status=match($remote){'RECEIVED'=>'successful','REFUNDED','CHARGEBACKED'=>'refunded','REFUND REQUESTED','REFUND PROCESSING'=>'processing',default=>$tx['status']};
            Database::exec('UPDATE payment_transactions SET status=?,gateway_payment_id=COALESCE(NULLIF(?,""),gateway_payment_id),payment_method=COALESCE(NULLIF(?,""),payment_method),last_verified_at=NOW(),refunded_at=IF(?="refunded",COALESCE(refunded_at,NOW()),refunded_at),updated_at=NOW() WHERE id=?',[$status,(string)($match['payment_id']??''),(string)($match['payment_method']['method']??''),$status,$id]);
            if($status==='successful'&&!$tx['donation_id']) Database::transaction(fn()=>self::finalizeSuccess($id));
            if($status==='refunded'&&$tx['donation_id']) Database::exec('UPDATE donations SET status="refunded",verified_at=NOW(),internal_note=CONCAT(COALESCE(internal_note,""),"\nGateway status synchronized as refunded/chargebacked.") WHERE id=?',[(int)$tx['donation_id']]);
        }
        return $r;
    }
    public static function refund(int $id,?float $amount,string $description):array
    {
        $tx=Database::one('SELECT * FROM payment_transactions WHERE id=?',[$id]);if(!$tx||$tx['status']!=='successful'||!$tx['gateway_payment_id'])throw new RuntimeException('Only successful gateway payments with a payment ID can be refunded.');if($amount!==null&&($amount<=0||$amount>(float)$tx['amount']))throw new RuntimeException('Partial refund amount must be greater than zero and cannot exceed the original payment.');$gateway=PaymentGatewayFactory::make($tx['gateway']);$r=$gateway->refund($tx['gateway_payment_id'],$amount,$description);if((int)($r['status']??0)===1){Database::exec('UPDATE payment_transactions SET status="processing",metadata_json=?,updated_at=NOW() WHERE id=?',[json_encode(['refund_requested'=>true,'refund_reference'=>$r['data']??null,'description'=>$description,'amount'=>$amount],JSON_UNESCAPED_SLASHES),$id]);Audit::write((int)(\App\Core\Auth::user()['id']??0),'PAYMENT_REFUND_REQUESTED','payment_transactions',$id,'Refund requested for '.$tx['public_id']);}return $r;
    }
    private static function publicId():string{return '97P-'.date('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(10)),0,16));}
    private static function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
