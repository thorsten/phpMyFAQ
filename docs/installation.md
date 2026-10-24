# 2. Installation

## 2.1 Requirements for phpMyFAQ

phpMyFAQ addresses a database system via PHP.
To install it, you will need a web server that meets the following requirements:

### PHP requirements

- version 8.4 or later
- memory_limit = 128M (the more the better)
- at least one database extension, see the database requirements below (PDO is preferred)
- cURL support
- FileInfo support
- Filter support
- GD support
- intl support
- JSON support
- mbstring support
- OpenSSL support
- Sodium support
- XML and XMLWriter support
- Zip support

The setup checks these extensions and refuses to start the installation if one of them is missing.

Suggested PHP extensions:

- zlib
- ldap (if you want to use LDAP or Microsoft Active Directory)
- redis (if you want to use Redis as an in-memory data store)

### Web server requirements

You can use phpMyFAQ with the following web servers:

- [Apache](http://www.apache.org) 2.4 or later (with mod_rewrite) and mod_ssl (if you wish to run phpMyFAQ under SSL)
- [Nginx](http://www.nginx.org) 1.0 or later (with URL rewriting) and SSL support
- [FrankenPHP](https://frankenphp.dev) 1.0 or later (modern PHP application server built on Caddy)
- [IIS](http://www.iis.net) 7.0 or later (with URL rewriting) and SSL support

#### Apache requirements

- mod_rewrite
- mod_ssl (if you wish to run phpMyFAQ under SSL)
- mod_headers

You should also ensure
you have `AllowOverride All` set in the `<Directory>` blocks so that the `.htaccess` file processes correctly,
and rewrite rules take effect.
Please check, if your path in
`RewriteBase` is correct.
By default, it's `/`, the root path.
If you installed phpMyFAQ in the folder `faq`,
it has to be `RewriteBase /faq/`.
Please be aware that modules like `mod_security` can cause problems with the installation and/or update process.

#### Nginx and IIS requirements

phpMyFAQ only ships rewrite rules for Apache (`.htaccess`) and IIS (`web.config`). For nginx, use the reference
configuration [`.docker/nginx/default.conf`](https://github.com/thorsten/phpMyFAQ/blob/main/.docker/nginx/default.conf)
from the repository as a starting point: it routes all requests to the front controllers and denies access to the
server-side directories below `content/`. It is written for the Docker development stack, so replace the `resolver`
and the `fastcgi_pass` upstream `php-fpm:9000` with the address or socket of your PHP-FPM pool.

### Database requirements

- [MySQL](http://www.mysql.com) 8 or later (via MySQLi extension or PDO)
- [PostgreSQL](http://www.postgresql.org) 10 or later (via pgsql extension or PDO)
- [Microsoft SQL Server](http://www.microsoft.com/sql/) 2016 and later (via sqlsrv extension or PDO)
- [SQLite](http://www.sqlite.org) 3 (via sqlite3 extension or PDO)
- [MariaDB](https://mariadb.org/) 10 or later (via MySQLi extension or PDO)
- [Percona Server](http://www.percona.com) 8 or later (via MySQLi extension or PDO)
- Galera Cluster 4 (via MySQLi extension or PDO)
- [Azure SQL Database](https://azure.microsoft.com/en-us/products/azure-sql/database) (via PDO, experimental)

The PDO extension is the preferred way to connect to your database server.

### Optional Search engine

- [Elasticsearch](https://www.elastic.co/products/elasticsearch) 7.x or 8.x
- [OpenSearch](https://opensearch.org/) 2.x

### Optional In-Memory Data Store

- [Redis](https://redis.io/) 7.x or later

### Additional requirements

- correctly set: access permissions, owner, group
- **Docker** (optional)
- **Kubernetes** (optional)

In case PHP runs as a module of Apache, you will have to be able to do a chown on the files before installation.
The files and directories must be owned by the web server's user.

You can determine which versions your web server is running by creating a file called **info.php** with the following
content: `<?php phpinfo();`

Upload this file to your webspace and open it using your browser. The installation-script checks which version of PHP
is installed on your server. Should you not meet the requirements, you cannot start the installation process.

In case you're running PHP before 8.4, you cannot use phpMyFAQ 4.2.

### Browser requirements

phpMyFAQ uses a modern HTML5/CSS3 powered markup. The supported browsers are the latest Mozilla Firefox
(Windows/macOS/Linux), the latest Safari (macOS/iOS), the latest Chrome (Windows/macOS/Linux), the latest Opera
(Windows/macOS/Linux) and Microsoft Edge (Windows/macOS/Linux).

We recommend using the latest version of Firefox, Chrome, Safari, Opera, or Microsoft Edge.

## 2.2 Preparations

### 2.2.1 Classic Shared Web Hosting

You can install phpMyFAQ via one of the provided packages as .zip or .tar.gz or using Git. If you choose our package,
download it and unzip the archive on your hard disk.

If you want to use Git, please run the following commands on your shell:

    $ git clone https://github.com/thorsten/phpMyFAQ.git
    $ cd phpMyFAQ
    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install
    $ curl -fsSL https://get.pnpm.io/install.sh | sh -
    $ pnpm install
    $ pnpm build

The `main` branch is the development branch. For a production installation, check out the tag of a release with
`git checkout <version>` before you install the dependencies. The application itself is located in the subdirectory
**phpmyfaq/**.

You can modify the layout of phpMyFAQ using templates. A description of how this is done can be found in the development section.
Copy all unzipped files to your web server in a directory using FTP. A good choice would be the directory **faq/**.

**Important:**
Writing permission for your script is needed in this directory to be able to write the file **content/core/config/database.php**
during installation. The installation script will stop when your web server isn't configured as needed.

It might help to set chmod 775 to the whole phpMyFAQ directory to avoid problems during the installation. If you're
running a very restrictive mod_php installation, you should keep the chmod 775 for the following files and directories
even after the successful installation:

- the directory **content/core/config/**
- the directory **content/core/data/**
- the directory **content/core/logs/**
- the directory **content/user/attachments/**
- the directory **content/user/images/**

All other directories shouldn't be world-writable for your own security.

**Note**: If you're running SELinux, you may need further configuration, or you should completely disable it.

The database user needs the permissions for CREATE, DROP, ALTER, INDEX, INSERT, UPDATE, DELETE, and SELECT on all tables
in the database.

### 2.2.2 Cloud Hosting via Docker

Official images are published to the GitHub Container Registry as `ghcr.io/thorsten/phpmyfaq` (Apache) and
`ghcr.io/thorsten/phpmyfaq:<version>-frankenphp` (FrankenPHP). They already contain PHP 8.4, all required extensions
and the built frontend assets. The recommended way to run them is the production Compose file
`docker-compose.prod.yml` together with `.env.production.example`:

    $ curl -fsSLO https://raw.githubusercontent.com/thorsten/phpMyFAQ/main/docker-compose.prod.yml
    $ curl -fsSL -o .env https://raw.githubusercontent.com/thorsten/phpMyFAQ/main/.env.production.example
    $ docker compose -f docker-compose.prod.yml up -d

Set the passwords and `PMF_BASE_URL` in `.env` first. With the database and admin credentials set, the container
installs phpMyFAQ on its first start; otherwise the web installer at `/setup/` is used. All details — profiles,
environment variables, volumes, HTTPS, backups, updates, and running the image without Compose — are described in
[Production Deployment](deployment.md).

### 2.2.3 Cloud or On-Premise Hosting via Kubernetes

You can use any MySQL or MariaDB deployment/helm chart, also choose another database engine.
View official resources or find one on [artifacthub.io](https://artifacthub.io/)

> You must have a storage method to persist your data.

Then, install it, using referenced manifests on our project at 
[GitHub](https://github.com/thorsten/phpMyFAQ/tree/main/kubernetes-deploy).

Read the definitions and configure, according to your needs. If you have any doubt, do not hesitate to consult us.

### 2.2.4 Installation via Composer

phpMyFAQ is published on [Packagist](https://packagist.org/packages/thorsten/phpmyfaq) as the
`thorsten/phpmyfaq` package, so you can bootstrap a fresh installation with `composer create-project`.
This is essentially a from-source installation that automates the PHP dependency step. It is best
suited for developers, or for servers where you already have a Node.js toolchain available.

    $ composer create-project thorsten/phpmyfaq phpmyfaq
    $ cd phpmyfaq

This downloads the latest stable release and installs all required PHP dependencies into
**phpmyfaq/src/libs**. To install a specific version, append a version constraint, e.g.
`composer create-project thorsten/phpmyfaq:^4.2 phpmyfaq`.

Composer only installs the PHP side of phpMyFAQ. The compiled CSS and JavaScript assets are not part
of the package and have to be built once using [Node.js](https://nodejs.org) 24 or later and
[PNPM](https://pnpm.io):

    $ pnpm install
    $ pnpm build

Use `pnpm build:prod` instead of `pnpm build` for a production-optimized, minified build.

**Important:** If you skip the asset build, the setup and admin pages are displayed without any
styling and the JavaScript does not work. If you do not want to install a Node.js toolchain on your
server, use one of the provided release packages (.zip or .tar.gz) described in section 2.2.1
instead — these already ship with pre-built assets.

After building the assets, set the file permissions as described in section 2.2.1, point your web
server's document root to the **phpmyfaq/** directory, and continue with the setup in section 2.5.

## 2.3 PHP settings

- We recommend using a PHP accelerator or OpCode cache
- Allocate at least 128 MB of memory to each PHP process
- Required extensions: see section 2.1
- Recommended configuration:

        memory_limit = 128M
        file_uploads = On

## 2.4 Server side recommendations

**_MySQL / Percona Server / MariaDB_**

    interactive_timeout = 120
    wait_timeout = 120
    max_allowed_packet = 64M

## 2.5 Setup

Open your browser and type in the following URL:

`http://www.example.com/faq/setup/`

### Step 1: Database server

Substitute **www.example.com** with your actual domain name. When the site is loaded, first select the database you want
to use for phpMyFAQ. The loaded database extensions from PHP are listed in a select box. Then enter the address of your
database server (e.g., db.provider.com), the database port, your database username and password as well as the database
name. The database has to be created with UTF-8 character set before running the installation script. You can leave the
prefix-field empty. If you are planning on using multiple FAQs in one database, you will have to use a table prefix,
though (i.e. _sport_ for a sports FAQ, _weather_ for a weather FAQ, etc.). Please note that the prefix may only contain
letters, digits, and underscores ("\_"), with a maximum of 32 characters. If you want to use SQLite, you only have to
select a path to the database file of SQLite.

### Step 2: LDAP or Microsoft Active Directory support

If the PHP LDAP extension is loaded, you can add your LDAP or Microsoft Active Directory information, too.

### Step 3: Elasticsearch and OpenSearch support

If you want to use Elasticsearch or OpenSearch, you can activate this in the third step.
You have to add at least one Elasticsearch or OpenSearch node and the index name.

### Step 4: Admin user setup

In addition, you can enter your language, the default here is English.
Furthermore, you should register your name, your email address, and - very importantly - your password.
You must enter the password twice, and it has to be at least eight characters long.
Then click the button **"install"** to initialize the tables in your database.

## 2.6 First Steps

You can enter the public area of your FAQ by entering

`http://www.example.com/faq/index.php`

into your browser's address field. Your FAQ will be empty and presented in the standard layout.

To configure phpMyFAQ point your browser to

`http://www.example.com/faq/admin/index.php`

Please use your chosen username and your password for your first login into the admin section.

Some variables that do not change regularly, they can be edited in the file _content/core/config/constants.php_.
You can change

- the time zone of your server (default: "Europe/Berlin")
- the save path for PHP sessions (default: empty, the PHP setting is used)
- the timeout in the admin section (default: 300 minutes)
- the timeout warning pop-up in the admin section (default: 5 minutes)
- the solution id start value (default: 1000)
- the incremental value of the solution id (default: 1)
- the number of records in the Top10 (default: 10)
- the number of the latest records (default: 5)
- the number of the trending records (default: 5)
- the number with which the Tags Cloud list is limited to (default: 50)
- the number with which the autocomplete list is limited to (default: 20)
- the default encryption type for passwords

## 2.7 Notes regarding the search functionality

- The boolean full-text search will only work with MySQL/MariaDB and if there are some entries in the database (5 or more).
  The term you are looking for should also not be in more than 50% of all your entries, or it will automatically be
  excluded from search. This is not a bug, but rather a feature of MySQL/MariaDB.
- The search on other databases is using the LIKE operator currently.
- To improve the search functionality, you should consider using Elasticsearch or OpenSearch.

## 2.8 Automatic user language detection

To set the default language in your browser, you have to set a variable that gets passed to the web server.
How this is done depends on the browser you are using.

- Mozilla Firefox: Tools → Options → Content → Languages
- Google Chrome / Microsoft Edge / Opera: Settings → Details → Language settings
- Safari uses the macOS system preferences to determine your preferred language: System preferences → International
  → Language

## 2.9 Enabling LDAP or Microsoft Active Directory support

If you entered the correct LDAP or Microsoft Active Directory information during the installation, you have to enable
the LDAP or Microsoft Active Directory support in the configuration in the admin backend. Now your user can authenticate
themselves in phpMyFAQ against your LDAP server or a Microsoft Active Directory server.

If you need special options for your LDAP or ADS configuration, you can change the LDAP configuration in the admin
configuration panel.

If you want to add LDAP support later, you can use the file **content/core/config/ldap.php.original** as a template. 
If you rename it to **content/core/config/ldap.php** you can use the LDAP features as well after you enabled it in the
administration backend.

Please note that you have to use the correct LDAP attributes for your LDAP server. 
The LDAP server address must be in the format "ldap://ldap.example.com"
or "ldaps://ldap.example.com" for secure connections.

The "samAccountName" attribute is used for the user's login name,
and the "mail" attribute is used for the email address.

If you want to use LDAP with a self-signed certificate,
you have to add the following configuration to /etc/ldap/ldap.conf:

    TLS_REQCERT never

This disables the certificate verification for all LDAP connections of the server, so only use it for testing.

## 2.10 PDF export

Main features of the PDF export:

- supports all ISO page formats;
- supports custom page formats, margins, and units of measure;
- supports UTF-8 Unicode and Right-To-Left languages;
- supports TrueTypeUnicode, OpenTypeUnicode, TrueType, OpenType, Type1, and CID-0 fonts;
- includes methods to publish some HTML code;
- includes graphic (geometric) and transformation methods;
- includes methods to set Bookmarks and print a Table of Content;
- supports automatic page break;
- supports automatic page numbering and page groups;
- supports automatic line break and text justification;
- supports JPEG and PNG images natively, all images supported by GD (GD, GD2, GD2PART, GIF, JPEG, PNG, BMP, XBM, XPM)

Images in FAQ answers are embedded into the PDF on the server:

- Images stored on your own phpMyFAQ host (for example uploads from the editor) are read from the `content/` directory
  and never requested over the network.
- Images on other hosts are only fetched when the host is listed in the configuration option
  _Allowed external hosts for media content_. Only the default port of the scheme (80 for HTTP, 443 for HTTPS) is used,
  unless the entry names a port explicitly, e.g. `images.example.org:8080`.
- Hosts that resolve to loopback, link-local or other non-routable addresses are never contacted, even when listed.
  Images that cannot be fetched under these rules are left out of the PDF.

## 2.11 Static solution ID

phpMyFAQ features a static solution ID which never changes. This ID is visible next to the question on a FAQ record
page. You may think why you need such an ID? If you have a record ID _1042_ it is now possible to enter only the ID
_1042_ in the input field of the full-text search box, and you'll be automatically redirected to the FAQ record with the
ID _1042_. By default, the numbers start at ID **1000**, but you can change this value in the file
_content/core/config/constants.php_.
You can also change the value of the incrementation of the static IDs.

## 2.12 Spam protection

phpMyFAQ performs these three checks on public forms:

1.  Check against IPv4 and IPv6 Network address
2.  Check against banned words
3.  Check against the captcha code (builtin or Google ReCaptcha)

The IPv4 and IPv6 Network addresses can be added or removed in the configuration panel in the administration backend.
If you want to add banned words to phpMyFAQ, then you have to edit the file _src/blockedwords.txt_. Please add only
one word per line.

By default, phpMyFAQ uses the builtin captcha functionality. If you want to use Google ReCaptcha v2, you can enable the
support for Google Recaptcha by adding your site and secret key. You can get the keys from
[Google](https://developers.google.com/recaptcha).

## 2.13 Attachments

phpMyFAQ supports encrypted attachments. The encryption uses the
[AES](http://en.wikipedia.org/wiki/Advanced_Encryption_Standard) algorithm in CBC mode, implemented by the bundled
phpseclib library. The key size can be 128, 192, or 256 bits.

Please be aware:

- Disabling encryption will cause all files to be saved unencrypted.
  In this case, you'll benefit from sparing disk space because identical files are saved only once.
- The default attachment encryption key must be exactly 16, 24 or 32 bytes long (AES-128, AES-192 or AES-256).
  Uploads are rejected before anything is written if the configured key has another length.
- Do not change the default attachment encryption key once files were uploaded.
  Doing so will cause all the previously uploaded files to be wrongly decrypted.
  If you need to change the default key, you will have to re-upload all files.
- Each attachment remembers whether it was stored encrypted. Toggling the global encryption
  setting only affects new uploads; existing files stay readable as long as the key configured
  when they were uploaded is still set.
- Always memorize your encryption keys. There is no way to decrypt files without a correct key.
- Files are always saved with names based on a virtual hash generated from several tokens (just like key and issue id
  etc), so there is no way to access a file directly using the name it was uploaded under.
- Download continuation isn't supported.

## 2.14 Syntax Highlighting

The bundled [highlight.js](https://highlightjs.org/) syntax highlighting component will find and highlight code inside
&lt;pre&gt;&lt;code&gt; tags; it tries to detect the language automatically. If automatic detection doesn't work for
you, you can specify the language in the class attribute:

    <pre><code class="html">...</code></pre>

The list of supported language classes is available in the class reference. Classes can also be prefixed with either
language- or lang-.

To disable highlighting altogether, use the "nohighlight" class:

    <pre><code class="nohighlight">...</code></pre>

## 2.15 Elasticsearch Support

To improve the search performance and quality of search results, it's possible to use Elasticsearch.
You need a running Elasticsearch instance accessible by phpMyFAQ via HTTP/REST.
You can add the IP(s)/Domain(s) and port(s) of your Elasticsearch cluster during installation or later by copying the
file **content/core/config/elasticsearch.php.original** to **content/core/config/elasticsearch.php** and adjusting it.
If you choose to add this during installation, the file will be automatically written and the index will be built.
If you enable Elasticsearch support in the admin configuration panel, you can create, re-import, and delete your
index with a user-friendly interface.

When the cluster is reached over HTTPS, the TLS certificate of the cluster is verified. For a cluster with a
private CA, point phpMyFAQ to the CA certificate in `content/core/config/elasticsearch.php`; disabling the
verification is only meant for local test clusters with self-signed certificates:

    $PMF_ES['hosts'] = ['https://elasticsearch.example.com:9200'];
    $PMF_ES['index'] = 'phpmyfaq';
    $PMF_ES['cafile'] = '/etc/ssl/certs/elasticsearch-ca.pem'; // or $PMF_ES['capath'] = '/etc/ssl/certs';
    // $PMF_ES['verify_peer'] = false; // self-signed test cluster only

## 2.16 OpenSearch Support

To improve the search performance and quality of search results, it's possible to use OpenSearch.
You need a running OpenSearch instance accessible by phpMyFAQ via HTTP/REST.
You can add the IP(s)/Domain(s) and port(s) of your OpenSearch cluster during installation or later by copying the
file **content/core/config/opensearch.php.original** to **content/core/config/opensearch.php** and adjusting it.
If you choose to add this during installation, the file will be automatically written and the index will be built.
If you enable OpenSearch support in the admin configuration panel, you can create, re-import, and delete your
index with a user-friendly interface.

TLS certificates are verified in the same way as for Elasticsearch; the options `cafile`, `capath` and
`verify_peer` are read from `$PMF_OS` in `content/core/config/opensearch.php`.

## 2.17 SSO (Single Sign-On) Support

phpMyFAQ supports SSO (Single Sign-On) with the `REMOTE_USER` server variable. The web server or application server
authenticates the user and populates `REMOTE_USER` with the user's identity. This is commonly used by SSO systems that
integrate with web servers, from HTTP Basic or Digest Authentication up to Kerberos/SPNEGO or SAML modules.

phpMyFAQ accepts the formats `user`, `DOMAIN\user`, and `user@DOMAIN` and uses the `user` part as the login name.
To use it, enable **Enable Single Sign On Support** in the security tab of the configuration in the administration
backend. Optionally, you can set a **Single Sign On logout redirect service URL**.

The examples below protect the admin section with HTTP Basic Authentication; replace the authentication directives with
the ones of your SSO module.

### Configuring Apache with `mod_php` to Pass `REMOTE_USER` to PHP

When Apache authenticates a request, it sets `REMOTE_USER` itself, and `mod_php` passes it to PHP. No additional
directive is needed.

#### Step 1: Configure authentication in Apache

In your Apache virtual host configuration file (commonly located at `/etc/apache2/sites-available/your-site.conf`),
add the authentication for the admin section:

```apache
<VirtualHost *:80>
    ServerName your-site.com
    DocumentRoot /var/www/html

    <Directory "/var/www/html/admin">
        # Basic authentication, or the directives of your SSO module
        AuthType Basic
        AuthName "Restricted Area"
        AuthUserFile /etc/apache2/.htpasswd
        Require valid-user
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

If using Basic Authentication, create a `.htpasswd` file with usernames and encrypted passwords.
You can skip this step if using an SSO system.

```bash
sudo htpasswd -c /etc/apache2/.htpasswd username
```

#### Step 2: Verify `mod_php` is Enabled and Restart Apache

Ensure that the `mod_php` module is enabled in Apache, then restart Apache to apply the new configuration:

```bash
sudo a2enmod php8.4
sudo systemctl restart apache2
```

Make sure to replace `php8.4` with your PHP version if it differs.

#### Step 3: Test the Configuration

To confirm that `REMOTE_USER` is being passed correctly, create a simple PHP file to output the `REMOTE_USER` value:

```php
<?php
echo 'REMOTE_USER: ' . $_SERVER['REMOTE_USER'];
```

Access this PHP file in the restricted area to ensure the `REMOTE_USER` variable is correctly populated.
Delete the file afterwards.

### Configuring Apache and PHP-FPM to Pass `REMOTE_USER` to PHP

With PHP-FPM, Apache forwards `REMOTE_USER` of an authenticated request as a FastCGI parameter via `mod_proxy_fcgi`.

#### Step 1: Modify Apache Configuration

Ensure that the `mod_proxy_fcgi` module is enabled in Apache. This module is responsible for handling FastCGI requests:

```bash
sudo a2enmod proxy_fcgi
sudo systemctl restart apache2
```

In your Apache virtual host configuration file (commonly located at `/etc/apache2/sites-available/your-site.conf`),
hand PHP files to PHP-FPM and add the authentication for the admin section:

```apache
<VirtualHost *:80>
    ServerName your-site.com
    DocumentRoot /var/www/html

    <FilesMatch "\.php$">
        SetHandler "proxy:unix:/run/php/php8.4-fpm.sock|fcgi://localhost"
    </FilesMatch>

    <Directory "/var/www/html/admin">
        # Basic authentication, or the directives of your SSO module
        AuthType Basic
        AuthName "Restricted Area"
        AuthUserFile /etc/apache2/.htpasswd
        Require valid-user
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

Replace the socket path with the one of your PHP-FPM pool.

#### Step 2: Restart Services

After making these changes, restart both Apache and PHP-FPM to apply the configuration:

```bash
sudo systemctl restart apache2
sudo systemctl restart php8.4-fpm
```

The name of the PHP-FPM service depends on your distribution, e.g. `php-fpm` or `php8.4-fpm`.

#### Step 3: Test the Configuration

Test the configuration with the PHP file from the `mod_php` section above.

### Configuring nginx and PHP-FPM to Pass `REMOTE_USER` to PHP

nginx provides the authenticated user name in the variable `$remote_user`, which has to be passed to PHP-FPM as a
FastCGI parameter.

#### Step 1: Modify nginx Configuration

In the nginx configuration file for your site (usually located at `/etc/nginx/sites-available/your-site.conf`), add the
authentication and the `REMOTE_USER` parameter to the location that passes the requests to PHP-FPM. With the reference
configuration mentioned in section 2.1 this is the location of the front controllers:

```nginx
location ~ ^/(index|admin/index|api/index|admin/api/index|setup/index)\.php$ {
    # Basic authentication, or the directives of your SSO module
    auth_basic           "Restricted Area";
    auth_basic_user_file /etc/nginx/.htpasswd;

    # Other configurations
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    include fastcgi_params;
    fastcgi_param REMOTE_USER $remote_user;  # Pass REMOTE_USER to PHP
}
```

All requests are routed to these front controllers, so the authentication in this location protects the whole FAQ,
including the public API. To protect only the admin section, use a separate location for `/admin/index.php` and
`/admin/api/index.php` with the authentication directives.

#### Step 2: Restart Services

After making these changes, reload nginx to apply the configuration:

```bash
sudo systemctl reload nginx
```

#### Step 3: Test the Configuration

Test the configuration with the PHP file from the `mod_php` section above.

### Configuring FrankenPHP to Pass `REMOTE_USER` to PHP

FrankenPHP passes the user authenticated by Caddy to PHP as `REMOTE_USER`. Install and configure FrankenPHP as
described in section 2.18, then add the authentication for the admin section to the site block of your Caddyfile:

```caddy
basic_auth /admin/* {
    # Generate the hash with: frankenphp hash-password
    username $2a$14$...
}
```

Replace `basic_auth` with the directive of your SSO module, if you use one. Test the configuration with the PHP file
from the `mod_php` section above.

## 2.18 FrankenPHP Installation

[FrankenPHP](https://frankenphp.dev) is a modern PHP application server built on the Caddy web server.

### Benefits of FrankenPHP

- **Built-in HTTPS**: Automatic certificate management with Let's Encrypt
- **HTTP/2 and HTTP/3** support
- **Modern Architecture**: Built on Caddy web server, PHP is embedded into the server
- **Easy Configuration**: Simple Caddyfile syntax

### Docker Installation

The easiest way to run phpMyFAQ with FrankenPHP is the official image `ghcr.io/thorsten/phpmyfaq:<version>-frankenphp`
with the `frankenphp` profile of `docker-compose.prod.yml`, see [Production Deployment](deployment.md).

For development, the repository contains a FrankenPHP stack in `docker-compose.yml`, which you can start with
`bin/dev preset frankenphp`.

### Manual Installation

#### Step 1: Install FrankenPHP

Download and install FrankenPHP from the official website:

```bash
# Download FrankenPHP
curl -L https://github.com/dunglas/frankenphp/releases/latest/download/frankenphp-linux-x86_64 -o frankenphp
chmod +x frankenphp
sudo mv frankenphp /usr/local/bin/
```

The FrankenPHP binary embeds its own PHP. Make sure it contains all extensions required by phpMyFAQ (see section 2.1),
or build a custom binary as described in the FrankenPHP documentation.

#### Step 2: Create a Caddyfile

Use the Caddyfile of the production image,
[`.docker/production/Caddyfile`](https://github.com/thorsten/phpMyFAQ/blob/main/.docker/production/Caddyfile), as a
starting point. It contains the rewrite rules for the front controllers of the admin section, the API, the setup and
the update, and it denies access to the server-side directories below `content/`. Adjust these settings:

- `root * /var/www/html`: the path to your **phpmyfaq/** directory
- `{$SERVER_NAME}`: your domain name, e.g. `faq.example.com`. With a public domain name, FrankenPHP obtains
  certificates automatically; ports 80 and 443 must be reachable from the internet for that.

#### Step 3: Start FrankenPHP

Start the FrankenPHP server:

```bash
sudo frankenphp run --config /path/to/your/Caddyfile
```

Or run it as a service by creating a systemd service file at `/etc/systemd/system/frankenphp.service`:

```ini
[Unit]
Description=FrankenPHP Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/html
ExecStart=/usr/local/bin/frankenphp run --config /path/to/your/Caddyfile
Restart=always
RestartSec=3
AmbientCapabilities=CAP_NET_BIND_SERVICE

[Install]
WantedBy=multi-user.target
```

The capability allows FrankenPHP to bind the ports 80 and 443 as user `www-data`. Then enable and start the service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable frankenphp
sudo systemctl start frankenphp
```

#### Step 4: Test the Configuration

Open your phpMyFAQ installation in your browser and continue with the setup in section 2.5.

### Performance Optimization

For production use, consider these optimizations:

- Configure OPcache settings
- Use production-ready database connections
- Enable compression and caching headers

### Worker Mode

FrankenPHP's worker mode keeps the application in memory between requests. phpMyFAQ 4.2 does not support worker mode
yet: the file `frankenphp-worker.php` in the phpMyFAQ directory is only a placeholder, and the shipped Caddyfiles do not
enable it. Run phpMyFAQ in FrankenPHP's default mode.

## 2.19 Redis-Backed Configuration Storage

phpMyFAQ can optionally use Redis to cache configuration values from `faqconfig`.

- Source of truth remains the database (`faqconfig`)
- Redis is used as a fast read layer
- On writing, phpMyFAQ updates the database first, then Redis
- If Redis is unavailable, phpMyFAQ falls back to database reads/writes

### Admin Setup

Open **Administration → Configuration → Storage** and configure:

- `Enable Redis for configuration storage`
- `Redis DSN for configuration storage`
- `Redis key prefix for configuration storage`
- `Redis connection timeout in seconds`

Use **Test Redis connection** before enabling Redis storage.

### DSN Examples

Docker Compose service name:

```text
tcp://redis:6379?database=1
```

Local host:

```text
tcp://127.0.0.1:6379?database=1
```

Unix socket:

```text
unix:///var/run/redis/redis.sock?database=1
```

With authentication (URL-encode special characters in the password; the user name is optional):

```text
tcp://user:password@redis.example.com:6379?database=1
```

The schemes `tcp://`, `redis://`, and `unix://` are supported. The `database` (or `db`) parameter selects the Redis
database; without it, database 0 is used.

### Docker Notes

`redis` as hostname works only if:

- a `redis` service/container is running
- your PHP container and Redis container are in the same Docker network

The development stack in `docker-compose.yml` contains a `redis` service (profile `redis`). The production Compose
file `docker-compose.prod.yml` does not include Redis; add your own Redis service to the `phpmyfaq` network or use an
external server.

Check quickly (replace `php-fpm` with the name of your PHP service, e.g. `apache` or `frankenphp`):

```bash
docker compose ps
docker compose exec php-fpm getent hosts redis
docker compose exec php-fpm php -r 'var_dump((bool) @fsockopen("redis", 6379));'
```

If `getaddrinfo for redis failed: Name or service not known`, either Redis is not running or hostname/networking is
wrong.

### Recommended Defaults

- DSN: `tcp://redis:6379?database=1` (Docker) or `tcp://127.0.0.1:6379?database=1` (non-Docker). Replace the
  hostname with your Redis server address if it differs.
- Prefix: `pmf:config:`
- Timeout: `1.0`

Use a unique prefix per phpMyFAQ instance when sharing one Redis server.

### Troubleshooting

#### Test button returns login redirect

This means the session is no longer valid (HTTP 401). Log in again and retry.

#### Test button says Redis connection failed

Open browser dev tools and inspect the API JSON response for the concrete reason:

- DNS/hostname resolution issue
- connection refused (Redis not listening)
- timeout
- invalid DSN

#### Redis enabled, but the app still works when Redis is down

This is expected behavior: phpMyFAQ falls back to the database.
