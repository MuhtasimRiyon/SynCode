<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'syncode:backup';

    protected $description = 'Save a private MySQL/MariaDB SQL backup without exposing credentials';

    public ?string $backupPath = null;

    public function handle(): int
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql') {
            $this->error('This backup command requires MySQL/MariaDB.');

            return self::FAILURE;
        }
        $config = $connection->getConfig();
        $directory = storage_path('app/private/backups');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true)) {
            return self::FAILURE;
        }
        chmod($directory, 0700);
        $path = $directory.'/syncode-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sql';
        touch($path);
        chmod($path, 0600);
        $args = [config('deployment.dump_binary'), '--single-transaction', '--quick', '--skip-lock-tables', '--no-tablespaces', '--user='.$config['username'], '--result-file='.$path];
        if (! empty($config['unix_socket'])) {
            $args[] = '--socket='.$config['unix_socket'];
        } else {
            $args[] = '--host='.$config['host'];
            $args[] = '--port='.($config['port'] ?? 3306);
        }
        $args[] = '--';
        $args[] = $config['database'];
        try {
            $process = new Process($args, base_path(), ['MYSQL_PWD' => (string) ($config['password'] ?? '')]);
            $process->setTimeout(300)->mustRun();
            if (filesize($path) === 0) {
                throw new \RuntimeException('The database dump is empty.');
            }
        } catch (\Throwable $error) {
            @unlink($path);
            $this->error('Backup failed. Check DB_DUMP_BINARY and database permissions.');

            return self::FAILURE;
        }
        $this->backupPath = $path;
        $this->info('Database backup saved: '.$path);

        return self::SUCCESS;
    }
}
