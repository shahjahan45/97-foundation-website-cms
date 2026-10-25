<?php
namespace App\Payments\Gateways;

use App\Payments\GatewayInterface;
use App\Payments\PaymentConfig;
use App\Payments\HttpClient;
use RuntimeException;

final class PayHereGateway implements GatewayInterface
{
    private array $config;
    public function __construct(?array $config=null){$this->config=$config??PaymentConfig::runtime();}
    public function key(): string{return 'payhere';}
    public function displayName(): string{return 'PayHere';}
    public function checkoutUrl(): string{return $this->config['mode']==='live'?'https://www.payhere.lk/pay/checkout':'https://sandbox.payhere.lk/pay/checkout';}
    private function base():string{return $this->config['mode']==='live'?'https://www.payhere.lk':'https://sandbox.payhere.lk';}
    private function requireCheckout():void{if(trim((string)$this->config['merchant_id'])===''||trim((string)$this->config['merchant_secret'])==='')throw new RuntimeException('PayHere Merchant ID and Merchant Secret are not configured.');}
    private function amount(float $v):string{return number_format($v,2,'.','');}
    private function splitName(string $name):array{$parts=preg_split('/\s+/',trim($name))?:[];$first=array_shift($parts)?:'Donor';$last=trim(implode(' ',$parts));return [$first,$last!==''?$last:'Supporter'];}
    public function buildCheckoutPayload(array $tx): array
    {
        $this->requireCheckout();[$first,$last]=$this->splitName((string)$tx['donor_name']);$amount=$this->amount((float)$tx['amount']);$currency='LKR';
        $hash=strtoupper(md5($this->config['merchant_id'].$tx['gateway_order_id'].$amount.$currency.strtoupper(md5($this->config['merchant_secret']))));
        $return=absolute_url('donate/payment/return?ref='.rawurlencode($tx['public_id']));$cancel=absolute_url('donate/payment/cancel?ref='.rawurlencode($tx['public_id']));$notify=absolute_url('payments/payhere/notify');
        if($this->config['mode']==='live'){foreach([$return,$cancel,$notify] as $u)if(!str_starts_with(strtolower($u),'https://'))throw new RuntimeException('Live PayHere checkout requires HTTPS return, cancel and callback URLs.');}
        return [
            'merchant_id'=>$this->config['merchant_id'],'return_url'=>$return,
            'cancel_url'=>$cancel,'notify_url'=>$notify,
            'first_name'=>$first,'last_name'=>$last,'email'=>$tx['donor_email']?:(filter_var(setting('foundation','email',''),FILTER_VALIDATE_EMAIL)?setting('foundation','email',''):'info@97foundation.org'),'phone'=>$tx['donor_phone']?:'+94700000000',
            'address'=>$tx['donor_address']?:'Sri Lanka','city'=>$tx['donor_city']?:'Polonnaruwa','country'=>'Sri Lanka','order_id'=>$tx['gateway_order_id'],
            'items'=>'97 Foundation Donation','currency'=>$currency,'amount'=>$amount,'custom_1'=>$tx['public_id'],'custom_2'=>(string)($tx['campaign_id']??''),'hash'=>$hash
        ];
    }
    public function verifyCallback(array $p,array $tx):array
    {
        $this->requireCheckout();$required=['merchant_id','order_id','payhere_amount','payhere_currency','status_code','md5sig'];foreach($required as $k)if(!isset($p[$k]))return ['valid'=>false,'reason'=>'Missing callback field: '.$k];
        if(!hash_equals((string)$this->config['merchant_id'],(string)$p['merchant_id']))return ['valid'=>false,'reason'=>'Merchant ID mismatch'];
        if(!hash_equals((string)$tx['gateway_order_id'],(string)$p['order_id']))return ['valid'=>false,'reason'=>'Order reference mismatch'];
        if(strtoupper((string)$p['payhere_currency'])!=='LKR')return ['valid'=>false,'reason'=>'Currency mismatch'];
        if(abs((float)$p['payhere_amount']-(float)$tx['amount'])>0.009)return ['valid'=>false,'reason'=>'Amount mismatch'];
        $local=strtoupper(md5($p['merchant_id'].$p['order_id'].$p['payhere_amount'].$p['payhere_currency'].$p['status_code'].strtoupper(md5($this->config['merchant_secret']))));
        if(!hash_equals($local,strtoupper((string)$p['md5sig'])))return ['valid'=>false,'reason'=>'Invalid callback signature'];
        $code=(string)$p['status_code'];$status=match($code){'2'=>'successful','0'=>'processing','-1'=>'cancelled','-2'=>'failed','-3'=>'refunded',default=>'failed'};
        return ['valid'=>true,'status'=>$status,'status_code'=>$code,'payment_id'=>substr((string)($p['payment_id']??''),0,120),'method'=>substr((string)($p['method']??$p['payment_method']??''),0,80),'chargeback'=>$code==='-3'];
    }
    private function token():string
    {
        $id=trim((string)$this->config['api_app_id']);$secret=trim((string)$this->config['api_app_secret']);if($id===''||$secret==='')throw new RuntimeException('PayHere API App ID and App Secret are required for status/refund API operations.');
        $auth=base64_encode($id.':'.$secret);$r=HttpClient::request('POST',$this->base().'/merchant/v1/oauth/token',['Authorization: Basic '.$auth,'Content-Type: application/x-www-form-urlencoded'],'grant_type=client_credentials');$j=json_decode($r['body'],true);if($r['status']<200||$r['status']>=300||!is_array($j)||empty($j['access_token']))throw new RuntimeException('PayHere OAuth connection failed.');return (string)$j['access_token'];
    }
    public function testConnection():array
    {
        try{$this->requireCheckout();if(trim((string)$this->config['api_app_id'])!==''&&trim((string)$this->config['api_app_secret'])!==''){$this->token();return ['ok'=>true,'message'=>'PayHere merchant checkout credentials are present and the Merchant API OAuth connection succeeded in '.strtoupper($this->config['mode']).' mode.'];}return ['ok'=>true,'message'=>'PayHere checkout credentials are configured. Add API App ID/Secret to test Retrieval/Refund API connectivity.'];}catch(\Throwable $e){return ['ok'=>false,'message'=>$e->getMessage()];}
    }
    public function retrieve(string $orderId):array
    {
        $token=$this->token();$r=HttpClient::request('GET',$this->base().'/merchant/v1/payment/search?'.http_build_query(['order_id'=>$orderId]),['Authorization: Bearer '.$token,'Content-Type: application/json']);$j=json_decode($r['body'],true);if(!is_array($j))throw new RuntimeException('Invalid PayHere retrieval response.');return $j;
    }
    public function refund(string $paymentId,?float $amount,string $description):array
    {
        $token=$this->token();$body=['payment_id'=>$paymentId,'description'=>substr($description,0,240)];if($amount!==null&&$amount>0)$body['amount']=$this->amount($amount);$r=HttpClient::request('POST',$this->base().'/merchant/v1/payment/refund',['Authorization: Bearer '.$token,'Content-Type: application/json'],json_encode($body,JSON_UNESCAPED_SLASHES));$j=json_decode($r['body'],true);if(!is_array($j))throw new RuntimeException('Invalid PayHere refund response.');return $j;
    }
}
