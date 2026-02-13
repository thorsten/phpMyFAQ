# 9. Developer documentation

## 9.1 Customizing phpMyFAQ

phpMyFAQ users have even more customization opportunities. The key feature is the user-selectable template sets, there
is a templates/default directory where the default layouts get shipped.

In phpMyFAQ code and layout are separated. The layout is based on several template files, that you can modify to suit
your own needs. All files for phpMyFAQ's default layout can be found in the directory _assets/templates/default/_.

### 9.1.1 Creating a custom layout

Follow these steps to create a custom template set:

- copy the directory assets/templates/default to e.g., assets/templates/example
- adjust template files in assets/templates/example to fit your needs
- adjust the CSS theme in assets/templates/example/theme.css to fit your needs
- activate your "example" template set within Admin → Configuration → Main

**Note:** There is a magic variable _{{ tplSetName }}_ containing the name of the actual layout available in each
template file.

### 9.1.2 Debug Configuration

phpMyFAQ 4.1 and later uses environment variables for the application configuration.

#### Available Variables

##### Debug Configuration

| Variable            | Description        | Example            |
|---------------------|--------------------|--------------------|
| `DEBUG`             | Enables debug mode | `true` / `false`   |
| `DEBUG_LEVEL`       | Debug detail level | `0`, `1`, `2`, `3` |
| `DEBUG_LOG_QUERIES` | SQL query logging  | `true` / `false`   |

##### Application

| Variable  | Description             | Example                                |
|-----------|-------------------------|----------------------------------------|
| `APP_ENV` | Application environment | `development`, `production`, `testing` |

#### Configuration Files

##### .env.example

Template for environment variables:

    # Debug Configuration
    DEBUG=false
    DEBUG_LEVEL=0
    DEBUG_LOG_QUERIES=false
    
    # Application Configuration
    APP_ENV=production

#### Configuration

##### .env File

Create a `.env` file in the project root based on `.env.example`:

    cp .env.example .env

### 9.1.3 Adding new language strings and translations

Please add at least the English translation for your new language string, the translation files are located in the folder **phpmyfaq/translations**.

The English translation file is named **language_en.php**.

To add new language strings and translations to phpMyFAQ, follow these steps:

1. Open the English translation file **language_en.php** in a text editor.
2. Add your new language string in the following format:
   `$PMF_LANG['your_new_language_string_key'] = 'Your new language string value';`
3. Save the file.
4. If you want to add translations for other languages, open the corresponding translation file (e.g., 
   **language_de.php** for German) and add the translation in the same format as above.

English is always the fallback language in phpMyFAQ, so make sure to add the English translation first.
Every translation file must contain some language description variables at the beginning of the file:

```php
$PMF_LANG['metaCharset'] = 'UTF-8';  // Character set, only use 'UTF-8'
$PMF_LANG['metaLanguage'] = 'en';    // Language code, e.g., 'en' for English, 'de' for German
$PMF_LANG['language'] = 'English';   // Language name in English
$PMF_LANG['direction'] = 'ltr';      // Text direction, 'ltr' for left-to-right, 'rtl' for right-to-left
$PMF_LANG['nplurals'] = '2';         // Number of plural forms, e.g., '2' for English
```

## 9.2 Templating

### 9.2.1 Introduction

phpMyFAQ v4 and later uses Twig, a modern template engine for PHP. It is fast, secure, and flexible.
This documentation provides an overview of how to use Twig in phpMyFAQ.

The default layout of phpMyFAQ is saved in the **assets/templates/default/index.twig** file.

### 9.2.2 Template files

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

To enable debugging in Twig, set the `DEBUG` option in the file .env to `true`.
You can then use the `dump` function in your templates.

```twig
{{ dump(variable) }}
```

#### Conclusion

