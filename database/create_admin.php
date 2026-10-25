<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
use App\Core\Database;

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only\n"); }
$email = strtolower(trim($argv[1] ?? ''));
$name = trim($argv[2] ?? 'Super Administrator');
$password = $argv[3] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) exit("Usage: php database/create_admin.php admin@example.com \"Admin Name\" \"StrongPassword\"\n");
if (strlen($password) < 12 || !preg_match('/[A-Z]/',$password) || !preg_match('/[a-z]/',$password) || !preg_match('/\d/',$password) || !preg_match('/[^A-Za-z0-9]/',$password)) exit("Password must be 12+ chars and include upper, lower, number and symbol.\n");
$hash = password_hash($password, PASSWORD_ARGON2ID);
Database::exec('INSERT INTO users(name,email,password_hash,role,status) VALUES(?,?,?,?,"active") ON DUPLICATE KEY UPDATE name=VALUES(name),password_hash=VALUES(password_hash),role="super_admin",status="active"',[$name,$email,$hash,'super_admin']);
echo "Super administrator created/updated securely.\n";
