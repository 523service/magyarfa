<?php
/**
 * Laravel DB full dump to /.backup/database-YYYYMMDD-HHMM.sql
 *
 * Usage:
 *   php backup-db.php
 *
 * Notes:
 * - Requires mysqldump (MySQL/MariaDB) or pg_dump (PostgreSQL) available on PATH.
 * - Reads DB_* from .env in the same directory as this script.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$projectRoot = __DIR__;
$envPath     = $projectRoot . DIRECTORY_SEPARATOR . '.env';
$backupDir   = $projectRoot . DIRECTORY_SEPARATOR . '.backup';

if (!is_file($envPath)) {
    fwrite(STDERR, "ERROR: .env not found at: {$envPath}\n");
    exit(1);
}

/**
 * Minimal .env parser that:
 * - ignores comments and empty lines
 * - supports KEY=VALUE
 * - supports quoted values: KEY="value" or KEY='value'
 * - strips surrounding quotes and unescapes common sequences
 */
function readEnvFile(string $path): array
{
    $vars = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException("Unable to read .env file: {$path}");
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Don't try to parse export statements; just strip leading "export "
        if (str_starts_with($line, 'export ')) {
            $line = trim(substr($line, 7));
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));

        // Remove inline comments only if not quoted
        if ($val !== '' && ($val[0] !== '"' && $val[0] !== "'")) {
            $hashPos = strpos($val, ' #');
            if ($hashPos !== false) {
                $val = trim(substr($val, 0, $hashPos));
            }
        }

        // Strip surrounding quotes
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))
        ) {
            $quote = $val[0];
            $val = substr($val, 1, -1);

            // Unescape a few common sequences in double-quoted values
            if ($quote === '"') {
                $val = str_replace(['\\n', '\\r', '\\t', '\\"', '\\\\'], ["\n", "\r", "\t", '"', '\\'], $val);
            }
        }

        $vars[$key] = $val;
    }

    return $vars;
}

function envGet(array $env, string $key, ?string $default = null): ?string
{
    if (array_key_exists($key, $env)) return $env[$key];
    $sys = getenv($key);
    if ($sys !== false) return $sys;
    return $default;
}

function ensureDir(string $path): void
{
    if (!is_dir($path)) {
        if (!mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException("Unable to create backup directory: {$path}");
        }
    }
    if (!is_writable($path)) {
        throw new RuntimeException("Backup directory is not writable: {$path}");
    }
}

function findBinary(string $name): string
{
    $cmd = (stripos(PHP_OS, 'WIN') === 0) ? "where " . escapeshellarg($name) : "command -v " . escapeshellarg($name);
    @exec($cmd, $out, $code);
    if ($code !== 0 || empty($out[0])) {
        throw new RuntimeException("Required binary not found on PATH: {$name}");
    }
    return trim($out[0]);
}

try {
    $env = readEnvFile($envPath);

    $connection = envGet($env, 'DB_CONNECTION', 'mysql') ?? 'mysql';
    $host       = envGet($env, 'DB_HOST', '127.0.0.1') ?? '127.0.0.1';
    $port       = envGet($env, 'DB_PORT', null);
    $database   = envGet($env, 'DB_DATABASE', null);
    $username   = envGet($env, 'DB_USERNAME', null);
    $password   = envGet($env, 'DB_PASSWORD', '');

    if (!$database || !$username) {
        throw new RuntimeException("Missing DB_DATABASE or DB_USERNAME in .env");
    }

    ensureDir($backupDir);

    $timestamp = date('Ymd-Hi'); // e.g. 20260126-0855
    $dumpPath  = $backupDir . DIRECTORY_SEPARATOR . "database-{$timestamp}.sql";

    $lowerConn = strtolower($connection);

    if (in_array($lowerConn, ['mysql', 'mariadb'], true)) {
        $mysqldump = findBinary('mysqldump');

        $portPart = $port ? (' --port=' . escapeshellarg($port)) : '';
        $hostPart = ' --host=' . escapeshellarg($host);

        // Use defaults-extra-file to avoid leaking password in process list
        $tmpCnf = tempnam(sys_get_temp_dir(), 'mysqldump_');
        if ($tmpCnf === false) {
            throw new RuntimeException("Unable to create temp file for mysqldump credentials");
        }

        $cnf = "[client]\nuser={$username}\npassword={$password}\n";
        file_put_contents($tmpCnf, $cnf);

        $cmd =
            escapeshellcmd($mysqldump) .
            " --defaults-extra-file=" . escapeshellarg($tmpCnf) .
            $hostPart .
            $portPart .
            " --single-transaction --routines --triggers --events --hex-blob" .
            " --databases " . escapeshellarg($database) .
            " > " . escapeshellarg($dumpPath) . " 2>&1";

        $exitCode = null;
        system($cmd, $exitCode);

        @unlink($tmpCnf);

        if ($exitCode !== 0 || !is_file($dumpPath) || filesize($dumpPath) === 0) {
            throw new RuntimeException("mysqldump failed (exit={$exitCode}). Output may be in the dump file/stdout redirection.");
        }
    }
    elseif (in_array($lowerConn, ['pgsql', 'postgres', 'postgresql'], true)) {
        $pgDump = findBinary('pg_dump');

        $portPart = $port ? (' --port=' . escapeshellarg($port)) : '';
        $hostPart = ' --host=' . escapeshellarg($host);
        $userPart = ' --username=' . escapeshellarg($username);

        // Avoid password prompt by setting PGPASSWORD for the command
        $envPrefix = (stripos(PHP_OS, 'WIN') === 0)
            ? 'set PGPASSWORD=' . escapeshellarg((string)$password) . ' && '
            : 'PGPASSWORD=' . escapeshellarg((string)$password) . ' ';

        $cmd =
            $envPrefix .
            escapeshellcmd($pgDump) .
            $hostPart .
            $portPart .
            $userPart .
            " --format=p --no-owner --no-privileges" .
            " " . escapeshellarg($database) .
            " > " . escapeshellarg($dumpPath) . " 2>&1";

        $exitCode = null;
        system($cmd, $exitCode);

        if ($exitCode !== 0 || !is_file($dumpPath) || filesize($dumpPath) === 0) {
            throw new RuntimeException("pg_dump failed (exit={$exitCode}). Output may be in the dump file/stdout redirection.");
        }
    }
    else {
        throw new RuntimeException("Unsupported DB_CONNECTION: {$connection} (supported: mysql/mariadb/pgsql)");
    }

    $size = filesize($dumpPath);
    echo "OK: Database export created:\n  {$dumpPath}\n  Size: {$size} bytes\n";
    exit(0);

} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
