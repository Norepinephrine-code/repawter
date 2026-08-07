<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found\n");
}

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/config/config.php';

const MIGRATIONS_DIR = APP_ROOT . '/db/migrations';

$args   = array_slice($argv, 1);
$status = in_array('--status', $args, true);
$seed   = in_array('--seed', $args, true);

foreach ($args as $arg) {
    if (!in_array($arg, ['--status', '--seed'], true)) {
        fwrite(STDERR, "Unknown option: {$arg}\n");
        fwrite(STDERR, "Usage: php db/migrate.php [--status] [--seed]\n");
        exit(2);
    }
}

$pdo = migration_connection();
ensure_migrations_table($pdo);

$applied  = applied_migrations($pdo);
$onDisk   = discover_migrations();

if ($onDisk === []) {
    fwrite(STDERR, "No migrations found in " . MIGRATIONS_DIR . "\n");
    exit(1);
}

if ($status) {
    print_status($onDisk, $applied);
    exit(0);
}

report_checksum_drift($onDisk, $applied);

$pending = array_filter(
    $onDisk,
    static fn (array $m): bool => !isset($applied[$m['version']])
);

if ($pending === []) {
    echo "Database is up to date — " . count($applied) . " migration(s) applied.\n";
} else {
    foreach ($pending as $migration) {
        apply_migration($pdo, $migration);
    }
    echo "\nApplied " . count($pending) . " migration(s).\n";
}

if ($seed) {
    load_seed($pdo);
}

exit(0);

