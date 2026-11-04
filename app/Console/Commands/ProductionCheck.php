<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\DemoAccounts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProductionCheck extends Command
{
    protected $signature = 'syncode:production-check {--config-only : Check configuration before the first migration}';

    protected $description = 'Check required production configuration and administrator access';

    public function handle(): int
    {
        $checks = [
            'APP_ENV is production' => app()->isProduction(),
            'Debug output is disabled' => ! config('app.debug'),
            'APP_KEY is configured' => (bool) config('app.key'),
            'APP_URL uses a real HTTPS host' => $this->productionUrl(config('app.url')),
            'Demo features are disabled' => ! config('demo.enabled'),
            'Secure session cookies are enabled' => config('session.secure') === true,
            'HTTP-only cookies are enabled' => config('session.http_only') === true,
            'Private database sessions are encrypted' => config('session.driver') === 'database' && config('session.encrypt') === true,
            'Application uses MySQL/MariaDB' => DB::connection()->getDriverName() === 'mysql',
            'Database account is not root' => ! in_array(DB::connection()->getConfig('username'), ['', 'root'], true),
            'Database password is set' => (bool) DB::connection()->getConfig('password'),
            'Storage and cache are writable' => is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')),
        ];
        try {
            DB::connection()->getPdo();
            $checks['Database connection works'] = true;
            if (! $this->option('config-only')) {
                $checks['An active real Super Admin exists'] = User::where('role', 'super_admin')->where('suspended', false)->whereNotIn('email', array_column(DemoAccounts::users(), 'email'))->exists();
                $checks['Known demo login accounts are absent'] = ! User::whereIn('email', array_column(DemoAccounts::users(), 'email'))->exists();
                $checks['Public storage link exists'] = is_link(public_path('storage')) && is_dir(public_path('storage'));
            }
        } catch (\Throwable $error) {
            $checks['Database connection and schema work'] = false;
        }
        foreach ($checks as $label => $passed) {
            $this->line(($passed ? 'PASS ' : 'FAIL ').$label);
        }

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }

    private function productionUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return parse_url($url, PHP_URL_SCHEME) === 'https' && $host && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            && ! str_ends_with($host, '.test') && ! str_ends_with($host,'.example');
    }
}
