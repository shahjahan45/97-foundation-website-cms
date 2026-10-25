<?php
namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout='site'): void { View::render($view,$data,$layout); }
    protected function requirePost(): void { if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); exit('Method Not Allowed'); } Csrf::enforce(); }
}
