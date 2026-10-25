# 12. Developer documentation

## 12.1 Customizing phpMyFAQ

phpMyFAQ users have even more customization opportunities. The key feature is the user-selectable template sets, there
is an assets/templates/default directory where the default layouts get shipped.

In phpMyFAQ code and layout are separated. The layout is based on several template files, that you can modify to suit
your own needs. All files for phpMyFAQ's default layout can be found in the directory _assets/templates/default/_.

### 12.1.1 Creating a custom layout

Follow these steps to create a custom template set:

- copy the directory assets/templates/default to e.g., assets/templates/example
- adjust template files in assets/templates/example to fit your needs
- adjust the CSS theme in assets/templates/example/theme.css to fit your needs
- activate your "example" template set within Admin → Configuration → Layout (`layout.templateSet`)

**Note:** There is a magic variable _{{ tplSetName }}_ containing the name of the actual layout available in each
template file.

### 12.1.2 Environment configuration

phpMyFAQ 4.1 and later reads its runtime configuration from environment variables. They are loaded from the file
`phpmyfaq/.env` if it exists; variables set in the process environment (e.g., in Docker) are used as well.
Create the file from the shipped template:

    cp phpmyfaq/.env.example phpmyfaq/.env

The most important variables are:

| Variable                  | Description                                                                 | Default             |
|---------------------------|-----------------------------------------------------------------------------|---------------------|
| `APP_ENV`                 | Application environment: `production`, `development`, `staging`, or `demo` | `production`        |
| `DEBUG`                   | Enables debug mode with detailed error messages; disables all caches        | `false`             |
| `DEBUG_LOG_QUERIES`       | Logs all database queries (performance impact)                              | `false`             |
| `ERROR_LOG`               | Target for error logs                                                       | `php://stderr`      |
| `TRUSTED_PROXIES`         | Comma-separated IPs or CIDR ranges of reverse proxies allowed to set `X-Forwarded-*` headers, or `REMOTE_ADDR` | empty |
| `ROUTING_CACHE_ENABLED`   | Caches the compiled routes                                                  | `true`              |
| `CONFIG_CACHE_ENABLED`    | Caches the `faqconfig` table in the filesystem cache                       | `true`              |
| `CONTAINER_CACHE_ENABLED` | Caches the compiled service container                                       | `true`              |
| `TWIG_CACHE_ENABLED`      | Caches compiled Twig templates                                              | `true`              |
| `ELASTICSEARCH_BASE_URI`  | Base URI of the Elasticsearch server                                        | `http://127.0.0.1:9200` |
| `OPENSEARCH_BASE_URI`     | Base URI of the OpenSearch server                                           | `http://127.0.0.1:9201` |
| `SEARCH_WAIT_TIMEOUT`     | Seconds to wait for the search engine to become available                  | `15`                |

The cache directories can be overridden with `ROUTING_CACHE_DIR`, `CONTAINER_CACHE_DIR`, and `TWIG_CACHE_DIR` (absolute
paths). All caches are disabled automatically in debug mode and on development versions. See `phpmyfaq/.env.example`
for the full, commented list.

### 12.1.3 Adding new language strings and translations

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

## 12.2 Templating

### 12.2.1 Introduction

phpMyFAQ v4 and later uses Twig, a modern template engine for PHP. It is fast, secure, and flexible.
This documentation provides an overview of how to use Twig in phpMyFAQ.

The default layout of phpMyFAQ is saved in the **assets/templates/default/index.twig** file.

### 12.2.2 Template files

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

### 12.2.3 Admin backend templates

The admin backend templates are located in the **assets/templates/admin** directory.
Usually, you don't need to modify these templates, but if you want to, you can do so.
Please be aware that changes to the admin backend templates can break the functionality of phpMyFAQ.

### 12.2.4 Dynamic template set and theme upload

phpMyFAQ v4.2 introduced dynamic template set loading and a storage-backed `ThemeManager`.

- Runtime template set is read from configuration key `layout.templateSet`.
- `TwigWrapper` now validates the requested set and falls back to `default` if the
  configured set is invalid or missing on disk.
- Tenant provisioning can skip physical template copies and only persist the
  template reference in `faqconfig` (`layout.templateSet`).

