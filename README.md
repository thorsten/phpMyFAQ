# phpMyFAQ 4.2-dev

![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/thorsten/phpMyFAQ)
![GitHub](https://img.shields.io/github/license/thorsten/phpMyFAQ)
![GitHub commit activity](https://img.shields.io/github/commit-activity/m/thorsten/phpMyFAQ)
[![Documentation Status](https://readthedocs.org/projects/phpmyfaq/badge/?version=latest)](https://phpmyfaq.readthedocs.io/en/latest/?badge=latest)

## What is phpMyFAQ?

phpMyFAQ is a comprehensive, multilingual FAQ system that is entirely database-driven.
It is compatible with a variety of databases for data storage and requires PHP 8.4+ for data access.
The system features a multi-language Content Management System equipped with a WYSIWYG editor and an Image Manager.
It also provides real-time search capabilities with Elasticsearch or OpenSearch.

phpMyFAQ supports flexible multi-user functionality, offering user and group-based permissions on categories and FAQs.
It includes a wiki-like revision feature, a news system, and configurable user-tracking.
Administrators can monitor user activities through detailed log files.
Additionally, phpMyFAQ supports adding its own custom pages to the FAQ system.
With support for over 40 languages, it also boasts enhanced automatic content negotiation and HTML5- / CSS3-based
responsive templates.
These Twig-based templates allow for the inclusion of your own text and HTML snippets. There's also a built-in plugin
system for further customization.

Additional features include PDF support, a backup system, a dynamic sitemap, related FAQs, tagging, a plugin system,
and built-in spam protection systems.
phpMyFAQ also supports two-factor authentication (2FA) for enhanced security.
A REST API is available for integration with other systems.
It also supports OpenLDAP, Microsoft Active Directory, Microsoft Entra ID, and an MCP Server for AI agents.
The system is easy to install, thanks to its user-friendly installation script.

phpMyFAQ is versatile
and can be run on almost any web hosting provider or deployed in the cloud using a Docker container.

## Requirements

phpMyFAQ is only supported on PHP 8.4+, you need a database as well. Supported databases are MySQL, MariaDB,
Percona Server, PostgreSQL, Microsoft SQL Server, and SQLite3. If you want to use Elasticsearch or Opensearch as the 
main search engine, you need Elasticsearch v6+ or OpenSearch v1+. Check our detailed requirements on
[phpmyfaq.de](https://www.phpmyfaq.de/requirements) for more information.

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

For development purposes, you can start a full stack to run your current PhpMyFAQ source code from your local repo.

    $ docker-compose up

The command above starts nine containers for multi-database development as follows.

_Specific images started at once to prepare the project:_

- **composer**: update composer dependencies
- **pnpm**: update pnpm dependencies

_Running using named volumes:_

- **mariadb**: image with MariaDB database with xtrabackup support
- **phpmyadmin**: a PHP tool to have a look at your MariaDB database.
- **postgres**: image with PostgreSQL database
- **pgadmin**: a PHP tool to have a look at your PostgreSQL database.
- **sqlserver**: image with Microsoft SQL Server for Linux
- **elasticsearch**: Open Source Software image (it means it does not have XPack installed)
- **opensearch**: OpenSearch image (it means it does not have XPack installed)
- **redis**: image with a Redis database

_Running apache web server with PHP 8.5 support:_

- **apache**: mounts the `phpmyfaq` folder in place of `/var/www/html`.

_Running nginx web server with PHP 8.5 support:_

- **nginx**: mounts the `phpmyfaq` folder in place of `/var/www/html`.
- **php-fpm**: PHP-FPM image with PHP 8.5 support

_Running FrankenPHP web server with PHP 8.5 support:_

- **frankenphp**: mounts the `phpmyfaq` folder in place of `/var/www/html`.

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

##### macOS with Docker for Mac

The vm.max_map_count setting must be set within the xhyve virtual machine:

    $ screen ~/Library/Containers/com.docker.docker/Data/com.docker.driver.amd64-linux/tty

Log in with root and no password. Then configure the sysctl setting as you would for Linux:

    $ sysctl -w vm.max_map_count=262144

##### Windows and macOS with Docker Toolbox

The vm.max_map_count setting must be set via docker-machine:

    $ docker-machine ssh
    $ sudo sysctl -w vm.max_map_count=262144

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

To run our unit tests via PHPUnit v12.x, execute this command on your CLI

    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install
    $ ./vendor/bin/phpunit

Please note that phpMyFAQ needs to be installed via Composer.

### TypeScript

To run our TypeScript tests via Vitest, execute this command on your CLI

    $ curl -fsSL https://get.pnpm.io/install.sh | sh -
    $ pnpm install
    $ pnpm test

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

## REST API v3.1 documentation

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
