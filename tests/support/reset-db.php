<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found\n");
}

define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/app/config/config.php';

if (APP_ENV === 'prod') {
    fwrite(STDERR, "Refusing to reset the database while APP_ENV=prod.\n");
    exit(1);
}

$allowUnsafe = getenv('ALLOW_UNSAFE_DB_RESET') === '1';

if (!$allowUnsafe && !str_contains(DB_NAME, 'test') && !str_contains(DB_NAME, 'e2e')) {
    fwrite(STDERR, sprintf(
        "Refusing to reset '%s': the name does not contain 'test' or 'e2e'.\n"
        . "Point DB_NAME at a throwaway database, or set ALLOW_UNSAFE_DB_RESET=1 if you\n"
        . "really mean to wipe this one.\n",
        DB_NAME
    ));
    exit(1);
}

$user = getenv('DB_MIGRATION_USER') ?: DB_USER;
$pass = getenv('DB_MIGRATION_PASS');
if ($pass === false) {
    $pass = DB_PASS;
}

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET),
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "Cannot connect to '" . DB_NAME . "': " . $e->getMessage() . "\n");
    fwrite(STDERR, "Create it first:\n");
    fwrite(STDERR, "  CREATE DATABASE `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n");
    exit(1);
}

echo "Resetting " . DB_NAME . " on " . DB_HOST . " ...\n";

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

$tables = $pdo->query(
    'SELECT table_name AS t FROM information_schema.tables WHERE table_schema = DATABASE()'
)->fetchAll();

foreach ($tables as $row) {
    $pdo->exec('DROP TABLE IF EXISTS `' . $row['t'] . '`');
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
echo "  dropped " . count($tables) . " table(s)\n";

$migrate = escapeshellarg(APP_ROOT . '/db/migrate.php');
$php     = escapeshellarg(PHP_BINARY);

exec("{$php} {$migrate} --seed 2>&1", $output, $status);

foreach ($output as $line) {
    echo '  ' . $line . "\n";
}

if ($status !== 0) {
    fwrite(STDERR, "Migration run failed.\n");
    exit(1);
}

foreach (['reports', 'pets', 'resources', 'agreements'] as $dir) {
    $path = APP_ROOT . '/uploads/' . $dir;
    if (!is_dir($path)) {
        continue;
    }
    foreach (glob($path . '/*') ?: [] as $file) {
        if (is_file($file) && basename($file) !== '.gitkeep') {
            unlink($file);
        }
    }
}

echo "Database ready.\n";
