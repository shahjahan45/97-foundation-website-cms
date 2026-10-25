<?php
namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\Site;
use App\Core\View;
use App\Payments\PaymentConfig;
use App\Payments\PaymentGatewayFactory;
use App\Payments\PaymentService;

final class OnlineDonationController
{
    public function review():void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);exit('Method Not Allowed');}Csrf::enforce();
        try{$tx=PaymentService::create($_POST);Session::put('payment.token.'.$tx['public_id'],$tx['session_token']);View::render('payment/review',['transaction'=>$tx,'paymentConfig'=>PaymentConfig::public(),'settings'=>Site::settings(),'seo'=>['title'=>'Review Online Donation | 97 Foundation','description'=>'Review your secure online donation before continuing to the payment gateway.']],'site');}
        catch(\Throwable $e){Session::flash('error',$e->getMessage());redirect('donate?tab=online');}
    }
    public function pay(string $public):void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);exit('Method Not Allowed');}Csrf::enforce();$tx=PaymentService::byPublic($public);if(!$tx){http_response_code(404);exit('Payment not found.');}$token=(string)($_POST['payment_token']??'');$stored=(string)Session::get('payment.token.'.$public,'');if($stored===''||!hash_equals($stored,$token)){http_response_code(403);exit('Payment session expired. Please restart your donation.');}if($tx['status']!=='pending'){redirect('donate/payment/return?ref='.rawurlencode($public));}
        Database::exec('UPDATE payment_transactions SET status="processing",retry_count=retry_count+1,updated_at=NOW() WHERE id=?',[(int)$tx['id']]);$tx=PaymentService::byPublic($public);try{$gateway=PaymentGatewayFactory::make($tx['gateway']);$payload=$gateway->buildCheckoutPayload($tx);View::render('payment/redirect',['transaction'=>$tx,'gateway'=>$gateway,'payload'=>$payload,'settings'=>Site::settings(),'seo'=>['title'=>'Secure Payment Redirect | 97 Foundation','description'=>'Redirecting securely to the payment provider.']],'site');}catch(\Throwable $e){Database::exec('UPDATE payment_transactions SET status="failed",failed_at=NOW(),updated_at=NOW() WHERE id=?',[(int)$tx['id']]);Session::flash('error',$e->getMessage());redirect('donate/payment/return?ref='.rawurlencode($public));}
    }
    public function notify():void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);exit;}header('Content-Type: text/plain; charset=UTF-8');
        try{$r=PaymentService::callback($_POST);http_response_code(!empty($r['valid'])||!empty($r['duplicate'])?200:400);echo !empty($r['valid'])||!empty($r['duplicate'])?'OK':'INVALID';}catch(\Throwable){http_response_code(404);echo 'NOT FOUND';}
    }
    public function returnPage():void
    {
        $ref=substr(trim($_GET['ref']??''),0,64);$tx=$ref?PaymentService::byPublic($ref):null;if(!$tx){http_response_code(404);exit('Payment reference not found.');}View::render('payment/result',['transaction'=>$tx,'settings'=>Site::settings(),'seo'=>['title'=>'Donation Payment Status | 97 Foundation','description'=>'View the verified status of your 97 Foundation online donation.']],'site');
    }
    public function cancel():void
    {
        $ref=substr(trim($_GET['ref']??''),0,64);if($ref)PaymentService::markCancelled($ref);$tx=$ref?PaymentService::byPublic($ref):null;if(!$tx){redirect('donate?tab=online');}View::render('payment/result',['transaction'=>$tx,'settings'=>Site::settings(),'seo'=>['title'=>'Donation Payment Cancelled | 97 Foundation','description'=>'Your online donation payment was cancelled. You can safely try again.']],'site');
    }
    public function receipt(string $public):void
    {
        $tx=PaymentService::byPublic(substr($public,0,64));if(!$tx||!in_array($tx['status'],['successful','refunded'],true)){http_response_code(404);exit('Receipt not available.');}View::render('payment/receipt',['transaction'=>$tx,'settings'=>Site::settings(),'seo'=>['title'=>'Donation Receipt | 97 Foundation','description'=>'Official 97 Foundation online donation receipt.']],'site');
    }
}
