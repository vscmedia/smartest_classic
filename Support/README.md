# Smartest Classic

Smartest Classic is a PHP publishing platform and content management system that began in 2006 as a collaboration between Marcus Gilroy-Ware and Eddie Tejeda. Motivated by the creative possibilities of the Hypertext medium, it was first built in the PHP 4 era, using Smarty 2, and it predates Composer, Symfony, namespaces, modern autoloading, and many of the conventions that later became normal in PHP applications.

Even so, Smartest was built around strict code organisation from the start. In addition to its co-evolution with the Quince controller package, its structure was influenced by modern operating-system design: everything object-oriented, ultra-specific class names, unified libraries and toolkits with no code repetition, anticipate and cushion user errors, a separated system layer, a controller and presentation layer, application modules, reusable helpers, cached output, and - thanks to Quince - self-contained modules that can be moved in or out without breaking the rest of the application.

Smartest was actively maintained and developed until around 2020, when its lead developers became too busy to continue the work. In 2026, the project was rescued with the help of generative AI and Codex, upgraded for modern PHP, Composer-managed dependencies, Smarty 5, PDO-style database access (work in progress), and a cleaner path toward long-term maintenance.

This repository is now offered to the world once again by its sponsor, [VSC Media International](https://www.vscmedia.com/) - not only for posterity, but because some of the problems Smartest was designed to solve are still real. Its flexible content architecture, full metadata engine, multiple sites in one install, page elements, templates, Build Kits automation, and strict separation between system code, project code, public files, and presentation files still offer a different set of trade-offs from WordPress, Drupal, Squarespace, and newer hosted builders.

Unlike the plugins model of Wordpress, Smartest is designed for clients and developers who know what they want, and won't force you to edit other people's code. You can start with very basic elements and easily incorporate flat HTML/CSS designs and javascript functionalities of your own creation. However, for users that find that freedom daunting, Smartest now includes a completed implementation of a long-planned feature: Build Kits, which are scripts that will rapidly build out a site for you, including all templates, media, CSS, Javascript, and data structures. You can read more about these below.

## System Requirements

Smartest Classic expects a conventional PHP web-server environment:

- PHP 8.3 or later.
- Composer 2.
- MySQL or MariaDB.
- Apache with `mod_rewrite`, or another web server with equivalent URL rewriting.
- A virtual host whose document root points at the repository's `Public/` directory.
- File permissions that allow the web server to write cache, log, uploaded-file, generated-site, and generated-template directories.

The distinction between the Smartest root and the served document root is important:

- The Smartest application root is the repository root.
- The web server document root must be `Public/`.
- Requests are routed through `Public/index.php`.
- The rest of the repository must not be directly web-accessible.

For Apache, the virtual host should look broadly like this:

```apache
<VirtualHost *:80>
    ServerName mysmartestwebsite.org
    ServerAlias myothersmartestwebsite.com
    DocumentRoot "/path/to/smartest/Public/"

    <Directory "/path/to/smartest/Public/">
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>
</VirtualHost>
```

Since Smartest supports multiple websites per install, each website is identified by a separate hostname.

An example local Apache vhost is provided in `Support/php8-local-apache-vhost.conf.example`.

## Install From Git

Clone the repository and install Composer dependencies:

```bash
git clone --branch smartest-php8-2026 https://github.com/vscmedia/smartest_classic.git
cd smartest_classic
composer install
```

Create an empty database and a database user with permission to create and alter tables in that database.

For example, from a MySQL root session:

```sql
CREATE DATABASE smartest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'smartest'@'localhost' IDENTIFIED BY 'change-this-password';
GRANT ALL PRIVILEGES ON smartest.* TO 'smartest'@'localhost';
FLUSH PRIVILEGES;
```

Configure your web server so the virtual host points to `Public/`, then visit the host in a browser. The installer will guide you through database configuration, initial user creation, and the first site setup.

The Smartest Installer is the authoritative setup process. It creates the local runtime files it needs, including:

- `Configuration/database.yml`
- `Public/.htaccess`
- site-specific files under `Sites/`

These files are intentionally local runtime files and are not committed to Git.

## URL Rewriting

Smartest does not only serve `.php` URLs. Human-friendly URLs, CMS routes, assets, previews, interface actions, and module URLs all depend on request rewriting. Once this is working, websites can use highly flexible URLs, including and shortcut/forward URLs.

For Apache, make sure `mod_rewrite` is enabled and that the `Public/` directory allows `.htaccess` rules:

```apache
AllowOverride All
```

The installer should create `Public/.htaccess`. If URL rewriting is not active, or if the virtual host is not pointed at `Public/`, the installer and the running CMS will not behave correctly.

## Installer-Created Runtime Files

Smartest is designed to create its runtime configuration through the browser-based installer, which will trigger when the Public/ folder is accessed via the corresponding VirtualHost for the first time. On a normal installation, it is not necessary to create `Configuration/database.yml`, `Public/.htaccess`, or site-specific files by hand before running the installer.

The installer will ask for database connection details, create the required tables, create the first user, and prepare the first site. It may also generate a one-off permissions repair script in `/tmp` if it detects directories that the web server cannot write to.

For specialised local development work only, this repository includes a helper script to bypass the installer:

```bash
Support/php8-local-bootstrap.sh --files-only
```

This can create ignored runtime files such as `Configuration/database.yml`, `Public/.htaccess`, and starter site directories, but it is not the normal installation path.

The same script also has an optional database seeding mode for PHP 8 migration testing:

```bash
Support/php8-local-bootstrap.sh --seed-db
```

Useful environment variables include:

```bash
SM_LOCAL_HOSTNAME=smartest.localhost
SM_DB_HOST=127.0.0.1
SM_DB_NAME=smartest
SM_DB_USER=smartest
SM_DB_PASSWORD=change-this-password
MYSQL_ADMIN_USER=root
MYSQL_ADMIN_PASSWORD=
```

## Fix Permissions

Smartest writes to several locations at runtime: cache directories, logs, generated object model files, uploaded assets, site folders, presentation folders, and temporary files.

During installation, the Smartest Installer may generate a one-off permissions repair script in `/tmp`. If it does, review it and run it as root, then return to the installer and continue.

This repository also includes a bundled maintenance script that can be used after installation or when repairing an existing checkout:

```bash
sudo Support/Maintenance/smartest-fix-permissions.sh
```

Run this command as root from within the Smartest application root directory, not from `Public/`.

The bundled script creates missing writable directories, sets group ownership to the configured web-server group, applies group-writable permissions, and clears stale temporary cache files. On systems where the web-server group is not `www-data`, edit the `TARGET_GROUP` value in the script before running it.

## Build Kits

Smartest can start with a completely empty project, but that flexibility can be daunting. Build Kits were designed to solve that problem.

A Build Kit is a code-free setup package that can create the first pieces of a site:

- pages and page structures
- templates and layouts
- files and assets
- data models and properties
- placeholders and containers
- starter content
- optional site settings

The bundled Build Kits live in `System/Install/BuildKits/`. Current examples include:

- Basic blog
- Informational website

During site creation, choose a Build Kit if you want Smartest to create a working starting point instead of an empty site. The created files, templates, models, and pages are normal Smartest content afterwards. They are not locked to the Build Kit and can be edited, deleted, renamed, or extended through the usual interface.

Build Kits are designed to respect Smartest's multi-site isolation. If the same kit is run more than once, created disk files should be given unique names where needed, and database objects should be created in the context of the new site unless explicitly shared.

## First Site Checklist

After installation:

1. Log in to `/smartest`.
2. Create or select your first site.
3. If prompted, choose a Build Kit such as Basic blog or Informational website.
4. Open Site Settings and confirm the hostname, site name, templates, and special pages.
5. Open Website Manager and check the page tree.
6. Open Page Elements for the home page and define any required placeholders or containers.
7. Open Assets and upload or create initial images, text assets, stylesheets, and scripts.
8. Preview the site before publishing changes.

## Project Layout

Important directories:

- `Public/`: the only directory served by the web server.
- `System/`: Smartest core code, applications, helpers, templates, configuration, install assets, and cache.
- `System/Library/`: Composer vendor directory plus first-party Quince code.
- `Library/`: project-level first-party extension code such as API classes, actions, and object model files.
- `Applications/`: custom or generated application modules.
- `Presentation/`: shared site presentation files such as masters and layouts.
- `Sites/`: local generated site-specific configuration and code.
- `Support/`: documentation, examples, and maintenance scripts.

## Composer

Smartest Classic now uses Composer for third-party PHP libraries. The Composer vendor directory is configured as:

```json
"vendor-dir": "System/Library/vendor"
```

That keeps third-party dependencies inside Smartest's existing system-library area while still using normal Composer workflows.

After cloning or pulling dependency changes, run:

```bash
composer install
```

Do not commit `System/Library/vendor/`.

## Licence

Smartest Classic is intended to be distributed under the GNU General Public License v3 or later.

Composer expresses this as:

```json
"license": "GPL-3.0-or-later"
```
