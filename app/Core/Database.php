<?php
namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static array $config = [];
    private static ?PDO $pdo = null;
    public static function configure(array $config): void { self::$config = $config; }
    public static function pdo(): PDO
    {
        if (self::$pdo) return self::$pdo;
        $c = self::$config;
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}";
        try {
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
            return self::$pdo;
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed. Check config/environment and import database/schema.sql.', 0, $e);
        }
    }
    public static function all(string $sql, array $params = []): array { $s=self::pdo()->prepare($sql); $s->execute($params); return $s->fetchAll(); }
    public static function one(string $sql, array $params = []): ?array { $s=self::pdo()->prepare($sql); $s->execute($params); $r=$s->fetch(); return $r ?: null; }
    public static function exec(string $sql, array $params = []): int { $s=self::pdo()->prepare($sql); $s->execute($params); return $s->rowCount(); }
    public static function insert(string $sql, array $params = []): int { $s=self::pdo()->prepare($sql); $s->execute($params); return (int)self::pdo()->lastInsertId(); }
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        try { $pdo->beginTransaction(); $r=$callback($pdo); $pdo->commit(); return $r; }
        catch (\Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
}
