# phpMyFAQ

![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/thorsten/phpMyFAQ)
![GitHub](https://img.shields.io/github/license/thorsten/phpMyFAQ)
![GitHub commit activity](https://img.shields.io/github/commit-activity/m/thorsten/phpMyFAQ)
[![Documentation Status](https://readthedocs.org/projects/phpmyfaq/badge/?version=latest)](https://phpmyfaq.readthedocs.io/en/latest/?badge=latest)

## What is phpMyFAQ?

phpMyFAQ is a multilingual, AI-ready, and scalable FAQ platform built for modern knowledge management.
Powered by PHP 8.4+ and a fully database-driven architecture, it delivers fast search with Elasticsearch/OpenSearch, 
flexible multi-user permissions, and a powerful content management system with revision history and WYSIWYG editing.

With 40+ languages, responsive Twig-based templates, a REST API, 2FA security, enterprise authentication 
(LDAP, Active Directory, Entra ID), and a built-in plugin system, phpMyFAQ integrates seamlessly into almost any 
environment.

Deploy it on traditional hosting or run it in the cloud via Docker.

## Requirements

phpMyFAQ requires PHP 8.4 or higher and a supported database system.
Supported databases include MySQL, MariaDB, Percona Server, PostgreSQL, Microsoft SQL Server, and SQLite3.

For enhanced search capabilities using Elasticsearch or OpenSearch, Elasticsearch 8.x or later or OpenSearch 2.x or 
later is required.

For a complete and up-to-date list of system requirements, please refer to the official documentation at
[phpmyfaq.de](https://www.phpmyfaq.de/requirements).

## Installation

### phpMyFAQ installation package for end-users

The best way to install phpMyFAQ is to download it on [phpmyfaq.de](https://www.phpmyfaq.de/download), unzip the package,
and open http://www.example.org/phpmyfaq/setup/ in your preferred browser.

### phpMyFAQ installation with Docker

#### Dockerfile

The Dockerfile provided in this repo only builds an environment to run any release for development purpose.
It does not contain any code
as the phpmyfaq folder is meant to be mounted as the `/var/www/html` folder in the container.

#### docker-compose.yml

For development purposes, you can run your current phpMyFAQ source code from your local repo. Every service is
gated behind a [Compose profile](https://docs.docker.com/compose/profiles/), so you pick exactly the combination
you need (one web server + the databases and search engines you want to test against).

The easiest way is the `bin/dev` helper, which maps friendly names to profiles and adds guardrails (it refuses to
start two web servers on the same port, auto-adds Redis, generates self-signed dev TLS certificates, and
installs dependencies on first run):

    $ bin/dev up nginx mariadb opensearch         # exactly these (Redis added automatically)
    $ bin/dev up apache postgres elasticsearch --tools
    $ bin/dev preset default                      # nginx + mariadb + opensearch + redis
    $ bin/dev preset full                         # one web server + every backing service
    $ bin/dev down                                # stop and remove the running stack
    $ bin/dev ps | logs [service] | shell <service>

Run `bin/dev help`, `bin/dev presets`, or `bin/dev profiles` for the full list. The same commands are available
through pnpm: `pnpm dev:up`, `pnpm dev:down`, `pnpm dev:default`, `pnpm dev:full`, etc.

If you prefer raw Compose, pass the profiles yourself — a bare `docker compose up` now starts nothing, because no
service is in the default profile:

    $ docker compose --profile nginx --profile mariadb --profile opensearch --profile redis up

The available services (grouped by their profile) are:

_Web servers — pick one (all bind `:443`), each with PHP 8.5 support:_

- **nginx** (`nginx`): mounts `phpmyfaq` as `/var/www/html`, served via the **php-fpm** container.
- **apache** (`apache`): mounts `phpmyfaq` as `/var/www/html`.
- **frankenphp** (`frankenphp`): mounts `phpmyfaq` as `/var/www/html`, with HTTP/3 support.

_Databases — pick any:_

- **mariadb** (`mariadb`): MariaDB database (reachable under the `db` network alias).
- **postgres** (`postgres`): PostgreSQL database.
- **sqlserver** (`mssql`): Azure SQL Edge image (runs natively on Apple Silicon and x86).

_Search engines — pick any:_

- **elasticsearch** (`elasticsearch`): Open Source Software image (no XPack installed).
- **opensearch** (`opensearch`): OpenSearch image (no XPack installed).

_Cache:_

- **redis** (`redis`): Redis database (added automatically by `bin/dev` unless `--no-redis`).

_Admin tools (`--tools`, or the `phpmyadmin` / `pgadmin` profiles):_

- **phpmyadmin** (`phpmyadmin`): a PHP tool to inspect your MariaDB database.
- **pgadmin** (`pgadmin`): a PHP tool to inspect your PostgreSQL database.

_Dependency bootstrap (`build` profile, run once via `bin/dev build`):_

- **composer**: installs the Composer (PHP) dependencies.
- **pnpm**: installs the pnpm (TypeScript) dependencies and builds the assets.

Then services will be available at the following addresses:

- phpMyFAQ: (https://localhost:443 or http://localhost:8080 as fallback)
- phpMyAdmin: (http://localhost:8000)
- pgAdmin: (http://localhost:8008)

#### Running tests

To run the test using Docker, you have to install the Composer development dependencies

    $ docker build -t phpmyfaq-test . && docker run --rm -it docker.io/library/phpmyfaq-test:latest 

#### Quote from Elasticsearch documentation

The vm.max_map_count kernel setting needs to be set to at least 262144 for production use. Depending on your platform:

##### Linux

The vm.max*map_count setting should be set permanently in */etc/sysctl.conf\_:

    $ grep vm.max_map_count /etc/sysctl.conf
    vm.max_map_count=262144

To apply the setting on a live system type: `sysctl -w vm.max_map_count=262144`

##### macOS and Windows with Docker Desktop

Docker Desktop runs containers inside a managed Linux VM. Open a shell in that VM and apply the setting there:

    $ docker run --rm --privileged --pid=host alpine nsenter -t 1 -m -u -n -i sysctl -w vm.max_map_count=262144

This change is not persistent and must be reapplied after restarting Docker Desktop.

### phpMyFAQ local installation from Github

To run phpMyFAQ locally, you need at least a running web server with PHP support and a database.

    $ git clone git://github.com/thorsten/phpMyFAQ.git
    $ cd phpMyFAQ
    $ git checkout main
    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install
    $ curl -fsSL https://get.pnpm.io/install.sh | sh -
    $ pnpm install
    $ pnpm build

Then open http://www.example.org/phpmyfaq/setup/ in your browser.

## Testing

### PHP

To run our unit tests via PHPUnit v13.x, execute this command on your CLI

    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install
    $ composer test

Please note that phpMyFAQ needs to be installed via Composer.

### TypeScript

To run our TypeScript tests via Vitest, execute this command on your CLI

    $ curl -fsSL https://get.pnpm.io/install.sh | sh -
    $ pnpm install
    $ pnpm test

### End-to-end (Playwright)

The end-to-end suite drives a real browser against a freshly installed instance
that is seeded with bilingual (German + English) test data. Provisioning,
serving, and teardown are fully automated by the `bin/e2e` helper, so no manual
setup or existing installation is required — your local configuration is backed
up and restored automatically.

Install the browser once, then run the suite:

    $ pnpm install
    $ pnpm e2e:install                # downloads the Chromium browser
    $ pnpm e2e:local                  # SQLite + built-in PHP server (fast)
    $ pnpm e2e:docker                 # dedicated MariaDB container (production-like)

`bin/e2e` installs phpMyFAQ headlessly via `php bin/console phpmyfaq:install`,
seeds the test data with `php bin/console phpmyfaq:seed-testdata`, serves the
app, and runs Playwright. Pass extra arguments through to Playwright after `--`,
for example `./bin/e2e local -- tests/e2e/search.spec.ts`. The specs live in
`tests/e2e/` and cover bilingual content, search, admin login, and basic
accessibility.

The suite also runs on every pull request and nightly in CI
(`.github/workflows/e2e.yml`) using the SQLite path, uploading the Playwright
HTML report and failure traces as build artifacts.

## Versioning

For transparency and insight into our release cycle, and for striving to maintain backward compatibility, phpMyFAQ will
be maintained under the Semantic Versioning guidelines as much as possible.

Releases will be numbered with the following format:

`<major>.<minor>.<patch>`

And constructed with the following guidelines:

- Breaking backward compatibility bumps the major (and resets the minor and patch)
- New additions without breaking backward compatibility bump the minor (and reset the patch)
- Bug fixes and misc changes bump the patch

For more information on SemVer, please visit http://semver.org/.

## Issue tracker

Found a bug, or do you have a feature request? [Please open a new issue](https://github.com/thorsten/phpMyFAQ/issues).
Before opening any issue, please search for existing issues.

## Contributing

Please check out our page about contributing on [phpmyfaq.de](https://www.phpmyfaq.de/contribute).

## Documentation

You can read the complete documentation on [here](https://phpmyfaq.readthedocs.io/en/latest/).

## REST API documentation

The REST API documentation is available as an OpenAPI 3.0 specification:

- [JSON](docs/openapi.json)
- [YAML](docs/openapi.yaml)

The Swagger UI is available at [https://api-docs.phpmyfaq.de/](https://api-docs.phpmyfaq.de/).

## Discord server

If you like to chat with the phpMyFAQ team, please join our [Discord server](https://discord.gg/wszhTceuNM).
We're happy to help you with your questions!

## License

Mozilla Public License 2.0, see LICENSE for more information.

Copyright © 2001–2026 Thorsten Rinne and the phpMyFAQ Team
