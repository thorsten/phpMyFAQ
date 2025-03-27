# phpMyFAQ 4.1-dev

![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/thorsten/phpMyFAQ)
![GitHub](https://img.shields.io/github/license/thorsten/phpMyFAQ)
![GitHub commit activity](https://img.shields.io/github/commit-activity/m/thorsten/phpMyFAQ)
[![Documentation Status](https://readthedocs.org/projects/phpmyfaq/badge/?version=latest)](https://phpmyfaq.readthedocs.io/en/latest/?badge=latest)

## What is phpMyFAQ?

phpMyFAQ is a multilingual, completely database-driven FAQ-system. It supports various databases to store all data; PHP
8.2+ is needed to access this data. phpMyFAQ also offers a multi-language Content Management System with a WYSIWYG
editor and a media manager, real time search support with Elasticsearch, flexible multi-user support with user
and group based permissions on categories and records, a wiki-like revision feature, a news system, user-tracking, 40+
supported languages, enhanced automatic content negotiation, HTML5/CSS3 based responsive templates, PDF-support, a
backup and restore system, a dynamic sitemap, related FAQs, tagging, enhanced SEO features, built-in spam protection
systems, Microsoft Entra ID, Microsoft Active Directory and OpenLDAP support, and an easy-to-use installation and update
script.

## Requirements

phpMyFAQ is only supported on PHP 8.2 and up, you need a database as well. Supported databases are MySQL, MariaDB,
Percona Server, PostgreSQL, Microsoft SQL Server and SQLite3. If you want to use Elasticsearch as the main search
engine, you need Elasticsearch 6.x or later. Check our detailed requirements on
[phpmyfaq.de](https://www.phpmyfaq.de/requirements) for more information.

## Installation

### phpMyFAQ installation package for end-users

The best way to install phpMyFAQ is to download it on [phpmyfaq.de](https://www.phpmyfaq.de/download), unzip the package
and open http://www.example.org/phpmyfaq/setup/ in your preferred browser.

### phpMyFAQ installation with Docker

#### Dockerfile

The Dockerfile provided in this repo only builds an environment to run any release for development purpose.
It does not contain any code
as the phpmyfaq folder is meant to be mounted as the `/var/www/html` folder in the container.

#### docker-compose.yml

For development purposes, you can start a full stack to run your current PhpMyFAQ source code from your local repo.

    $ docker-compose up

The command above starts nine containers for multi database development as following.

_Specific images started once to prepare the project:_

- **composer**: update composer dependencies
- **pnpm**: update pnpm dependencies

_Running using named volumes:_

- **mariadb**: image with MariaDB database with xtrabackup support
- **phpmyadmin**: a PHP tool to have a look at your MariaDB database.
- **postgres**: image with PostgreSQL database
- **pgadmin**: a PHP tool to have a look at your PostgreSQL database.
- **sqlserver**: image with Microsoft SQL Server for Linux
- **elasticsearch**: Open Source Software image (it means it does not have XPack installed)

_Running apache web server with PHP 8.4 support:_

- **apache**: mounts the `phpmyfaq` folder in place of `/var/www/html`.

_Running nginx web server with PHP 8.4 support:_

- **nginx**: mounts the `phpmyfaq` folder in place of `/var/www/html`.
- **php-fpm**: PHP-FPM image with PHP 8.4 support

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

Then just open http://www.example.org/phpmyfaq/setup/ in your browser.

## Testing

### PHP

To run our unit tests via PHPUnit v11.x, just execute this command on your CLI

    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install
    $ ./vendor/bin/phpunit

Please note that phpMyFAQ needs to be installed via Composer.

### Javascript

To run our Javascript tests via Jest, just execute this command on your CLI

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
- New additions without breaking backward compatibility bumps the minor (and resets the patch)
- Bug fixes and misc changes bumps the patch

For more information on SemVer, please visit http://semver.org/.

## Issue tracker

Found a bug, or do you have a feature request? [Please open a new issue](https://github.com/thorsten/phpMyFAQ/issues).
Before opening any issue, please search for existing issues.

## Contributing

Please check out our page about contributing on [phpmyfaq.de](https://www.phpmyfaq.de/contribute).

## Documentation

You can read the complete documentation on [here](https://phpmyfaq.readthedocs.io/en/latest/).

## REST API v3.1 documentation

The REST API documentation is available as OpenAPI 3.0 specification:

- [JSON](docs/openapi.json)
- [YAML](docs/openapi.yaml)

The Swagger UI is available at [https://api-docs.phpmyfaq.de/](https://api-docs.phpmyfaq.de/).

## Discord server

If you like to chat with the phpMyFAQ team, please join our [Discord server](https://discord.gg/wszhTceuNM).
We're happy to help you with your questions!

## License

Mozilla Public License 2.0, see LICENSE for more information.

Copyright © 2001–2025 Thorsten Rinne and the phpMyFAQ Team
