<?php
/**
 * PDO singleton for bnsis_permission (MySQL/MariaDB).
 */
declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $cfg = require APP_ROOT . '/config/database.php';
            $dsn = $cfg['socket'] && !getenv('DB_HOST')
                ? "mysql:unix_socket={$cfg['socket']};dbname={$cfg['database']};charset={$cfg['charset']}"
                : "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset={$cfg['charset']}";
            self::$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }

    /** Convenience: SELECT many. */
    public static function select(string $sql, array $params = []): array
    {
        $st = self::connection()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** Convenience: SELECT one row or null. */
    public static function selectOne(string $sql, array $params = []): ?array
    {
        $rows = self::select($sql, $params);
        return $rows[0] ?? null;
    }

    /** Convenience: INSERT/UPDATE/DELETE -> affected rows. */
    public static function statement(string $sql, array $params = []): int
    {
        $st = self::connection()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    public static function insertGetId(string $sql, array $params = []): int
    {
        self::statement($sql, $params);
        return (int) self::connection()->lastInsertId();
    }
}
