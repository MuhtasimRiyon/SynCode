# SynCode

A Laravel 12 developer community backed by MySQL/MariaDB. The local XAMPP application is available at **http://localhost/SynCode/public**. Database: **syncode**. Sessions also use the database.

## Setup

Requires PHP 8.2+, Composer, MySQL/MariaDB, and the PDO MySQL extension. Start Apache and MySQL in XAMPP. For a fresh installation, create an empty `syncode` database in phpMyAdmin, then run:

```sh
composer install
cp .env.example .env  # Fresh installations only; keep an existing .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

Set `APP_URL`, database credentials, and `DB_SOCKET` in `.env` for your machine. The current XAMPP socket is `/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock`. The working UI uses `public/css` and `public/js` directly and needs no Vite build.

## Features

- Public landing page, feed, category/tag search, latest and trending stories, and article reading.
- Registration, remembered login, logout, safe return URLs, and role-based workspaces.
- Account-owned drafts, story editing/deletion, Markdown preview/rendering, cover uploads, categories, tags, and series assignment.
- Moderated publication and scheduling in `Asia/Dhaka` by default (`APP_TIMEZONE` can override it). Pending, draft, rejected and scheduled stories are private to their author and moderators.
- Persistent likes, bookmarks, author follows, following feed, comments, comment deletion, reports, and activity notifications.
- Profile updates, unique usernames, bio/location, and account-specific reading preferences.
- Actual dashboard and community metrics, review/feature/reject/delete actions, report resolution, categories/tags, series creation, site branding/SEO, approval policy, and maintenance mode.
- Super Admin user-role and suspension controls. Suspended users cannot log in or continue authenticated sessions; their stories leave the public feed. An administrator cannot remove their own access or the last active administrator.
- Newsletter signup storage with duplicate protection. **Email campaigns and external social login are not configured.**

Scheduled stories requiring moderation remain pending until approved. Approved stories publish on the first application request at or after their publication time. To publish without waiting for traffic, run Laravel’s scheduler:

```sh
php artisan schedule:work
```

On a server, invoke `php artisan schedule:run` every minute through the system scheduler. `php artisan stories:publish` also processes due stories manually.

## Administrator and clean data

Sample stories have been removed from the working local database. Debug output is disabled. Three local test accounts are available through the login page’s Super Admin, Moderator and Author credential buttons; select a role, then click Sign in. Their emails are `admin@syncode.test`, `moderator@syncode.test`, and `author@syncode.test`; all use `SynCode@123`. These accounts are for local feature testing only. A private SQL backup was saved before cleanup under `storage/app/private/backups/`. No real administrator identity or password is invented.

Create your own administrator from the project terminal:

```sh
php artisan syncode:admin
```

This asks for your name, email and a hidden password with confirmation (at least 12 characters, mixed case, a number and a symbol). It creates a Super Admin without changing existing users. Ordinary registration always creates an Author.

With demo flags disabled, normal seeding creates only default categories. `DEMO_ACCOUNTS_ENABLED=true` enables local test accounts and their credential buttons. Sample stories additionally require `DEMO_CONTENT_ENABLED=true`; enabling login buttons alone does not recreate blogs. Production always refuses demo seeding. The opt-in development accounts are defined in `app/Support/DemoAccounts.php`. Never enable them on a public server.

```sh
php artisan syncode:demo-clean           # Preview known sample data
php artisan syncode:demo-clean --apply   # Back up MySQL before cleanup
php artisan syncode:backup              # Private SQL backup
```

Cleanup preserves edited sample stories and unrelated users. Accounts with remaining contributions are preserved; known demo logins with remaining contributions are disabled.

## Server deployment

The repository is prepared locally; it has not been deployed to a remote server. Use [the deployment guide](deploy/README.md), `.env.production.example`, and the Nginx/PHP templates in `deploy/`. Keep the local `.env` for local development. Production requires the real domain, HTTPS, a database user/password and a real administrator.

## Database handling

Migrations add community tables without replacing existing users or sessions. Inspect them through phpMyAdmin:

- `users`, `posts`, `comments`, `likes`, `bookmarks`, `follows`
- `community_notifications`, `reports`, `taxonomies`, `series`
- `site_settings`, `newsletter_subscriptions`

Foreign keys clean up post interactions when a story is deleted. Composite unique indexes prevent duplicate likes, bookmarks and follows. Transactions protect updates and concurrent reaction changes. Cover files live in `storage/app/public/covers`, are exposed through `public/storage`, and are removed when replaced or when their story is deleted. Upload validation accepts JPG, PNG, WebP and GIF up to 8 MB. Markdown strips raw HTML and unsafe links.

## Verification

```sh
php artisan test
node --check public/js/community.js
```

Tests use an isolated in-memory SQLite database and cover authentication, permissions, author ownership, publication/scheduling, interaction persistence and counts, reports, notifications, settings, suspension, maintenance, uploads, series filtering, and session-deduplicated reads. They do not reset the local MySQL database.
