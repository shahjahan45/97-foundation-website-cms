<?php
namespace App\Core;

final class Totp
{
    private const ALPHABET='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    public static function generateSecret(int $length=32): string { $s=''; for($i=0;$i<$length;$i++) $s.=self::ALPHABET[random_int(0,31)]; return $s; }
    private static function decode(string $b32): string { $b32=strtoupper(preg_replace('/[^A-Z2-7]/','',$b32)); $bits=''; foreach(str_split($b32) as $c){ $v=strpos(self::ALPHABET,$c); $bits.=str_pad(decbin($v),5,'0',STR_PAD_LEFT);} $out=''; foreach(str_split($bits,8) as $byte){ if(strlen($byte)===8)$out.=chr(bindec($byte)); } return $out; }
    public static function code(string $secret, ?int $time=null): string { $counter=intdiv($time??time(),30); $bin=pack('N*',0).pack('N*',$counter); $hash=hash_hmac('sha1',$bin,self::decode($secret),true); $o=ord($hash[19])&0xf; $v=((ord($hash[$o])&0x7f)<<24)|((ord($hash[$o+1])&0xff)<<16)|((ord($hash[$o+2])&0xff)<<8)|(ord($hash[$o+3])&0xff); return str_pad((string)($v%1000000),6,'0',STR_PAD_LEFT); }
    public static function verify(string $secret,string $code): bool { if(!preg_match('/^\d{6}$/',$code))return false; for($i=-1;$i<=1;$i++) if(hash_equals(self::code($secret,time()+$i*30),$code)) return true; return false; }
}
