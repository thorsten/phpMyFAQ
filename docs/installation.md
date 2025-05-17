# 2. Installation

## 2.1 Requirements for phpMyFAQ

phpMyFAQ addresses a database system via PHP.
To install it, you will need a web server that meets the following requirements:

### PHP requirements

- version 8.3 or later
- memory_limit = 128M (the more the better)
- cURL support
- GD support
- XMLWriter support
- JSON support
- Filter support
- SPL support
- FileInfo support
- Sodium support
- intl support

### Web server requirements

You can use phpMyFAQ with the following web servers:

- [Apache](http://www.apache.org) 2.4 or later (with mod_rewrite) and mod_ssl (if you wish to run phpMyFAQ under SSL)
- [Nginx](http://www.nginx.org) 1.0 or later (with URL rewriting) and SSL support

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

### Database requirements

- [MySQL](http://www.mysql.com) (via MySQLi extension or PDO)
- [PostgreSQL](http://www.postgresql.org) (via pgsql extension or PDO)
- [Microsoft SQL Server](http://www.microsoft.com/sql/) 2012 and later (via sqlsrv extension or PDO)
- [SQLite](http://www.sqlite.org) (via sqlite3 extension or PDO)
- [MariaDB](http://montyprogram.com/mariadb/) (via MySQLi extension or PDO)
- [Percona Server](http://www.percona.com) (via MySQLi extension or PDO)
- [Azure SQL Database](https://azure.microsoft.com/en-us/products/azure-sql/database) (via PDO, experimental)

### Optional Search engine

- [Elasticsearch](https://www.elastic.co/products/elasticsearch) 7.x or 8.x
- [OpenSearch](https://opensearch.org/) 2.x

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

In case you're running PHP before 8.3, you cannot use phpMyFAQ 4.1.

### Browser requirements

phpMyFAQ uses a modern HTML5/CSS3 powered markup. The supported browsers are the latest Mozilla Firefox
(Windows/macOS/Linux), the latest Safari (macOS/iOS), the latest Chrome (Windows/macOS/Linux), the latest Opera
(Windows/macOS/Linux) and Microsoft Edge (Windows/macOS/Linux).

We recommend using the latest version of Firefox, Chrome, Safari, Opera or Microsoft Edge.

## 2.2 Preparations

### 2.2.1 Classic Shared Web Hosting

You can install phpMyFAQ via one of the provided packages as .zip or .tar.gz or using Git. If you choose our package,
download it and unzip the archive on your hard disk.

If you want to use Git, please run the following commands on your shell:

    $ git clone git@github.com:thorsten/phpMyFAQ.git 4.0
    $ cd phpMyFAQ
    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install
    $ curl -fsSL https://get.pnpm.io/install.sh | sh -
    $ pnpm install
    $ pnpm build

You can modify the layout of phpMyFAQ using templates. A description of how this is done can be found in the development section.
Copy all unzipped files to your web server in a directory using FTP. A good choice would be the directory **faq/**.

**Important:**
Writing permission for your script is needed in this directory to be able to write the file **content/core/config/database.php**
during installation. The installation script will stop when your web server isn't configured as needed.

It might help to set chmod 775 to the whole phpMyFAQ directory to avoid problems during the installation. If you're
running a very restrictive mod_php installation you should keep the chmod 775 for the following files and directories
even after the successful installation:

- the directory **content/core/config/**
- the directory **content/core/data/**
- the directory **content/core/logs/**
- the directory **content/user/attachments/**
- the directory **content/user/images/**

All other directories shouldn't be world-writable for your own security.

**Note**: If you're running SELinux, you may need further configuration, or you should completely disable it.

The database user needs the permissions for CREATE, DROP, ALTER, INDEX, INSERT, UPDATE, DELETE and SELECT on all tables
in the database.

### 2.2.2 Cloud Hosting via Docker

You first need a database, let's try with a MariaDB container:

    $ docker run -ti -n phpmyfaq-db mariadb

Then start the phpMyFAQ web application:

    $ docker run -ti --link phpmyfaq-db:db -p 8080:80 phpmyfaq/phpmyfaq

### 2.2.3 Cloud or On-Premise Hosting via Kubernetes

You can use any mysql deployment/helm chart, like: https://github.com/bitnami/charts/tree/main/bitnami/mysql, you can
also choose another database engine.
View official resources or find one on: https://artifacthub.io/

> You must have a storage method to persist your data.

Then, install it, using referenced manifests on this project: https://github.com/thorsten/phpMyFAQ/tree/main/kubernetes-deploy.

Read the definitions and configure according to your needs. If you have any doubt, do not hesitate to consult us.

## 2.3 PHP settings

- We recommend using a PHP accelerator or OpCode cache
- Allocate at least 128 MB of memory to each PHP process
- Required extensions: GD, JSON, Session, MBString, Filter, XMLWriter, SPL, FileInfo
- Recommended configuration:

        memory_limit = 128M
        file_upload = on

## 2.5 Server side recommendations

**_MySQL / Percona Server / MariaDB_**

    interactive_timeout = 120
    wait_timeout = 120
    max_allowed_packet = 64M

## 2.6 Setup

Open your browser and type in the following URL:

`http://www.example.com/faq/setup/`

### Step 1: Database server

Substitute **www.example.com** with your actual domain name. When the site is loaded, first select the database you want
to use for phpMyFAQ. The loaded database extensions from PHP are listed in a select box. Then enter the address of your
database server (e.g. db.provider.com), the database port, your database username and password as well as the database
name. The database have to be created with UTF-8 character set before running the installation script. You can leave the
prefix-field empty. If you are planning on using multiple FAQs in one database you will have to use a table prefix,
though (i.e. _sport_ for a sports FAQ, _weather_ for a weather FAQ, etc.). Please note that only letters and an
underline: "\_" can be used as the prefix. If you want to use SQLite, you only have to select a path to the database file
of SQLite.

### Step 2: LDAP or Microsoft Active Directory support

If PHP was compiled with the LDAP extension, you can add your LDAP or Microsoft Active Directory information, too.
Then you can insert your LDAP or Microsoft Active Directory information as well.

### Step 3: Elasticsearch and OpenSearch support

If you want to use Elasticsearch or OpenSearch, you can activate this in the third step.
You have to add at least one Elasticsearch or OpenSearch node and the index name.

### Step 4: Admin user setup

In addition, you can enter your language, default here is English. Furthermore, you should register your name, your
email address and - very importantly - your password. You must enter the password twice, and it has to be at least eight
characters long. Then click the button **"install"** to initialize the tables in your database.

## 2.7 First Steps

You can enter the public area of your FAQ by entering

`http://www.example.com/faq/index.php`

into your browser's address field. Your FAQ will be empty and presented in the the standard layout.

To configure phpMyFAQ point your browser to

`http://www.example.com/faq/admin/index.php`

Please use your chosen username and your password for your first login into the admin section.

Some variables that do not change regularly, they can be edited in the file _content/core/config/constants.php_.
You can change

- the time zone of your server (default: "Europe/Berlin")
- the timeout in the admin section (default: 300 minutes)
- the timeout warning pop-up in the admin section (default: 5 minutes)
- the solution id start value (default: 1000)
- the incremental value of the solution id (default: 1)
- the number of records in the Top10 (default: 10)
- the number of the latest records (default: 5)
- flag with which a Google site map will be forced to use the current phpMyFAQ SEO URL schema (default: true)
- the number with which the Tags Cloud list is limited to (default: 50)
- the number with which the autocomplete list is limited to (default: 20)
- the default encryption type for passwords

## 2.8 Notes regarding the search functionality

- The boolean full-text search will only work with MySQL and if there are some entries in the database (5 or more).
  The term you are looking for should also not be in more than 50% of all your entries, or it will automatically be
  excluded from search. This is not a bug, but rather a feature of MySQL.
- The search on other databases is using the LIKE operator currently.
- To improve the search functionality, you should consider using Elasticsearch or OpenSearch.

## 2.9 Automatic user language detection

To set the default language in your browser, you have to set a variable that gets passed to the web server.
How this is done depends on the browser you are using.

- Mozilla Firefox: Tools -> Options -> Content -> Languages
- Google Chrome / Microsoft Edge / Opera: Settings → Details → Language settings
- Safari uses the macOS system preferences to determine your preferred language: System preferences → International
  → Language

## 2.10 Enabling LDAP or Microsoft Active Directory support

If you're entered the correct LDAP or Microsoft Active Directory information during the installation, you have to enable
the LDAP or Microsoft Active Directory support in the configuration in the admin backend. Now your user can authenticate
themselves in phpMyFAQ against your LDAP server or a Microsoft Active Directory server.

If you need special options for your LDAP or ADS configuration, you can change the LDAP configuration in the admin
configuration panel.

If you want to add LDAP support later, you can use the file **content/core/config/ldap.php.original** as template, and
if you rename it to **content/core/config/ldap.php** you can use the LDAP features as well after you enabled it in the
administration backend.

Please note that you have to use the correct LDAP attributes for your LDAP server. 
The LDAP server address must be in the format "ldap://ldap.example.com"
or "ldaps://ldap.example.com" for secure connections.

The "samAccountName" attribute is used for the user's login name,
and the "mail" attribute is used for the email address.

If you want to use LDAP with a self-signed certificate,
you have to add the following configuration to /etc/ldap/ldap.conf:

    TLS_REQCERT never.


## 2.11 PDF export

Main features of the PDF export:

- supports all ISO page formats;
- supports custom page formats, margins and units of measure;
- supports UTF-8 Unicode and Right-To-Left languages;
- supports TrueTypeUnicode, OpenTypeUnicode, TrueType, OpenType, Type1 and CID-0 fonts;
- includes methods to publish some HTML code;
- includes graphic (geometric) and transformation methods;
- includes methods to set Bookmarks and print a Table of Content;
- supports automatic page break;
- supports automatic page numbering and page groups;
- supports automatic line break and text justification;
- supports JPEG and PNG images natively, all images supported by GD (GD, GD2, GD2PART, GIF, JPEG, PNG, BMP, XBM, XPM)

## 2.12 Static solution ID

phpMyFAQ features a static solution ID which never changes. This ID is visible next to the question on a FAQ record
page. You may think why you need such an ID? If you have a record ID _1042_ it is now possible to enter only the ID
_1042_ in the input field of the full-text search box, and you'll be automatically redirected to the FAQ record with the
ID _1042_. By default, the numbers start at ID **1000**, but you can change this value in the file _inc/constants.php_.
You can also change the value of the incrementation of the static IDs.

## 2.13 Spam protection

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

## 2.14 Attachments

phpMyFAQ supports encrypted attachments. The encryption uses the [AES](http://en.wikipedia.org/wiki/Advanced_Encryption_Standard)
algorithm implemented in mcrypt extension (if available) or with native PHP Rijndael implementation. The key size vary
depending on the implementation used and can be max 256 bits long. Use of mcrypt extension is strongly recommended because
of performance reasons, its availability is checked automatically at the run time.

Please be aware:

- Disabling encryption will cause all files be saved unencrypted. In this case, you'll benefit from sparing disk space,
  because identical files will be saved only once.
- Do not change the default attachment encryption key once files were uploaded.
  Doing so will cause all the previously uploaded files to be wrong decrypted.
  If you need to change the default key, you will have to re-upload all files.
- Always memorize your encryption keys. There is no way to decrypt files without a correct key.
- Files are always saved with names based on a virtual hash generated from several tokens (just like key and issue id
  etc), so there is no way to assess a file directly using the name it was uploaded under.
- Download continuation isn't supported.

## 2.15 Syntax Highlighting

The bundled [highlight.js](https://highlightjs.org/) syntax highlighting component will find and highlight code inside
&lt;pre&gt;&lt;code&gt; tags; it tries to detect the language automatically. If automatic detection doesn't work for
you, you can specify the language in the class attribute:

    <pre><code class="html">...</code></pre>

The list of supported language classes is available in the class reference. Classes can also be prefixed with either
language- or lang-.

To disable highlighting altogether, use the "nohighlight" class:

    <pre><code class="nohighlight">...</code></pre>

## 2.16 Elasticsearch Support

To improve the search performance and quality of search results, it's possible to use Elasticsearch.
You need a running Elasticsearch instance accessible by phpMyFAQ via HTTP/REST.
You can add the IP(s)/Domain(s) and port(s) of your Elasticsearch cluster during installation or later by renaming the
Elasticsearch file located in the folder config/.
If you choose to add this during installation, the file will be automatically written and the index will be built.
If you enabled Elasticsearch support in the admin configuration panel, you can create, re-import and delete your
index with a user-friendly interface.

## 2.17 OpenSearch Support

To improve the search performance and quality of search results, it's possible to use OpenSearch.
You need a running OpenSearch instance accessible by phpMyFAQ via HTTP/REST.
You can add the IP(s)/Domain(s) and port(s) of your OpenSearch cluster during installation or later by renaming the
OpenSearch file located in the folder config/.
If you choose to add this during installation, the file will be automatically written and the index will be built.
If you enabled OpenSearch support in the admin configuration panel, you can create, re-import and delete your
index with a user-friendly interface.

## 2.18 SSO (Single Sign-On) Support

phpMyFAQ supports SSO (Single Sign-On)
with the REMOTE_USER server variable is populated by the web server or application server
to indicate the authenticated user's identity.
This is commonly used by several SSO systems that integrate with web servers,
especially when using standard authentication mechanisms like HTTP Basic Authentication,
HTTP Digest Authentication, or more advanced protocols.

### Configuring Apache with `mod_php` to Pass `REMOTE_USER` to PHP

When using Apache with `mod_php`,
the `REMOTE_USER` variable can be automatically passed to PHP without the need for PHP-FPM.
Here’s how to configure Apache to pass `REMOTE_USER` to PHP when using basic authentication or Single Sign-On
(SSO) systems.

#### Step 1: Configure Apache to Pass `REMOTE_USER`

In your Apache virtual host configuration file (commonly located at `/etc/apache2/sites-available/your-site.conf`),
add the following directives to ensure `REMOTE_USER` is passed to PHP.

```apache
<VirtualHost *:80>
    ServerName your-site.com
    DocumentRoot /var/www/html

    <Directory "/var/www/html/admin">
        # Enable basic authentication or SSO
        AuthType Basic
        AuthName "Restricted Area"
        AuthUserFile /etc/apache2/.htpasswd
        Require valid-user

        # Ensure REMOTE_USER is available for PHP
        SetEnvIf Authorization "(.*)" REMOTE_USER=$1
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

In this configuration:

- The `AuthType` and `AuthUserFile` specify the use of Basic Authentication, but this can be adapted for other authentication mechanisms (such as SSO).
- The `SetEnvIf` directive ensures that `REMOTE_USER` is passed to PHP correctly.

If using Basic Authentication, create a `.htpasswd` file with usernames and encrypted passwords. You can skip this step if using an SSO system.

```bash
sudo htpasswd -c /etc/apache2/.htpasswd username
```

#### Step 2: Verify `mod_php` is Enabled

Ensure that the `mod_php` module is enabled in Apache:

```bash
sudo a2enmod php7.4
sudo systemctl restart apache2
```

Make sure to replace `php7.4` with your PHP version if it differs.

#### Step 3: Restart Apache

After making these changes, restart Apache to apply the new configuration:

```bash
sudo systemctl restart apache2
```

#### Step 4: Test the Configuration

To confirm that `REMOTE_USER` is being passed correctly, create a simple PHP file to output the `REMOTE_USER` value:

```php
<?php
echo 'REMOTE_USER: ' . $_SERVER['REMOTE_USER'];
```

Access this PHP file in the restricted area to ensure the `REMOTE_USER` variable is correctly populated.

### Configuring Apache and PHP-FPM to Pass `REMOTE_USER` to PHP

To make the `REMOTE_USER` variable available to PHP through Apache,
follow these steps to modify both the Apache configuration and the PHP-FPM settings.

#### Step 1: Modify Apache Configuration to Pass `REMOTE_USER`

Ensure that the `mod_proxy_fcgi` module is enabled in Apache. This module is responsible for handling FastCGI requests:

```bash
sudo a2enmod proxy_fcgi
sudo systemctl restart apache2
```

In your Apache virtual host configuration file (commonly located at `/etc/apache2/sites-available/your-site.conf`),
add the following configuration to ensure that `REMOTE_USER` is passed to PHP-FPM:

```apache
<VirtualHost *:80>
    ServerName your-site.com
    DocumentRoot /var/www/html

    <Location "/admin">
        # Pass REMOTE_USER to PHP-FPM
        SetEnvIf Authorization "(.*)" REMOTE_USER=$1

        ProxyPassMatch ^/(.*\.php(/.*)?)$ unix:/var/run/php/php7.4-fpm.sock|fcgi://localhost/var/www/html
    </Location>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

This configuration ensures that Apache passes the `REMOTE_USER` variable to PHP-FPM,
specifically for requests to the `/admin` section.

#### Step 2: Modify PHP-FPM Configuration

Open the PHP-FPM pool configuration file
(commonly located at `/etc/php-fpm.d/www.conf` or similar, depending on your PHP version):

```bash
sudo nano /etc/php-fpm.d/www.conf
```

Find the following line and uncomment it to ensure that environment variables are passed through to PHP:

```ini
clear_env = no
```

#### Step 3: Restart Services

After making these changes, restart both Apache and PHP-FPM to apply the configuration:

```bash
sudo systemctl restart apache2
sudo systemctl restart php-fpm
```

#### Step 4: Test the Configuration

To confirm that `REMOTE_USER` is being passed correctly, create a simple PHP file to output the `REMOTE_USER` value:

```php
<?php
echo 'REMOTE_USER: ' . $_SERVER['REMOTE_USER'];
```

Access this PHP file through your browser in the admin area to ensure the `REMOTE_USER` variable is correctly populated.

### Configuring nginx and PHP-FPM to Pass `REMOTE_USER` to PHP

To make the `REMOTE_USER` variable available to PHP through nginx,
follow these steps to modify both the nginx configuration and the PHP-FPM settings.

#### Step 1: Modify nginx Configuration to Pass `REMOTE_USER`

Open the FastCGI parameters file in nginx. This file is typically located at `/etc/nginx/fastcgi_params.default`:

```bash
sudo nano /etc/nginx/fastcgi_params.default
```

Add the following line to pass the `REMOTE_USER` variable from nginx to PHP:

```nginx
fastcgi_param REMOTE_USER $remote_user;
```

In the nginx configuration file for your specific site (usually located at `/etc/nginx/sites-available/your-site.conf`),
ensure the `REMOTE_USER` variable is passed to PHP only in the appropriate location blocks
(e.g., admin areas).

Example configuration for the admin area:

```nginx
location ~ \.php$ {
    # Other configurations
    fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    fastcgi_param REMOTE_USER $remote_user;  # Pass REMOTE_USER to PHP
    include fastcgi_params;
}
```

#### Step 2: Modify PHP-FPM Configuration

Open the PHP-FPM pool configuration file (commonly located at `/etc/php-fpm.d/www.conf` or similar, depending on your PHP version):

```bash
sudo nano /etc/php-fpm.d/www.conf
```

Find the following line and uncomment it to ensure that environment variables are passed through to PHP:

```ini
clear_env = no
```

#### Step 3: Restart Services

After making these changes, restart both nginx and PHP-FPM to apply the configuration:

```bash
sudo systemctl restart nginx
sudo systemctl restart php-fpm
```

#### Step 4: Test the Configuration

To confirm that `REMOTE_USER` is being passed correctly, create a simple PHP file to output the `REMOTE_USER` value:

```php
<?php
echo 'REMOTE_USER: ' . $_SERVER['REMOTE_USER'];
```

Access this PHP file through your browser in the admin area to ensure the `REMOTE_USER` variable is correctly populated.
