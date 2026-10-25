<?php
namespace App\Core;

final class Session
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $v = $_SESSION ?? [];
        foreach (explode('.', $key) as $p) { if (!is_array($v) || !array_key_exists($p,$v)) return $default; $v=$v[$p]; }
        return $v;
    }
    public static function put(string $key, mixed $value): void
    {
        $parts=explode('.',$key); $ref=&$_SESSION;
        foreach ($parts as $p) { if (!isset($ref[$p]) || !is_array($ref[$p])) $ref[$p]=[]; $ref=&$ref[$p]; }
        $ref=$value;
    }
    public static function forget(string $key): void { self::forgetNested($key); }
    public static function pull(string $key, mixed $default=null): mixed { $v=self::get($key,$default); self::forgetNested($key); return $v; }
    private static function forgetNested(string $key): void { $parts=explode('.',$key); $ref=&$_SESSION; $last=array_pop($parts); foreach($parts as $p){ if(!isset($ref[$p])||!is_array($ref[$p])) return; $ref=&$ref[$p]; } unset($ref[$last]); }
    public static function flash(string $key, mixed $value): void { self::put('flash.'.$key,$value); }
    public static function regenerate(): void { session_regenerate_id(true); }
}
