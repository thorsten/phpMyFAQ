# 6. Developer documentation

## 6.1 Customizing phpMyFAQ

phpMyFAQ users have even more customization opportunities. The key feature is the user-selectable template sets, there
is a templates/default directory where the default layouts get shipped.

In phpMyFAQ code and layout are separated. The layout is based on several template files, that you can modify to suit
your own needs. The most important files for phpMyFAQ's default layout can be found in the directory
_assets/templates/default/_. All original templates are valid HTML5 based on Bootstrap v5.3.

### 6.1.1 Creating a custom layout

Follow these steps to create a custom template set:

- copy the directory assets/templates/default to assets/themes/example
- adjust template files in assets/templates/example to fit your needs
- activate "example" within Admin->Config->Main

**Note:** There is a magic variable _{{ tplSetName }}_ containing the name of the actual layout available in each
template file.

### 6.1.2 DEBUG mode

If you want to see possible errors or the log of the SQL queries, you can enable the hidden DEBUG mode. To do so, please
set the following code in src/Bootstrap.php:

`const DEBUG = true;`

## 6.2 Templating

### 6.2.1 Introduction

phpMyFAQ v4 and later uses Twig, a modern template engine for PHP. It is fast, secure, and flexible.
This documentation provides an overview of how to use Twig in your projects.

The default layout of phpMyFAQ is saved in the **assets/templates/default/index.twig** file.

### 6.2.2 Template files

#### Variables

In Twig templates, variables are enclosed in double curly braces.

```twig
Hello, {{ name }}!
```

#### Filters

Filters modify the value of a variable. They are applied using the pipe (`|`) symbol.

```twig
{{ name | upper }}
```

#### Functions

Functions are used to perform tasks. Twig comes with many built-in functions.

```twig
{{ date('now') }}
```

#### Control Structures

##### If Statement

The `if` statement evaluates a condition and renders content based on its truthiness.

```twig
{% if name %}
    Hello, {{ name }}!
{% else %}
    Hello, Stranger!
{% endif %}
```

##### For Loop

The `for` loop iterates over arrays or objects.

```twig
{% for user in users %}
    <p>{{ user.name }}</p>
{% endfor %}
```

#### Debugging

To enable debugging in Twig, set the `DEBUG` option in the file src/Bootstrap.php to `true`.
You can then use the `dump` function in your templates.

```twig
{{ dump(variable) }}
```

#### Conclusion

