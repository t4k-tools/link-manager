# Link Manager

> *Was there really a need for yet another script to keep links organized? Forgive my presumption in wanting a public version, thinking it might be useful to someone.*

A small, dependency-free **PHP 8 + SQLite** application to collect, organize, search and share links. No framework, no Composer, no build step: copy the files, point it at an SQLite file, and run.

🇮🇹 Versione italiana: **[README.it.md](README.it.md)**

---

## Features

- **Authentication** with PHP sessions, `password_hash()`/`password_verify()`, session regeneration on login.
- **Roles**: `admin` (sees and manages everything) and `user` (manages only their own links).
- **Three-state visibility** per link:
  - `public` — visible to everyone, even without login;
  - `shared` — visible to all authenticated users;
  - `private` — visible only to the owner (and admins).
- **Tags**: add tags to links, click to filter, tag cloud, and a tag autocomplete in the forms.
- **Search**: full-text over description and tags, plus filter by type and by tag.
- **Card view** with pagination, both in the backend list and on the public page.
- **Public page** (`my_link.php`) listing only public links, with a sticky tag sidebar, plus a tag index page (`tags.php`).
- **Click tracking** with a safe redirect (only `http`/`https` URLs).
- **CSV export** of the visible links.
- **Security by default**: CSRF tokens on every state-changing POST, prepared statements (PDO), output escaping, hardened session cookies, no secrets in code, errors logged not shown in production.

## Tech stack

- PHP 8.1+ (PDO, `pdo_sqlite`)
- SQLite (a single file, kept outside the web root)
- Bootstrap 5 + Font Awesome (via CDN)
- Vanilla JavaScript (no frontend framework)

## Quick start

```bash
git clone git@github.com:t4k-tools/link-manager.git
cd link-manager

# 1) Local configuration (SQLite by default)
cp link/1/config.example.php link/1/config.local.php
#    set 'env' => 'dev' in config.local.php to see errors while developing

# 2) Create the database from the schema + a first admin user
php database/init_db.php admin "change-this-password" admin@example.com

# 3) Run the built-in dev server (document root = ./link)
./start_dev.command 8080
```

Then open `http://localhost:8080/login.php` and sign in with the admin user you created.
The public page is at `http://localhost:8080/my_link.php` (no login required).

## Configuration

`link/1/config.local.php` (git-ignored) holds the environment configuration. Example:

```php
<?php
declare(strict_types=1);
return [
    'env' => 'prod', // 'dev' shows errors on screen; 'prod' logs them only
    'db'  => [
        'driver' => 'sqlite',
        'path'   => __DIR__ . '/../../storage/link_manager.sqlite',
    ],
];
```

A legacy **MySQL** driver is also supported (`'driver' => 'mysql'` with host/dbname/username/password), useful when migrating an existing dataset.

## Project structure

```
link/                 application (document root)
  1/                  config, DB connection, session, CSRF, helpers, visibility
  src/                repositories (UserRepository, LinkRepository) — SQL isolated
  templates/          shared layout (header/footer)
  css/ js/ 2/         assets
  *.php               pages (login, dashboard, link, add/edit/delete, users, public...)
database/             schema_sqlite.sql, init_db.php, MySQL→SQLite import tool
storage/              the live SQLite file (git-ignored, outside the document root)
```

## Security notes

- Database file and secrets live **outside** the web root and are git-ignored.
- All write actions go through POST with a CSRF token; deletions are POST-only.
- Server-side authorization on every action (roles + ownership), not just hidden buttons.
- In production set `env => prod` (errors go to the log, never to the browser).

## License

Released for whoever may find it useful. No warranty.
