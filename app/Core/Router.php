<?php
namespace App\Core;

final class Router
{
    private array $routes=[];
    public function get(string $path, callable|array $handler): void { $this->routes['GET'][]=[$path,$handler]; }
    public function post(string $path, callable|array $handler): void { $this->routes['POST'][]=[$path,$handler]; }

    public function dispatch(string $method,string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $base = parse_url((string)App::config('app.url', ''), PHP_URL_PATH) ?: '';
        $base = ($base === '/') ? '' : rtrim($base, '/');

        // Strip only a complete base-path boundary. Example:
        // /97-foundation/about -> /about
        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            $path = substr($path, strlen($base));
            if ($path === '') $path = '/';
        }

        $path = '/' . trim($path, '/');
        if ($path === '//') $path = '/';

        foreach($this->routes[$method]??[] as [$route,$handler]){
            $params=[];
            $pattern=preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',function($m)use(&$params){$params[]=$m[1];return '([^/]+)';},$route);
            if(preg_match('#^'.$pattern.'$#',$path,$m)){
                array_shift($m);
                $args=array_map('urldecode',$m);
                if(is_array($handler)){
                    [$class,$fn]=$handler;
                    (new $class())->$fn(...$args);
                } else {
                    $handler(...$args);
                }
                return;
            }
        }

        http_response_code(404);
        View::render('404',[],'site');
    }
}
