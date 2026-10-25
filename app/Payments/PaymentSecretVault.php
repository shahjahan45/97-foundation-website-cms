<?php
namespace App\Payments;

use RuntimeException;

final class PaymentSecretVault
{
    private static ?string $cachedKey=null;
    private static function filePath(): string{return dirname(__DIR__,2).'/storage/private/payment.key';}
    private static function configuredKey(): string{return trim((string)(config('payments.encryption_key','') ?: getenv('PAYMENT_ENCRYPTION_KEY') ?: getenv('APP_KEY') ?: ''));}
    private static function rawKey(): string
    {
        if(self::$cachedKey!==null)return self::$cachedKey;
        $configured=self::configuredKey();
        if(strlen($configured)>=32)return self::$cachedKey=$configured;
        $file=self::filePath();
        if(is_file($file)){
            $saved=trim((string)@file_get_contents($file));
            if(strlen($saved)>=32)return self::$cachedKey=$saved;
        }
        $dir=dirname($file);
        if(!is_dir($dir))@mkdir($dir,0750,true);
        if(is_dir($dir)&&is_writable($dir)){
            try{
                $generated=bin2hex(random_bytes(32));
                if(@file_put_contents($file,$generated,LOCK_EX)!==false){@chmod($file,0600);return self::$cachedKey=$generated;}
            }catch(\Throwable){}
        }
        return self::$cachedKey='';
    }
    public static function ready(): bool{return strlen(self::rawKey())>=32&&function_exists('openssl_encrypt');}
    public static function source(): string
    {
        if(strlen(self::configuredKey())>=32)return 'server_environment';
        if(self::ready()&&is_file(self::filePath()))return 'private_storage';
        return 'unavailable';
    }
    private static function key(): string
    {
        $raw=self::rawKey();
        if(strlen($raw)<32)throw new RuntimeException('Secure payment credential storage is unavailable. Make storage/private writable or configure PAYMENT_ENCRYPTION_KEY on the server.');
        return hash('sha256',$raw,true);
    }
    public static function encrypt(string $plain): string
    {
        if($plain==='')return '';
        $iv=random_bytes(12);$tag='';
        $cipher=openssl_encrypt($plain,'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,$iv,$tag,'97-foundation-payments');
        if($cipher===false)throw new RuntimeException('Unable to encrypt payment credential.');
        return 'v1.'.base64_encode($iv).'.'.base64_encode($tag).'.'.base64_encode($cipher);
    }
    public static function decrypt(?string $encoded): string
    {
        if(!$encoded)return '';
        $parts=explode('.',$encoded);
        if(count($parts)!==4||$parts[0]!=='v1')return '';
        try{$iv=base64_decode($parts[1],true);$tag=base64_decode($parts[2],true);$cipher=base64_decode($parts[3],true);if($iv===false||$tag===false||$cipher===false)return '';$plain=openssl_decrypt($cipher,'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,$iv,$tag,'97-foundation-payments');return $plain===false?'':$plain;}catch(\Throwable){return '';}
    }
    public static function mask(string $value): string
    {
        if($value==='')return 'Not configured';$len=strlen($value);if($len<=6)return str_repeat('•',$len);return substr($value,0,2).str_repeat('•',max(6,$len-6)).substr($value,-4);
    }
}
