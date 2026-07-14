<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '+08:00'");
    }
    return $pdo;
}

function db_one(string $sql, array $p = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($p);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function db_all(string $sql, array $p = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($p);
    return $stmt->fetchAll();
}

function db_exec(string $sql, array $p = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($p);
    return $stmt->rowCount();
}

function db_insert_id(): string
{
    return db()->lastInsertId();
}
