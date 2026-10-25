<?php
/**
 * COPY THIS FILE TO config.local.php ON THE LIVE SERVER.
 * Never commit/share the real file and never include it in release ZIP replacements.
 */
return [
    'app' => [
        'env' => 'production',
        'debug' => false,
        'url' => 'https://97foundation.datacubex.com',
        'timezone' => 'Asia/Colombo',
    ],
    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'YOUR_HOSTING_DATABASE_NAME',
        'user' => 'YOUR_HOSTING_DATABASE_USER',
        'pass' => 'YOUR_HOSTING_DATABASE_PASSWORD',
        'charset' => 'utf8mb4',
    ],
];