Theme uploads use `phpMyFAQ\Template\ThemeManager`:

- Input format: ZIP archive.
- Theme name: 2 to 64 characters, letters, numbers, `_` and `-` only.
- Requires the PHP `zip` extension.
- Required file: `index.twig` (theme root or `<themeName>/index.twig`).
- `index.twig` is the only Twig file allowed in the archive, and it must be static: Twig syntax (`{{`, `{%`, `{#`)
  is rejected.
- Allowed file extensions:
  `.twig`, `.css`, `.js`, `.json`, `.png`, `.jpg`, `.jpeg`, `.svg`, `.webp`, `.gif`,
  `.woff`, `.woff2`, `.ttf`, `.otf`.
- Upload target: `StorageInterface` at `<themeRootPath>/<themeName>/...`
  (default root path: `themes`).
- Activation: `layout.templateSet` is updated via `ThemeManager::activateTheme()`, which fails if the theme has no
  `index.twig` in the storage. `ThemeManager::activateDefaultTheme()` switches back to `default`.

Example usage:

```php
use phpMyFAQ\Template\ThemeManager;

$themeManager = new ThemeManager($configuration, $storage, 'themes');
$themeManager->uploadTheme('tenant-theme', '/tmp/tenant-theme.zip');
$themeManager->activateTheme('tenant-theme');
```

Admin UI:

- Open `/admin/configuration`
- Use the `layout` tab
- Upload ZIP in the `layout` tab
- Select the template in `layout.templateSet` and save configuration

Security checks:

- Requires `CONFIGURATION_EDIT` permission
- CSRF token (`theme-manager`) is required for upload/activation

## 12.3 Themes

The default CSS theme is located in the **assets/templates/default** directory and is stored in the file **theme.css**.
You can create your own CSS theme by copying the default theme and modifying it to suit your needs.
The CSS theme is based on Bootstrap’s CSS custom properties for fast and forward-looking design and development.
phpMyFAQ officially supports three modes in the default theme: **Light**, **Dark**, and **High Contrast**.
**Accessibility Guidelines:**
The High Contrast mode is specifically engineered for users with visual impairments and features the __Atkinson 
Hyperlegible Next__ font to maximize character recognition.

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

## 12.4 Custom CSS

You can add custom CSS to your phpMyFAQ installation by adding the CSS code in the admin configuration in the layout
tab.
This way, you can customize the look and feel of your phpMyFAQ installation, and you don't want to modify the SCSS
files.

## 12.5 REST APIs

phpMyFAQ offers interfaces to access phpMyFAQ installations with other clients like the iPhone App. phpMyFAQ includes a
REST API and offers APIs for various services like fetching the phpMyFAQ version or doing a search against the
phpMyFAQ installation.