For more detailed information, visit the [Twig documentation](https://twig.symfony.com/doc/).

### 9.2.2 Admin backend templates

The admin backend templates are located in the **assets/templates/admin** directory.
Usually, you don't need to modify these templates, but if you want to, you can do so.
Please be aware that changes to the admin backend templates can break the functionality of phpMyFAQ.

## 9.3 Themes

The default CSS theme is located in the **assets/templates/default** directory and is stored in the file **theme.css**.
You can create your own CSS theme by copying the default theme and modifying it to suit your needs.
The CSS theme is based on Bootstrap’s CSS custom properties for fast and forward-looking design and development.
phpMyFAQ officially supports three modes in the default theme: **Light**, **Dark**, and **High Contrast**.
**Accessibility Guidelines:**
The High Contrast mode is specifically engineered for users with visual impairments and features the __Atkinson Hyperlegible Next__ font to maximize character recognition.

*   **Prioritize Legibility:** When customizing this mode, avoid "visual noise" such as text-shadows, transitions, or decorative gradients that could interfere with assistive technologies.
*   **Contrast Standards:** Customizations **must** adhere to the [WCAG 2.2 Contrast Guidelines](https://www.w3.org/TR/WCAG22/). For the High Contrast theme, we aim for **Level AAA**, requiring a contrast ratio of at least **7:1** for normal text and **4.5:1** for large text.

**Defining Mode-Specific CSS:**
phpMyFAQ uses Bootstrap’s color mode architecture. You can target specific modes using the **data-bs-theme** attribute in your CSS:

```css
/* Default (Light) Mode */
:root {
    --primary-text: #212529;
}

/* Dark Mode */
[data-bs-theme='dark'] {
    --primary-text: #eceff9;
}

/* High Contrast Mode (A11y focused) */
[data-bs-theme='high-contrast'] {
    /* Enforce maximum contrast ratio (WCAG AAA) */
    --primary-text: #ffff00;
    --primary-bg: #000000;
    
    /* 
       We use !important here to ensure that accessibility-critical styles 
       cannot be accidentally overridden by decorative styles or third-party 
       plugins, maintaining the integrity of the high-contrast interface.
    */
    text-shadow: none !important;
    box-shadow: none !important;
}
```

For more information, check out the documentation on [Bootstrap](https://getbootstrap.com/docs/5.3/customize/css-variables/).

## 9.4 Custom CSS

You can add custom CSS to your phpMyFAQ installation by adding the CSS code in the admin configuration in the layout
tab.
This way, you can customize the look and feel of your phpMyFAQ installation, and you don't want to modify the SCSS
files.

## 9.5 REST APIs

phpMyFAQ offers interfaces to access phpMyFAQ installations with other clients like the iPhone App. phpMyFAQ includes a
REST API and offers APIs for various services like fetching the phpMyFAQ version or doing a search against the
phpMyFAQ installation.

The API documentation can be found at [https://api-docs.phpmyfaq.de/](https://api-docs.phpmyfaq.de/).

## 9.6 phpMyFAQ development

Since phpMyFAQ is an Open Source project, we encourage developers to contribute patches and code for us to include in
the main package of phpMyFAQ.
However, there are a few rules and limitations when doing so and this page lists them.

1. Contributed code will be licensed under the MPL 2.0 license.
2. Copyright notices will be changed to phpMyFAQ Team. But contributors will get credit for their work!
3. All third party code will be reviewed, tested, and possibly modified before being released.

These basic rules make it possible for us to earn a living of the phpMyFAQ project, but it also ensures that the code
remains Open Source and under the MPL 2.0 license.
All contributions will be added to the changelog and on the phpMyFAQ website.

### 9.6.1 How to contribute?

Contributing to phpMyFAQ is quite easy: just fork the [project on GitHub](https://github.com/thorsten/phpMyFAQ),
work on your copy and send pull requests.

### 9.6.2 Setup a local phpMyFAQ development environment

Before working on phpMyFAQ, set up a local environment with the following software:

- Git
- PHP 9.4 up to 9.6
- PHPUnit 12.x
- Composer
- Node.js 24+
- TypeScript 5.x
- PNPM
- Docker

### 9.6.3 Configure your Git installation

Set up your user information with your real name and a working e-mail address:

    $ git config --global user.name "Your Name"
    $ git config --global user.email you@example.com

### 9.6.4 Background jobs during development

phpMyFAQ uses the internal queue for asynchronous tasks such as mail delivery.
Run the worker locally while testing features that send email:

```bash
php bin/worker.php
```

Optionally limit processing to a fixed number of jobs:

```bash
php bin/worker.php 10
```
    $ git config core.autocrlf # if you're on Windows

### 9.6.4 How to get the phpMyFAQ source code?

Clone your forked phpMyFAQ repository locally:

    $ git clone git@github.com:USERNAME/phpMyFAQ.git

Add the upstream repository as remote:

    $ cd phpMyFAQ
    $ git remote add upstream git://github.com/thorsten/phpMyFAQ.git

Please check our [coding standards](https://www.phpmyfaq.de/docs/standards) before sending patches or pull requests.
Every PR on GitHub will check the coding standards and tests as well.

### 9.6.5 Run Docker Compose

The Dockerfile provided in the phpMyFAQ repository only builds an environment
to run any release for development purposes.
It does not contain any code as the phpmyfaq folder is meant to be mounted as the /var/www/html folder in the container.

For development purposes, you can start a full stack to run your current phpMyFAQ source code from your local repository.

    $ docker-compose up

The command above starts nine containers for ^multi-database development as follows.

_Specific images started at once to prepare the project:_

- **composer**: update Composer dependencies
- **pnpm**: update PNPM dependencies

_Running using named volumes:_

- **mariadb**: image with MariaDB database with xtrabackup support
- **phpmyadmin**: a PHP tool to have a look on your MariaDB database.
- **postgres**: image with PostgreSQL database
- **pgadmin**: a PHP tool to have a look on your PostgreSQL database.
- **sqlserver**: image with Microsoft SQL Server for Linux
- **elasticsearch**: Open Source Software image (it means it does not have XPack installed)
- **opensearch**: OpenSearch image (it means it does not have XPack installed)

_Running apache web server with PHP 9.5 support:_

- **apache**: mounts the `phpmyfaq` folder in place of `/var/www/html`.

_Running nginx web server with PHP 9.5 support:_

- **nginx**: mounts the `phpmyfaq` folder in place of `/var/www/html`.
- **php-fpm**: PHP-FPM image with PHP 9.5 support

_Running FrankenPHP with PHP 9.5 support:_

- **frankenphp**: mounts the `phpmyfaq` folder in place of `/var/www/html`. Modern PHP application server built on Caddy with worker mode for better performance.

Then services will be available at the following addresses:

- phpMyFAQ: (https://localhost:443 by default or http://localhost:8080)
- phpMyFAQ with FrankenPHP: (http://localhost:8888 or https://localhost:8443)
- phpMyAdmin: (http://localhost:8000)
- pgAdmin: (http://localhost:8008)

### 9.6.6 Fetch third party libraries and install phpMyFAQ

After cloning your forked repository, you have to fetch the 3rd party libraries used in phpMyFAQ:

    $ cd phpMyFAQ
    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install
    $ pnpm install
    $ pnpm build

Then start a normal, local phpMyFAQ installation.

If you change some TypeScript code, you have to re-build the .ts files into one with the following PNPM task:

    $ pnpm build

During development, you can use the watch mode:

    $ pnpm build:watch

For a production build, you can use the following command:

    $ pnpm build:prod

To run the PHPUnit-based tests, you can use the following command:

    $ composer test

To run the ESLint-based checks, you can use the following command:

    $ pnpm eslint .

To run the Vitest-based tests, you can use the following command:

    $ pnpm test

### 9.6.7 Coding standards

The following coding standards are used in phpMyFAQ:

- PHP: [PER Coding Style 3.0](https://www.php-fig.org/per/coding-style/)
- TypeScript with ESLint recommendations

### 9.6.8 Rebase your Patch

Before submitting your patch, please update your local branch:

    $ git checkout main
    $ git fetch upstream
    $ git merge upstream/main
    $ git checkout YOUR_BRANCH_NAME
    $ git rebase main

### 9.6.9 Make a Pull Request

You can now make a pull request on the phpMyFAQ GitHub repository.

## 9.7 Builtin Twig Extensions

phpMyFAQ v4 and later use the Twig template engine for the frontend and the backend.
We have added some custom extensions to Twig to make it easier to work with phpMyFAQ.

### Category Name Twig Extension

The category name extension is used to get the name of a category by its ID.

Example:

    {{ categoryId | categoryName }}

### Create Link Twig Extension

The create link extension is used to create a link to a category or FAQ entry by its ID.

Example for a category link:

    {{ categoryLink(categoryId) }}

Example for a FAQ entry link:

    {{ faqLink(categoryId, faqId, faqLanguage) }}

### FAQ question Twig Extensions

The FAQ question extension is used to get the question of a FAQ entry by its ID.

Example:

    {{ faqId | faqQuestion }}

### Formatting bytes Twig Extensions

The format bytes extension is used to format a number of bytes to a human-readable format.

Example:

    {{ bytes | formatBytes }}

### Format date Twig Extensions

The format date extension is used to format a date to a human-readable format.

Example:

    {{ date | formatDate }}

### ISO date format Twig Extensions

The ISO date format extension is used to format a date to an ISO date format.

Example:

    {{ date | createIsoDate }}

### Language code Twig Extensions

The language code extension is used to get the language name by its language code.

Example:

    {{ languageCode | getFromLanguageCode }}

### Permission translation Twig Extensions

The permission translation extension is used to get the permission name by its permission string.

Example:

    {{ permissionString | permission }}

### Translation Twig Extensions

The translation extension is used to get the translation of a string.

Example:

    {{ 'string' | translate }}

### Title slugify Twig Extensions

The title slugify extension is used to create a slug from a string.

Example:

     {{ "Hello World"|slugify }}  {# outputs: hello-world #}

### User name Twig Extensions

The username extension is used to get the name of a user by its ID.

Example:

    {{ userId | userName }}

## 9.8 Working with the Docker containers

### 9.9.1 Create a new SSL certificate

To create a new SSL certificate, you can use the following command:

    $ mkcert -install -cert-file .docker/cert.pem -key-file .docker/cert-key.pem localhost

For more information, please visit the [mkcert](https://github.com/FiloSottile/mkcert) website.

### 9.9.2 Using an OpenLDAP docker container for testing

To test phpMyFAQ during development with an OpenLDAP docker container, you can use the following test setup:

    $ docker pull rroemhild/test-openldap
    $ docker run --rm -p 10389:10389 -p 10636:10636 rroemhild/test-openldap

The credentials for the OpenLDAP container are stored in the file content/core/config/ldap.php:

    <?php
    $PMF_LDAP['ldap_server'] = 'ldap://<your ip address>';
    $PMF_LDAP['ldap_port'] = 10389;
    $PMF_LDAP['ldap_user'] = 'cn=admin,dc=planetexpress,dc=com';
    $PMF_LDAP['ldap_password'] = 'GoodNewsEveryone';
    $PMF_LDAP['ldap_base'] = 'ou=people,dc=planetexpress,dc=com';

After activating the LDAP authentication in the admin backend, you can use the following credentials to log in:

    Username: professor
    Password: professor

More information about the OpenLDAP docker container can be found on the [Docker Hub](https://hub.docker.com/r/rroemhild/test-openldap).
