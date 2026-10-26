<?php
namespace App\Controllers\Admin;

use App\Core\Controller;use App\Core\Auth;use App\Core\Database;use App\Core\Session;use App\Core\Audit;
use App\Payments\PaymentConfig;use App\Payments\PaymentSecretVault;use App\Payments\PaymentGatewayFactory;

final class PaymentGatewayAdminController extends Controller
{
    public function index():void
    {
        $u=Auth::requireAdmin();Auth::enforce('manage_payment_gateway');$row=PaymentConfig::row();$runtime=PaymentConfig::runtime();
        $checkoutReady=trim((string)$runtime['merchant_id'])!==''&&trim((string)$runtime['merchant_secret'])!=='';
        $apiReady=trim((string)$runtime['api_app_id'])!==''&&trim((string)$runtime['api_app_secret'])!=='';
        $httpsReady=str_starts_with(strtolower(absolute_url('')),'https://');
        $this->view('admin/payment-gateway',['user'=>$u,'gateway'=>$row,'runtime'=>$runtime,'vaultReady'=>PaymentSecretVault::ready(),'vaultSource'=>PaymentSecretVault::source(),'checkoutReady'=>$checkoutReady,'apiReady'=>$apiReady,'httpsReady'=>$httpsReady,'merchantSecretMask'=>PaymentSecretVault::mask((string)$runtime['merchant_secret']),'appSecretMask'=>PaymentSecretVault::mask((string)$runtime['api_app_secret']),'adminTitle'=>'Easy Payment Setup'],'admin');
    }
    public function save():void
    {
        $this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_payment_gateway');$old=PaymentConfig::row();$mode=($_POST['mode']??'sandbox')==='live'?'live':'sandbox';$merchantId=substr(trim($_POST['merchant_id']??''),0,100);$appId=substr(trim($_POST['api_app_id']??''),0,160);$min=max(1,(float)($_POST['min_amount']??100));$max=max($min,(float)($_POST['max_amount']??1000000));$prefix=preg_replace('/[^A-Z0-9-]/','',strtoupper(substr(trim($_POST['receipt_prefix']??'97D'),0,16)))?:'97D';
        $merchantEnc=$old['merchant_secret_enc']??'';$appEnc=$old['api_app_secret_enc']??'';$merchantSecret=trim((string)($_POST['merchant_secret']??''));$appSecret=trim((string)($_POST['api_app_secret']??''));
        try{if($merchantSecret!=='')$merchantEnc=PaymentSecretVault::encrypt($merchantSecret);if($appSecret!=='')$appEnc=PaymentSecretVault::encrypt($appSecret);}catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect('admin/developer/payment-gateway');}
        // V7.2: the on/off switch is separate; saving credentials keeps the current state.
        $enabled=array_key_exists('enabled',$_POST)?(($_POST['enabled']??'')==='1'?1:0):(int)($old['enabled']??0);
        $hasMerchantId=$merchantId!==''||trim((string)getenv('PAYHERE_MERCHANT_ID'))!=='';
        $hasMerchantSecret=$merchantEnc!==''||trim((string)getenv('PAYHERE_MERCHANT_SECRET'))!=='';
        if($enabled&&(!$hasMerchantId||!$hasMerchantSecret)){Session::flash('error','Online payments are on, so the PayHere Merchant ID and Merchant Secret cannot be empty. Turn online payments off first if you want to remove them.');redirect('admin/developer/payment-gateway');}
        if($mode==='live'&&$enabled&&!str_starts_with(strtolower(absolute_url('')),'https://')){Session::flash('error','Live payments require HTTPS. Keep Sandbox selected until the live website is using HTTPS.');redirect('admin/developer/payment-gateway');}
        Database::exec('INSERT INTO payment_gateway_configs(provider,enabled,mode,merchant_id,merchant_secret_enc,api_app_id,api_app_secret_enc,currency,min_amount,max_amount,receipt_prefix,receipt_email_enabled,updated_by,updated_at) VALUES("payhere",?,?,?,?,?,?,"LKR",?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled),mode=VALUES(mode),merchant_id=VALUES(merchant_id),merchant_secret_enc=VALUES(merchant_secret_enc),api_app_id=VALUES(api_app_id),api_app_secret_enc=VALUES(api_app_secret_enc),currency="LKR",min_amount=VALUES(min_amount),max_amount=VALUES(max_amount),receipt_prefix=VALUES(receipt_prefix),receipt_email_enabled=VALUES(receipt_email_enabled),updated_by=VALUES(updated_by),updated_at=NOW()',[$enabled,$mode,$merchantId,$merchantEnc,$appId,$appEnc,$min,$max,$prefix,isset($_POST['receipt_email_enabled'])?1:0,(int)$u['id']]);
        Audit::write((int)$u['id'],'PAYMENT_GATEWAY_CONFIG_UPDATED','payment_gateway_configs',1,'Payment gateway configuration updated (credentials not logged).');
        if(($_POST['save_action']??'')==='save_test'){
            $gateway=PaymentGatewayFactory::make('payhere');$r=$gateway->testConnection();Database::exec('UPDATE payment_gateway_configs SET last_test_status=?,last_test_message=?,last_test_at=NOW(),updated_by=? WHERE provider="payhere"',[$r['ok']?'success':'failed',substr($r['message'],0,500),(int)$u['id']]);Audit::write((int)$u['id'],'PAYMENT_GATEWAY_TEST','payment_gateway_configs',1,'Payment gateway setup validation: '.($r['ok']?'success':'failed'));Session::flash($r['ok']?'success':'error',($r['ok']?'Setup saved and validated. ':'Setup saved, but validation needs attention. ').$r['message']);redirect('admin/developer/payment-gateway');
        }
        Session::flash('success','Payment setup saved securely.');redirect('admin/developer/payment-gateway');
    }
    /** V7.2 — one-click online payments On / Off (used on several admin pages). */
    public function toggle():void
    {
        $this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_payment_gateway');
        $on=($_POST['enabled']??'0')==='1';
        $back=(string)($_POST['return']??'admin/developer/payment-gateway');
        if(!in_array($back,['admin/developer/payment-gateway','admin/online-payments','admin/donation-settings','admin/campaigns','admin'],true))$back='admin/developer/payment-gateway';
        $runtime=PaymentConfig::runtime();
        if($on&&(trim((string)$runtime['merchant_id'])===''||trim((string)$runtime['merchant_secret'])==='')){Session::flash('error','Online payments need your PayHere Merchant ID and Merchant Secret first. Add them in Developer Integration → Easy Payment Setup, then turn online payments on.');redirect($back);}
        if($on&&$runtime['mode']==='live'&&!str_starts_with(strtolower(absolute_url('')),'https://')){Session::flash('error','Live payments need HTTPS. Switch the gateway to Sandbox or enable HTTPS before turning online payments on.');redirect($back);}
        if(Database::one('SELECT id FROM payment_gateway_configs WHERE provider="payhere" LIMIT 1'))Database::exec('UPDATE payment_gateway_configs SET enabled=?,updated_by=?,updated_at=NOW() WHERE provider="payhere"',[$on?1:0,(int)$u['id']]);
        else Database::exec('INSERT INTO payment_gateway_configs(provider,enabled,mode,currency,min_amount,max_amount,receipt_prefix,updated_by,updated_at) VALUES("payhere",?,"sandbox","LKR",100,1000000,"97D",?,NOW())',[$on?1:0,(int)$u['id']]);
        Audit::write((int)$u['id'],$on?'ONLINE_PAYMENTS_ENABLED':'ONLINE_PAYMENTS_DISABLED','payment_gateway_configs',1,'Online payments turned '.($on?'on':'off'));
        Session::flash('success',$on?'Online payments are on. Donors can now pay by card on the Donate page'.($runtime['mode']==='live'?'.':' (Sandbox test mode).'):'Online payments are off. The Online Payment option on the Donate page is now unavailable; bank transfer donations still work.');
        redirect($back);
    }
    public function test():void
    {
        $this->requirePost();$u=Auth::requireAdmin();Auth::enforce('manage_payment_gateway');$gateway=PaymentGatewayFactory::make('payhere');$r=$gateway->testConnection();Database::exec('UPDATE payment_gateway_configs SET last_test_status=?,last_test_message=?,last_test_at=NOW(),updated_by=? WHERE provider="payhere"',[$r['ok']?'success':'failed',substr($r['message'],0,500),(int)$u['id']]);Audit::write((int)$u['id'],'PAYMENT_GATEWAY_TEST','payment_gateway_configs',1,'Payment gateway connection test: '.($r['ok']?'success':'failed'));Session::flash($r['ok']?'success':'error',$r['message']);redirect('admin/developer/payment-gateway');
    }
}