The API documentation can be found at [https://api-docs.phpmyfaq.de/](https://api-docs.phpmyfaq.de/).

## 12.6 phpMyFAQ development

Since phpMyFAQ is an Open Source project, we encourage developers to contribute patches and code for us to include in
the main package of phpMyFAQ.
However, there are a few rules and limitations when doing so and this page lists them.

1. Contributed code will be licensed under the MPL 2.0 license.
2. Copyright notices will be changed to phpMyFAQ Team. But contributors will get credit for their work!
3. All third party code will be reviewed, tested, and possibly modified before being released.

These basic rules make it possible for us to earn a living of the phpMyFAQ project, but it also ensures that the code
remains Open Source and under the MPL 2.0 license.
All contributions will be added to the changelog and on the phpMyFAQ website.

### 12.6.1 How to contribute?

Contributing to phpMyFAQ is quite easy: just fork the [project on GitHub](https://github.com/thorsten/phpMyFAQ),
work on your copy, and send pull requests.

### 12.6.2 Setup a local phpMyFAQ development environment

Before working on phpMyFAQ, set up a local environment with the following software:

- Git
- PHP 8.4 or later
- Composer (installs PHPUnit 13, Mago, and Rector into `phpmyfaq/src/libs/bin/`)
- Node.js 24+
- PNPM (installs TypeScript, Vitest, oxlint, oxfmt, and Stylelint)
- Docker with Docker Compose

### 12.6.3 Configure your Git installation

Set up your user information with your real name and a working e-mail address:

    $ git config --global user.name "Your Name"
    $ git config --global user.email you@example.com
    $ git config --global core.autocrlf true # if you're on Windows

### 12.6.4 Background jobs during development

phpMyFAQ uses an internal, database-backed queue for asynchronous tasks. The worker has handlers for mail delivery,
FAQ search indexing, and exports (see `phpmyfaq.queue.worker` in `phpmyfaq/src/services.php`).
Mail delivery only uses the queue if `mail.useQueue` is enabled (it is disabled by default). Run the worker locally
while testing queued email delivery:

```bash
php bin/worker.php
```

Optionally limit processing to a fixed number of jobs:

```bash
php bin/worker.php 10
```

Recurring maintenance tasks (session cleanup, search index optimization, statistics aggregation, and backups) run
via the task scheduler:

```bash
php bin/scheduler.php run
```

### 12.6.5 How to get the phpMyFAQ source code?

Clone your forked phpMyFAQ repository locally:

    $ git clone git@github.com:USERNAME/phpMyFAQ.git

Add the upstream repository as remote:

    $ cd phpMyFAQ
    $ git remote add upstream https://github.com/thorsten/phpMyFAQ.git

Please check our [coding standards](#1269-coding-standards) before sending patches or pull requests.
Every PR on GitHub will check the coding standards and tests as well.

### 12.6.6 Run the Docker Compose development stacks

The repository ships a `docker-compose.yml` for development. It does not contain any code: the local `phpmyfaq`
folder is mounted as `/var/www/html` into the web server containers, so you always run your current source code.

The `apache`, `nginx` and `frankenphp` containers use a local TLS certificate (`.docker/cert.pem` and
`.docker/cert-key.pem`), which is not committed to the repository. If the files are missing, `bin/dev` generates a
self-signed certificate with openssl. For a certificate your browser trusts, generate it with
[mkcert](https://github.com/FiloSottile/mkcert) before the first start:

    $ mkcert -install
    $ mkcert -cert-file .docker/cert.pem -key-file .docker/cert-key.pem localhost 127.0.0.1 ::1

Every service is gated behind a Compose profile. The `bin/dev` script lets you pick the combination you need, for
example a web server, a database and a search engine:

    $ bin/dev up nginx mariadb opensearch
    $ bin/dev up apache postgres elasticsearch --tools
    $ bin/dev preset default

The available profiles are:

- web servers: `nginx` (nginx with PHP-FPM 8.5), `apache` (Apache with PHP 8.5), `frankenphp` (FrankenPHP with
  worker mode)
- databases: `mariadb`, `postgres`, `mssql` (Azure SQL Edge)
- search engines: `elasticsearch`, `opensearch`
- others: `redis`, `phpmyadmin`, `pgadmin`, `tools` (phpMyAdmin and pgAdmin), `build` (one-off `composer` and `pnpm`
  containers that install the dependencies)

`bin/dev up` adds Redis automatically (skip it with `--no-redis`) and installs the Composer and PNPM dependencies
before the first start (skip it with `--no-build`). `--tools` adds phpMyAdmin or pgAdmin for the selected database.
The presets are `default` (nginx,
MariaDB, OpenSearch, Redis, phpMyAdmin), `mysql-es`, `postgres`, `apache`, `frankenphp`, and `full`. Run
`bin/dev presets` and `bin/dev help` for details.

The most common commands are also available as PNPM scripts:

    $ pnpm dev:up nginx mariadb   # bin/dev up <profile...>
    $ pnpm dev:down      # stop and remove the containers
    $ pnpm dev:build     # install the Composer and PNPM dependencies
    $ pnpm dev:ps        # show the running services
    $ pnpm dev:logs      # follow the logs
    $ pnpm dev:default   # start the default preset
    $ pnpm dev:full      # start the full preset

The web servers all bind the same host ports, so start only one of them at a time. The services are available at:

- phpMyFAQ with nginx or FrankenPHP: https://localhost (port 443) or http://localhost (port 80)
- phpMyFAQ with Apache: https://localhost (port 443) or http://localhost:8080
- phpMyAdmin: http://localhost:8000
- pgAdmin: http://localhost:8008

### 12.6.7 Fetch third party libraries and install phpMyFAQ

`bin/dev up` installs the dependencies automatically. Without Docker, fetch the 3rd party libraries used in phpMyFAQ
after cloning your forked repository:

    $ cd phpMyFAQ
    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install
    $ pnpm install
    $ pnpm build

Composer installs the PHP dependencies into `phpmyfaq/src/libs`, not into `vendor/`.
Then start a normal, local phpMyFAQ installation.

If you change TypeScript or SCSS code, rebuild the assets with `pnpm build`, or use `pnpm build:watch` during
development. The most important PNPM scripts are:

| Script                                | Description                                              |
|---------------------------------------|----------------------------------------------------------|
| `build`, `build:watch`, `build:prod`  | Build the TypeScript and SCSS assets with Vite           |
| `test`, `test:watch`, `test:coverage` | Run the Vitest tests                                     |
| `tsc`                                 | Type-check the TypeScript code                           |
| `oxlint`                              | Lint the TypeScript code                                 |
| `oxfmt`, `oxfmt:fix`                  | Check or fix the TypeScript/JSON/YAML/HTML formatting    |
| `stylelint`, `stylelint:fix`          | Lint the SCSS code                                       |
| `e2e:local`, `e2e:docker`             | Run the Playwright end-to-end tests (SQLite or MariaDB)  |
| `docs:build`                          | Build this documentation with MkDocs                     |

The PHP tests and checks are available as Composer scripts, see below.

The Git hooks (installed by `pnpm install` via Husky) run `bin/run-checks`: the pre-commit hook checks the
pipelines affected by the staged files, the pre-push hook runs the full PHP and JavaScript pipelines. This can take a
few minutes.

### 12.6.8 Composer scripts

phpMyFAQ provides a set of Composer scripts to simplify common development tasks. Run them with `composer <script>`.

#### Code quality

| Script             | Description                                                              |
|--------------------|--------------------------------------------------------------------------|
| `check`            | Run `format:dry-run`, `lint` and `analyze`, exactly like the CI Mago step |
| `lint`             | Run Mago linter against the lint baseline                                |
| `lint:fix`         | Run Mago linter and automatically fix issues                             |
| `format`           | Format the PHP code with Mago                                            |
| `format:dry-run`   | Check PHP code formatting with Mago without changing files               |
| `analyze`          | Run Mago static analysis against the analyze baseline                    |
| `analyze:all`      | Run Mago static analysis without the baseline                            |
| `analyze:baseline` | Remove outdated entries from the analyze baseline                        |

Mago lint and analyze run against baseline files (`mago-lint-baseline.toml`, `mago-analyze-baseline.toml`) that
suppress pre-existing findings. Editing a baselined line resurfaces its finding: fix it instead of re-baselining it.

#### Testing

| Script                 | Description                                                              |
|------------------------|--------------------------------------------------------------------------|
| `test`                 | Run PHPUnit tests in parallel with ParaTest, without coverage            |
| `test:sequential`      | Run PHPUnit tests in one process, without coverage                       |
| `test:ci`              | Run PHPUnit exactly like the git hooks and CI (`bin/phpunit-ci`)         |
| `test:coverage`        | Run PHPUnit tests with text coverage output                              |
| `test:coverage-html`   | Run PHPUnit tests with HTML coverage report in `coverage/`               |
| `test:coverage-clover` | Run PHPUnit tests with a Clover coverage report in `coverage/clover.xml` |
| `coverage:classes`     | Report classes below 80% coverage from the Clover report                 |
| `coverage:gate`        | Fail if a class is below 80% coverage in the Clover report               |

A single test file runs much faster than the whole suite: `composer test -- tests/phpMyFAQ/Path/SomeTest.php`.

#### API documentation

| Script         | Description                                      |
|----------------|--------------------------------------------------|
| `openapi:json` | Generate OpenAPI specification in JSON format    |
| `openapi:yaml` | Generate OpenAPI specification in YAML format    |
| `docs:build`   | Build this documentation with MkDocs in strict mode |

#### Refactoring

| Script            | Description                                   |
|-------------------|-----------------------------------------------|
| `refactor`        | Run Rector automated refactoring              |
| `refactor:dryrun` | Preview Rector refactoring without applying   |

#### Version management

| Script          | Description                                     |
|-----------------|-------------------------------------------------|
| `version:get`   | Print the current phpMyFAQ version              |
| `version:sync`  | Sync the version from PHP to package.json       |
| `version:check` | Verify that PHP and package.json versions match |

### 12.6.9 Coding standards

The following coding standards are used in phpMyFAQ:

- PHP: [PER Coding Style 3.0](https://www.php-fig.org/per/coding-style/)
- TypeScript with oxlint recommendations

### 12.6.10 Rebase your Patch

Before submitting your patch, please update your local branch:

    $ git checkout main
    $ git fetch upstream
    $ git merge upstream/main
    $ git checkout YOUR_BRANCH_NAME
    $ git rebase main

### 12.6.11 Make a Pull Request

You can now make a pull request on the phpMyFAQ GitHub repository.

## 12.7 Builtin Twig Extensions

phpMyFAQ v4 and later use the Twig template engine for the frontend and the backend.
We have added some custom extensions to Twig to make it easier to work with phpMyFAQ. They are located in
`phpmyfaq/src/phpMyFAQ/Twig/Extensions`.

### Category Name Twig Extension

The category name extension is used to get the name of a category by its ID. It is available as a filter and as a
function.

Example:

    {{ categoryId | categoryName }}
    {{ categoryName(categoryId) }}

### Create Link Twig Extension

The create link extension is used to create a link to a category or FAQ entry by its ID.

Example for a category link:

    {{ categoryLink(categoryId) }}

Example for a FAQ entry link:

    {{ faqLink(categoryId, faqId, faqLanguage) }}

Both are available as functions and as filters.

### FAQ question Twig Extensions

The FAQ question extension is used to get the question of a FAQ entry by its ID.

Example:

    {{ faqId | faqQuestion }}

### Formatting bytes Twig Extensions

The format bytes extension is used to format a number of bytes to a human-readable format. The optional argument
sets the number of decimals (default: 2).

Example:

    {{ bytes | formatBytes }}
    {{ bytes | formatBytes(1) }}

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

The username extension is used to get the login name of a user by its ID.

Example:

    {{ userId | userName }}

The real name filter returns the display name of a user, falls back to the login name if no display name is stored,
and returns a neutral placeholder if the user no longer exists.

Example:

    {{ userId | realName }}

### Tag name Twig Extension

The tag name extension is used to get the name of a tag by its ID.

Example:

    {{ tagId | tagName }}

### Asset version Twig Extension

The asset function appends the modification time of a file as a `?v=` query parameter, so browsers fetch updated
scripts and stylesheets after an upgrade while unchanged files stay cacheable. The path is relative to the phpMyFAQ
root directory.

Example:

    <link href="{{ baseHref }}{{ asset('assets/public/styles.css') }}" rel="stylesheet">

### Plugin Twig Extension

The plugin function triggers a plugin event and outputs the rendered result of all plugins listening to it. See
[Plugins](plugins.md) for details.

Example:

    {{ phpMyFAQPlugin('hello.world', 'Hello, World!') | raw }}

## 12.8 Working with the Docker containers

### 12.8.1 Create a new SSL certificate

The `mkcert` commands in the Docker Compose section above create a locally trusted certificate. If
`.docker/cert.pem` or `.docker/cert-key.pem` is missing or empty, `bin/dev up` generates a self-signed certificate
for `localhost` with `openssl` before it starts the `nginx` or `frankenphp` profile. Browsers do not trust this
certificate, so use `mkcert` if you want to avoid the certificate warning.

To create a new certificate, replace both files. The `apache` image copies the certificate files at build time, so
rebuild it afterwards with `bin/dev rebuild apache`; `nginx` and `frankenphp` mount the files and only need a
restart.

For more information, please visit the [mkcert](https://github.com/FiloSottile/mkcert) website.

### 12.8.2 Using an OpenLDAP docker container for testing

To test phpMyFAQ during development with an OpenLDAP docker container, you can use the following test setup:

    $ docker pull rroemhild/test-openldap
    $ docker run --rm -p 10389:10389 -p 10636:10636 rroemhild/test-openldap

The credentials for the OpenLDAP container are stored in the file `phpmyfaq/content/core/config/ldap.php`
(you can use `ldap.php.original` in the same directory as a template):

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
