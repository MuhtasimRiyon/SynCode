# SynCode server handoff

The application is prepared locally. No domain, hosting, certificate, external email service or real administrator has been configured. The local MySQL database has three explicitly enabled role test accounts and no sample stories. Remove these local test accounts before copying that database to a server; a fresh server database receives none. An SQL backup of the earlier data is private under `storage/app/private/backups/` and must not be shipped in a public release.

## First installation

1. Install PHP 8.2+ with Laravel’s required extensions plus PDO MySQL and Fileinfo, Composer, MySQL/MariaDB, and Apache or Nginx/PHP-FPM. Configure the server document root to the project’s **public/** directory. Never expose the project root, `.env`, backups, logs or `vendor/`. The root `.htaccess` also protects the local XAMPP layout. See the [Laravel deployment documentation](https://laravel.com/docs/12.x/deployment).
2. Upload the source, `composer.lock`, public assets and migrations. Exclude `.env`, `.git`, local `vendor/`, local sessions/cache/logs, SQL backups, test files and `storage/framework/testing/`. Install production dependencies on the server. Do not copy local `bootstrap/cache/*.php` files. Persist `storage/app/public/` between releases.
3. Create a fresh `syncode` database and a dedicated account restricted to that database. The deployment account needs migration privileges; do not use MySQL root. Set a strong database password. Do not import the pre-cleanup local demo backup into a new production database.
4. Copy `.env.production.example` to `.env` **on the server**. Fill the real HTTPS `APP_URL`, database values and a blank `DB_SOCKET` unless that server requires a socket. Keep demo disabled and debug false. Generate `APP_KEY` once using `php artisan key:generate` after dependencies are installed. Preserve that key on every later release. Use actual proxy IPs in `TRUSTED_PROXIES` only if HTTPS terminates at a load balancer.
5. Give the PHP process write access only to `storage/` and `bootstrap/cache/`; keep source and `.env` restricted. Do not use permissions `777`. Apply `php.ini` upload limits and adapt `nginx.conf` to your PHP socket, real domain and existing TLS certificate. For cPanel, configure the domain document root to `.../SynCode/public` and select PHP 8.2+.
6. Run `bash deploy/release.sh`. It installs production dependencies, checks production configuration, migrates without resetting existing data, seeds categories, links public uploads and caches Laravel configuration/routes/views. It refuses local/debug/HTTP/root-database settings. No frontend build is required for the current direct public assets.
7. Run `php artisan syncode:admin` to create your own administrator through hidden password prompts. There is no built-in production password. Run `php artisan syncode:production-check`; resolve every FAIL before exposing the site.
8. Configure the scheduler (adjust paths):

   ```cron
   * * * * * cd /var/www/syncode && /usr/bin/php artisan schedule:run >> /var/log/syncode-scheduler.log 2>&1
   ```

   This publishes approved scheduled posts without waiting for page traffic. The current features do not require a background queue worker; configure one when adding queued jobs/email delivery.
9. Verify `/up`, the landing/feed empty state, registration, administrator login, author submission → moderation → publication, comment/report permissions, uploads and HTTPS-only cookies. The first registered public user remains an Author.

The health route is a process check; `syncode:production-check` also verifies database connectivity. Monitor application logs and scheduler output with restricted permissions.

## Updates, backups and rollback

Before an update, run `php artisan syncode:backup` and copy `storage/app/public/` to private backup storage. SQL backups are mode `0600` in a private directory. `DB_DUMP_BINARY` must point to your server’s `mysqldump` or `mariadb-dump`. The command uses a consistent transactional dump and keeps the database password out of command arguments. Copy backups off-server and verify restore procedures; no automatic backup schedule or retention has been installed.

Retain the previous release, `.env`, application key and uploaded files. For an update requiring downtime, use `php artisan down`, deploy the new release, then `php artisan up` after checks. A failed release script exits without declaring success. Restore code first when compatible. Restore SQL only after deciding which later writes must be preserved; the release script never runs `migrate:fresh` or automatically rolls back user data.

## Local operation

Local `.env` stays HTTP/XAMPP with debug output disabled. Role test credentials are explicitly enabled locally; sample story seeding remains disabled. It is intentionally different from production: `syncode:production-check` reports FAIL for local environment, HTTP URL, local root database credentials and the absence of a real administrator. This is expected until actual server credentials/domain are configured. `php artisan syncode:admin` works locally now.

## External services

Newsletter addresses are stored, but email campaigns are not sent. `MAIL_MAILER=log` intentionally prevents accidental email delivery; SMTP or an email provider must be configured for sending. External social login is not included. Neither feature is advertised as configured in the application.
