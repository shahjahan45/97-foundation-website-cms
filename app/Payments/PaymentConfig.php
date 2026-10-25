<?php
namespace App\Payments;

use App\Core\Database;

final class PaymentConfig
{
    public static function row(): array
    {
        try{$row=Database::one('SELECT * FROM payment_gateway_configs WHERE provider="payhere" LIMIT 1')?:[];}catch(\Throwable){$row=[];}
        return array_merge([
            'provider'=>'payhere','enabled'=>0,'mode'=>'sandbox','merchant_id'=>'','merchant_secret_enc'=>'','api_app_id'=>'','api_app_secret_enc'=>'',
            'currency'=>'LKR','min_amount'=>'100.00','max_amount'=>'1000000.00','receipt_prefix'=>'97D','receipt_email_enabled'=>0,'last_test_status'=>'never','last_test_message'=>'','last_test_at'=>null
        ],$row);
    }

    public static function public(): array
    {
        $r=self::row();
        return [
            'provider'=>$r['provider'],'enabled'=>(int)$r['enabled']===1,'mode'=>$r['mode'],'currency'=>'LKR',
            'min_amount'=>(float)$r['min_amount'],'max_amount'=>(float)$r['max_amount'],
            'receipt_prefix'=>$r['receipt_prefix']?:'97D','receipt_email_enabled'=>(int)$r['receipt_email_enabled']===1
        ];
    }

    public static function runtime(): array
    {
        $r=self::row();
        $merchantId=trim((string)(getenv('PAYHERE_MERCHANT_ID') ?: $r['merchant_id']));
        $merchantSecret=trim((string)(getenv('PAYHERE_MERCHANT_SECRET') ?: PaymentSecretVault::decrypt($r['merchant_secret_enc']??'')));
        $appId=trim((string)(getenv('PAYHERE_APP_ID') ?: $r['api_app_id']));
        $appSecret=trim((string)(getenv('PAYHERE_APP_SECRET') ?: PaymentSecretVault::decrypt($r['api_app_secret_enc']??'')));
        $mode=strtolower(trim((string)(getenv('PAYHERE_MODE') ?: $r['mode'])))==='live'?'live':'sandbox';
        return array_merge($r,['merchant_id'=>$merchantId,'merchant_secret'=>$merchantSecret,'api_app_id'=>$appId,'api_app_secret'=>$appSecret,'mode'=>$mode,'currency'=>'LKR']);
    }

    public static function configured(): bool
    {
        $r=self::runtime();return (int)$r['enabled']===1&&$r['merchant_id']!==''&&$r['merchant_secret']!=='';
    }
}