function migration_connection(): PDO
{
    $user = getenv('DB_MIGRATION_USER') ?: DB_USER;
    $pass = getenv('DB_MIGRATION_PASS');
    if ($pass === false) {
        $pass = DB_PASS;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

    try {
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        fwrite(STDERR, "Cannot connect to database '" . DB_NAME . "' on " . DB_HOST . " as '{$user}'.\n");
        fwrite(STDERR, $e->getMessage() . "\n\n");
        fwrite(STDERR, "The database must already exist. Create it with:\n");
        fwrite(STDERR, "  CREATE DATABASE `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n");
        exit(1);
    }
}

function ensure_migrations_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `schema_migrations` (
            `version`     VARCHAR(100) NOT NULL PRIMARY KEY,
            `filename`    VARCHAR(255) NOT NULL,
            `checksum`    CHAR(64)     NOT NULL,
            `applied_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `duration_ms` INT UNSIGNED NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function applied_migrations(PDO $pdo): array
{
    $rows = $pdo->query('SELECT `version`, `checksum`, `applied_at` FROM `schema_migrations`')->fetchAll();

    $out = [];
    foreach ($rows as $row) {
        $out[$row['version']] = ['checksum' => $row['checksum'], 'applied_at' => $row['applied_at']];
    }
    ksort($out);

    return $out;
}

function discover_migrations(): array
{
    $files = glob(MIGRATIONS_DIR . '/*.sql') ?: [];
    sort($files, SORT_STRING);

    $out = [];
    foreach ($files as $path) {
        $filename = basename($path);

        
        if (!preg_match('/^(\d+)_/', $filename, $m)) {
            fwrite(STDERR, "Skipping '{$filename}': migrations must be named <number>_<description>.sql\n");
            continue;
        }

        $out[] = [
            'version'  => $m[1],
            'filename' => $filename,
            'path'     => $path,
            'checksum' => hash_file('sha256', $path),
        ];
    }

    return $out;
}

function print_status(array $onDisk, array $applied): void
{
    echo "Migration status for database '" . DB_NAME . "' on " . DB_HOST . "\n\n";
    printf("  %-9s %-40s %-9s %s\n", 'VERSION', 'MIGRATION', 'STATE', 'APPLIED AT');
    echo '  ' . str_repeat('-', 82) . "\n";

    foreach ($onDisk as $m) {
        $record = $applied[$m['version']] ?? null;
        if ($record === null) {
            $state = 'pending';
            $when  = '-';
        } elseif ($record['checksum'] !== $m['checksum']) {
            $state = 'CHANGED';
            $when  = $record['applied_at'];
        } else {
            $state = 'applied';
            $when  = $record['applied_at'];
        }

        printf("  %-9s %-40s %-9s %s\n", $m['version'], $m['filename'], $state, $when);
    }

    
    
    $known = array_column($onDisk, 'version');
    foreach (array_keys($applied) as $version) {
        if (!in_array($version, $known, true)) {
            printf("  %-9s %-40s %-9s %s\n", $version, '(file missing)', 'ORPHAN', $applied[$version]['applied_at']);
        }
    }

    echo "\n";
}

function report_checksum_drift(array $onDisk, array $applied): void
{
    $drifted = [];
    foreach ($onDisk as $m) {
        $record = $applied[$m['version']] ?? null;
        if ($record !== null && $record['checksum'] !== $m['checksum']) {
            $drifted[] = $m['filename'];
        }
    }

    if ($drifted === []) {
        return;
    }

    fwrite(STDERR, "WARNING: these migrations were edited after being applied:\n");
    foreach ($drifted as $filename) {
        fwrite(STDERR, "  - {$filename}\n");
    }
    fwrite(
        STDERR,
        "Their changes are NOT in this database. Write a new migration instead of\n"
        . "editing an applied one; see db/migrations/README.md.\n\n"
    );
}

function apply_migration(PDO $pdo, array $migration): void
{
    $sql        = file_get_contents($migration['path']);
    $statements = split_sql_statements((string)$sql);

    printf("  %-40s ", $migration['filename']);

    $start = microtime(true);

    try {
        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }
    } catch (PDOException $e) {
        echo "FAILED\n\n";
        fwrite(STDERR, "Migration {$migration['filename']} failed:\n" . $e->getMessage() . "\n\n");
        fwrite(
            STDERR,
            "MySQL does not roll back DDL, so the database may be part-way through\n"
            . "this migration. Inspect it, undo the partial change, then re-run.\n"
        );
        exit(1);
    }

    $durationMs = (int)round((microtime(true) - $start) * 1000);

    $stmt = $pdo->prepare(
        'INSERT INTO `schema_migrations` (`version`, `filename`, `checksum`, `duration_ms`)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$migration['version'], $migration['filename'], $migration['checksum'], $durationMs]);

    printf("ok (%d ms)\n", $durationMs);
}

function load_seed(PDO $pdo): void
{
    if (APP_ENV === 'prod') {
        fwrite(STDERR, "\nRefusing to load demo seed data while APP_ENV=prod.\n");
        exit(1);
    }

    $path = APP_ROOT . '/db/seed.sql';
    if (!is_file($path)) {
        fwrite(STDERR, "\nSeed file not found: {$path}\n");
        exit(1);
    }

    echo "\nLoading demo seed data from db/seed.sql ... ";

    try {
        foreach (split_sql_statements((string)file_get_contents($path)) as $statement) {
            $pdo->exec($statement);
        }
    } catch (PDOException $e) {
        echo "FAILED\n";
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(1);
    }

    echo "ok\n";
}

function split_sql_statements(string $sql): array
{
    $statements = [];
    $current    = '';
    $length     = strlen($sql);
    $i          = 0;

    
    $context = null;

    while ($i < $length) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($context === null) {
            
            if ($char === '-' && $next === '-') {
                $context = 'line';
                $i      += 2;
                continue;
            }
            if ($char === '#') {
                $context = 'line';
                $i++;
                continue;
            }
            if ($char === '/' && $next === '*') {
                $context = 'block';
                $i      += 2;
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $context  = $char;
                $current .= $char;
                $i++;
                continue;
            }

            if ($char === ';') {
                $trimmed = trim($current);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $current = '';
                $i++;
                continue;
            }

            $current .= $char;
            $i++;
            continue;
        }

        if ($context === 'line') {
            if ($char === "\n") {
                $context  = null;
                $current .= "\n";
            }
            $i++;
            continue;
        }

        if ($context === 'block') {
            if ($char === '*' && $next === '/') {
                $context = null;
                $i      += 2;
                continue;
            }
            $i++;
            continue;
        }

        
        $current .= $char;

        
        if ($char === '\\' && $context !== '`' && $next !== '') {
            $current .= $next;
            $i       += 2;
            continue;
        }

        if ($char === $context) {
            
            if ($next === $context) {
                $current .= $next;
                $i       += 2;
                continue;
            }
            $context = null;
        }

        $i++;
    }

    $trimmed = trim($current);
    if ($trimmed !== '') {
        $statements[] = $trimmed;
    }

    return $statements;
}
