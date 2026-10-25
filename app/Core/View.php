<?php
namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'site'): void
    {
        $viewFile = dirname(__DIR__) . '/Views/' . $view . '.php';
        $layoutFile = dirname(__DIR__) . '/Views/layouts/' . $layout . '.php';
        if (!is_file($viewFile) || !is_file($layoutFile)) { http_response_code(500); exit('View not found.'); }
        extract($data, EXTR_SKIP);
        ob_start(); require $viewFile; $content = ob_get_clean();
        require $layoutFile;
    }
}
