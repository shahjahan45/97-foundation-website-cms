<?php
namespace App\Core;

final class App
{
    private static array $config = [];
    public static function setConfig(array $config): void { self::$config = $config; }
    public static function config(string $key, mixed $default = null): mixed
    {
        $value = self::$config;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) return $default;
            $value = $value[$part];
        }
        return $value;
    }
}