For more detailed information, visit the [Twig documentation](https://twig.symfony.com/doc/).

### 6.2.2 Admin backend templates

The admin backend templates are located in the **assets/templates/admin** directory.

## 6.3 REST APIs

phpMyFAQ offers interfaces to access phpMyFAQ installations with other clients like the iPhone App. phpMyFAQ includes a
REST API and offers APIs for various services like fetching the phpMyFAQ version or doing a search against the
phpMyFAQ installation.

The API documentation can be found at [https://api-docs.phpmyfaq.de/](https://api-docs.phpmyfaq.de/).

## 6.4 phpMyFAQ development

Since phpMyFAQ is an Open Source project, we encourage developers to contribute patches and code for us to include in
the main package of phpMyFAQ.
However, there are a few rules and limitations when doing so and this page lists them.

1. Contributed code will be licensed under the MPL 2.0 license.
2. Copyright notices will be changed to phpMyFAQ Team. But contributors will get credit for their work!
3. All third party code will be reviewed, tested and possible modified before being released.

These basic rules makes it possible for us to earn a living of the phpMyFAQ project, but it also ensures that the code
remains Open Source and under the MPL 2.0 license.
All contributions will be added to the changelog and on the phpMyFAQ website.

### 6.4.1 How to contribute?

Contributing to phpMyFAQ is quite easy: just fork the [project on GitHub](https://github.com/thorsten/phpMyFAQ),
work on your copy and send pull requests.

### 6.4.2 Setup a local phpMyFAQ development environment

Before working on phpMyFAQ, set up a local environment with the following software:

- Git
- PHP 8.2+
- PHPUnit 10.x
- Composer
- Node.js v18+
- PNPM
- Docker

### 6.4.3 Configure your Git installation

Set up your user information with your real name and a working e-mail address:

    $ git config --global user.name "Your Name"
    $ git config --global user.email you@example.com
    $ git config core.autocrlf # if you're on Windows

### 6.4.4 How to get the phpMyFAQ source code?

Clone your forked phpMyFAQ repository locally:

    $ git clone git@github.com:USERNAME/phpMyFAQ.git

Add the upstream repository as remote:

    $ cd phpMyFAQ
    $ git remote add upstream git://github.com/thorsten/phpMyFAQ.git

Please check our [coding standards](https://www.phpmyfaq.de/docs/standards) before sending patches or pull requests.
Every PR on GitHub will check the coding standards and tests as well.

### 6.4.5 Run Docker Compose

The Dockerfile provided in the phpMyFAQ repository only builds an environment
to run any release for development purpose.
It does not contain any code as the phpmyfaq folder is meant to be mount as the /var/www/html folder in the container.

For development purposes, you can start a full stack to run your current phpMyFAQ source code from your local repository.

    $ docker-compose up

The command above starts 9 containers for multi database development as following.

_Specific images started once to prepare the project:_

- **composer**: update Composer dependencies
- **pnpm**: update PNPM dependencies

_Running using named volumes:_

- **mariadb**: image with MariaDB database with xtrabackup support
- **phpmyadmin**: a PHP tool to have a look on your MariaDB database.
- **postgres**: image with PostgreSQL database
- **pgadmin**: a PHP tool to have a look on your PostgreSQL database.
- **sqlserver**: image with Microsoft SQL Server for Linux
- **elasticsearch**: Open Source Software image (it means it does not have XPack installed)

_Running apache web server with PHP 8.3 support:_

- **phpmyfaq**: mounts the `phpmyfaq` folder in place of `/var/www/html`.

Then services will be available at the following addresses:

- phpMyFAQ: (http://localhost:8080 or https://localhost:443)
- phpMyAdmin: (http://localhost:8000)
- pgAdmin: (http://localhost:8008)

### 6.4.6 Fetch 3rd party libraries and install phpMyFAQ

After cloning your forked repository, you have to fetch the 3rd party libraries used in phpMyFAQ:

    $ cd phpMyFAQ
    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install
    $ pnpm install
    $ pnpm build

Then just start a normal, local phpMyFAQ installation.

If you change some JavaScript code, you have to re-build the .js files into one with the following PNPM task:

    $ pnpm build

During development, you can use the watch mode:

    $ pnpm build:watch

To run the PHPUnit-based tests, you can use the following command:

    $ composer test

To run the Jest-based tests, you can use the following command:

    $ pnpm test

### 6.4.7 Rebase your Patch

Before submitting your patch, please update your local branch:

    $ git checkout 4.0
    $ git fetch upstream
    $ git merge upstream/4.0
    $ git checkout YOUR_BRANCH_NAME
    $ git rebase 4.0

### 6.4.8 Make a Pull Request

You can now make a pull request on the phpMyFAQ GitHub repository.

## 6.5 Twig Extensions

phpMyFAQ uses the Twig template engine for the frontend and the backend. We have added some custom extensions to Twig to
make it easier to work with phpMyFAQ.

### 6.5.1 Category Name

The category name extension is used to get the name of a category by its ID.

Example:

    {{ categoryId | categoryName }}

### 6.5.2 FAQ question

The FAQ question extension is used to get the question of a FAQ entry by its ID.

Example:

    {{ faqId | faqQuestion }}

### 6.5.3 Formatting bytes

The format bytes extension is used to format a number of bytes to a human-readable format.

Example:

    {{ bytes | formatBytes }}

### 6.5.4 Format date

The format date extension is used to format a date to a human-readable format.

Example:

    {{ date | formatDate }}

### 6.5.5 ISO date format

The ISO date format extension is used to format a date to an ISO date format.

Example:

    {{ date | createIsoDate }}

### 6.5.6 Language code

The language code extension is used to get the language name by its language code.

Example:

    {{ languageCode | getFromLanguageCode }}

### 6.5.7 Permission translation

The permission translation extension is used to get the permission name by its permission string.

Example:

    {{ permissionString | permission }}

### 6.5.8 Translation

The translation extension is used to get the translation of a string.

Example:

    {{ 'string' | translate }}

### 6.5.9 User name

The username extension is used to get the name of a user by its ID.

Example:

    {{ userId | userName }}

## Docker container

### Create a new SSL certificate

To create a new SSL certificate, you can use the following command:

    $ mkcert -install -cert-file .docker/cert.pem -key-file .docker/cert-key.pem localhost

For more information, please visit the [mkcert](https://github.com/FiloSottile/mkcert) website.
