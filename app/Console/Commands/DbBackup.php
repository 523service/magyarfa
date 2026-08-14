<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class DbBackup extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Create a full database dump into /.backup directory';

    public function handle(): int
    {
        try {
            date_default_timezone_set(config('app.timezone', 'UTC'));

            $connectionName = config('database.default');
            $connection     = config("database.connections.{$connectionName}");

            if (!$connection) {
                throw new RuntimeException("Database connection not found: {$connectionName}");
            }

            $driver   = $connection['driver'] ?? null;
            $host     = $connection['host'] ?? '127.0.0.1';
            $port     = $connection['port'] ?? null;
            $database = $connection['database'] ?? null;
            $username = $connection['username'] ?? null;
            $password = $connection['password'] ?? '';

            if (!$database || !$username || !$driver) {
                throw new RuntimeException('Invalid database configuration');
            }

            $backupDir = base_path('.backup');
            File::ensureDirectoryExists($backupDir);

            if (!is_writable($backupDir)) {
                throw new RuntimeException("Backup directory is not writable: {$backupDir}");
            }

            $timestamp = now()->format('Ymd-Hi');
            $dumpPath  = "{$backupDir}/database-{$timestamp}.sql";

            match ($driver) {
                'mysql', 'mariadb' => $this->dumpMysql(
                    $dumpPath,
                    $host,
                    $port,
                    $database,
                    $username,
                    $password
                ),
                'pgsql' => $this->dumpPostgres(
                    $dumpPath,
                    $host,
                    $port,
                    $database,
                    $username,
                    $password
                ),
                default => throw new RuntimeException("Unsupported DB driver: {$driver}")
            };

            $size = filesize($dumpPath);
            $this->info("✔ Database backup created");
            $this->line("Path: {$dumpPath}");
            $this->line("Size: {$size} bytes");

            return Command::SUCCESS;

        } catch (Throwable $e) {
            $this->error('✖ Database backup failed');
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    /* -----------------------------------------------------------------
     |  MySQL / MariaDB
     | -----------------------------------------------------------------
     */

    protected function dumpMysql(
        string $dumpPath,
        string $host,
        ?string $port,
        string $database,
        string $username,
        string $password
    ): void {

        // put .env like: MYSQLDUMP_PATH=C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe
        $mysqldump = (string) env('MYSQLDUMP_PATH', '');

        if ($mysqldump !== '') {
            // Normalize Windows paths for shell usage
            $mysqldump = str_replace('\\', '/', $mysqldump);

            if (!is_file($mysqldump)) {
                throw new \RuntimeException("MYSQLDUMP_PATH is set but file not found: {$mysqldump}");
            }
        } else {
            // Fallback only if env is not set
            $mysqldump = $this->findBinary('mysqldump');
        }

        // Avoid exposing password in process list
        $tmpCnf = tempnam(sys_get_temp_dir(), 'mysqldump_');
        if ($tmpCnf === false) {
            throw new RuntimeException('Unable to create temp credentials file');
        }

        file_put_contents($tmpCnf, "[client]\nuser={$username}\npassword={$password}\n");

        $cmd =
            escapeshellcmd($mysqldump) .
            " --defaults-extra-file=" . escapeshellarg($tmpCnf) .
            " --host=" . escapeshellarg($host) .
            ($port ? " --port=" . escapeshellarg($port) : '') .
            " --single-transaction --routines --triggers --events --hex-blob" .
            " --databases " . escapeshellarg($database) .
            " > " . escapeshellarg($dumpPath) . " 2>&1";

        $this->execCommandWithProgress($cmd, $dumpPath);

        @unlink($tmpCnf);
    }

    /* -----------------------------------------------------------------
     |  PostgreSQL
     | -----------------------------------------------------------------
     */

    protected function dumpPostgres(
        string $dumpPath,
        string $host,
        ?string $port,
        string $database,
        string $username,
        string $password
    ): void {
        $pgDump = $this->findBinary('pg_dump');

        $envPrefix = stripos(PHP_OS, 'WIN') === 0
            ? 'set PGPASSWORD=' . escapeshellarg($password) . ' && '
            : 'PGPASSWORD=' . escapeshellarg($password) . ' ';

        $cmd =
            $envPrefix .
            escapeshellcmd($pgDump) .
            " --host=" . escapeshellarg($host) .
            ($port ? " --port=" . escapeshellarg($port) : '') .
            " --username=" . escapeshellarg($username) .
            " --format=p --no-owner --no-privileges" .
            " " . escapeshellarg($database) .
            " > " . escapeshellarg($dumpPath) . " 2>&1";

        $this->execCommandWithProgress($cmd, $dumpPath);

    }

    /* -----------------------------------------------------------------
     |  Helpers
     | -----------------------------------------------------------------
     */

    protected function findBinary(string $binary): string
    {
        $cmd = stripos(PHP_OS, 'WIN') === 0
            ? "where {$binary}"
            : "command -v {$binary}";

        exec($cmd, $out, $code);

        if ($code !== 0 || empty($out[0])) {
            throw new RuntimeException("Binary not found on PATH: {$binary}");
        }

        return trim($out[0]);
    }

    protected function execCommand(string $command): void
    {
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException("Dump command failed:\n" . implode("\n", $output));
        }
    }

    protected function execCommandWithProgress(string $command, string $dumpPath): void
    {
        $startTime = microtime(true);

        // Start process
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start dump process');
        }

        // Close STDIN immediately
        fclose($pipes[0]);

        // Poll while process is running
        while (true) {
            $status = proc_get_status($process);
            $elapsed = (int) (microtime(true) - $startTime);

            $sizeBytes = is_file($dumpPath) ? filesize($dumpPath) : 0;

            if ($sizeBytes >= 1024 * 1024) {
                $size = round($sizeBytes / 1024 / 1024, 2) . ' MB';
            } else {
                $size = round($sizeBytes / 1024, 1) . ' KB';
            }

            $this->output->write(
                "\r⏱ {$elapsed}s | 💾 {$size}"
            );

            if (!$status['running']) {
                break;
            }

            usleep(500_000); // 0.5 sec refresh
        }

        // Read outputs (important for error handling)
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        $this->output->writeln(''); // new line after progress

        if ($exitCode !== 0) {
            throw new \RuntimeException(
                "Dump failed (exit={$exitCode})\n{$stderr}"
            );
        }
    }


}
