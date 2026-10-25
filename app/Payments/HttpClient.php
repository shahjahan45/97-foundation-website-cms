<?php
namespace App\Payments;

use RuntimeException;

final class HttpClient
{
    public static function request(string $method,string $url,array $headers=[],string $body=''): array
    {
        if(!function_exists('curl_init')) throw new RuntimeException('PHP cURL extension is required for payment API status/refund calls.');
        $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,CURLOPT_CUSTOMREQUEST=>strtoupper($method),CURLOPT_HTTPHEADER=>$headers,CURLOPT_USERAGENT=>'97Foundation-PaymentService/1.0']);
        if($body!=='')curl_setopt($ch,CURLOPT_POSTFIELDS,$body);
        $response=curl_exec($ch);$errno=curl_errno($ch);$error=curl_error($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
        if($response===false||$errno) throw new RuntimeException('Payment provider connection failed: '.$error);
        return ['status'=>$status,'body'=>(string)$response];
    }
}
