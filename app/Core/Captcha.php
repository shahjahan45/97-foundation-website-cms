<?php
namespace App\Core;
final class Captcha
{
    public static function issue(string $scope='login'): string
    {
        $code=(string)random_int(1000,9999);
        Session::put('captcha.'.$scope,['code'=>$code,'expires'=>time()+600]);
        return $code;
    }
    public static function current(string $scope='login'): string
    {
        $data=Session::get('captcha.'.$scope);
        if(!is_array($data) || ($data['expires']??0)<time()) return self::issue($scope);
        return (string)$data['code'];
    }
    public static function verify(string $input,string $scope='login'): bool
    {
        $data=Session::get('captcha.'.$scope);
        Session::forget('captcha.'.$scope);
        return is_array($data) && ($data['expires']??0)>=time() && hash_equals((string)$data['code'],trim($input));
    }
}
