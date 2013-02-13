### [ phpMyFAQ 2.8.x Documentation ]

1.  **[Introduction][1]**
    1.  [License][2]
    2.  [Support][3]
    3.  [Copyright][4]
2.  **[Installation][5]**
    1.  [Requirements][6]
    2.  [Preparations][7]
    3.  [Setup][8]
    4.  [First Steps][9]
    5.  [Notes regarding the search-function][10]
    6.  [Automatic content negotiation][11]
    7.  [PHP settings][12]
    8.  [Enabling support for SEO-friendly URLs][13]
    9.  [Enabling LDAP support][14]
    10. [PDF export][15]
    11. [Mozilla Firefox, Google Chrome and IE8+ search plugins][16]
    12. [Static solution ID][17]
    13. [Spam protection][18]
    14. [Attachments][19]
    15. [Twitter][20]
    16. [Caching][21]
3.  **[Upgrading][22]**
    1.  [Upgrading from phpMyFAQ 2.5.x][23]
    2.  [Upgrading from phpMyFAQ 2.6.x][24]
    3.  [Upgrading from phpMyFAQ 2.7.x][25]
    4.  [Upgrading phpMyFAQ 2.8.x versions][26]
    5.  [Modifying templates for phpMyFAQ 2.8.x][27]
4.  **[Frontend][28]**
    1.  [Change languages][29]
    2.  [RSS Feeds][30]
    3.  [Advanced search][31]
    4.  [Instant Response][32]
    5.  [Add FAQ][33]
    6.  [Add questions][34]
    7.  [Open questions][35]
    8.  [Submit translation][36]
    9.  [Social networks][37]
    10. [Internal references][38]
    11. [Public user registration][39]
    12. [Complete secured FAQ][40]
5.  **[Administration][41]**
    1.  [User Administration][42]
    2.  [Group Administration][43]
    3.  [Category Administration][44]
    4.  [Record Administration][45]
    5.  [Comment Administration][46]
    6.  [Glossary][47]
    7.  [News Administration][48]
    8.  [Attachment Administration][49]
    9.  [Statistics][50]
    10. [Exports][51]
    11. [Backup][52]
    12. [Configuration][53]
    13. [Multisite Configuration][54]
6.  **[Customizing phpMyFAQ][55]**
    1.  [The file assets/template/default/index.tpl][56]
    2.  [The file assets/template/default/style.css][57]
    3.  [More Templates][58]
7.  **[Customizing phpMyFAQ][59]**
    1.  [Creating custom layout][60]
8.  **[Developer documentation][61]**
    1.  [phpMyFAQ development][91]
    2.  [rest/json API][62]
9.  **[One more thing][63]**

* * *

**1. <a id="1"></a>Introduction**

phpMyFAQ is a multilingual, completely database-driven FAQ-system. It supports various databases to store all data, PHP 5.3.3 (or higher) is needed in order to access this data. phpMyFAQ also offers a multi-language Content Management-System with a WYSIWYG editor and an Image Manager, flexible multi-user support with user and group based permissions on categories and records, a wiki-like revision feature, a news system, user-tracking, language modules, enhanced automatic content negotiation, templates, extensive XML-support, PDF-support, a backup-system, a dynamic site map, related articles, tagging, RSS feeds, built-in spam protection systems, LDAP support, Twitter and Facebook support and an easy to use installation script.

This documentation should help you with installing, administrating and using phpMyFAQ.

[back to top][64]

* * *

**1.1. <a id="1.1"></a>License**

phpMyFAQ is published under the [Mozilla Public License Version 2.0](http://www.mozilla.org/MPL/2.0/) (MPL). This license guarantees you the free usage of phpMyFAQ, access to the source code and the right to modify and distribute phpMyFAQ.

The only restrictions apply to the copyright, which remains at all times at Thorsten Rinne and the phpMyFAQ Team. Any modified versions of phpMyFAQ will also fall under the terms of MPL. Any other program, that may only be accessing certain functions of phpMyFAQ is not affected by these restrictions and may be distributed under any type of license.

A commercial usage or commercially distribution of phpMyFAQ, e.g. on CD-ROMs, is allowed, as long as the conditions mentioned above are met.

We decided to use MPL as the licensing model for phpMyFAQ because we feel that it is a good compromise between the protection of the openness and free distribution on the one hand and the interaction with other software regardless of its licensing model. When compared to other licensing models its text is short and easily comprehensible, even for newcomers.

This documentation is licensed under a [Creative Commons License](http://creativecommons.org/licenses/by/2.0/).

[back to top][64]

* * *

**1.2. <a id="1.2"></a>Support**

If you should run into any problems using phpMyFAQ check out our support forums at [forum.phpmyfaq.de](http://forum.phpmyfaq.de). You can also use our Twitter account [@phpMyFAQ](https://twitter.com/phpMyFAQ/) to ask us short question if they fit into 140 characters. There is no free support by phone or email, please refrain from calling or mailing us.

The phpMyFAQ team offers the following paid services:

*   Customizing
*   Support

If you're interested, just take a look at our [support page](http://www.phpmyfaq.de/support.php).

[back to top][64]

* * *

**1.3. <a id="1.3"></a>Copyright**

© 2001-2013 by Thorsten Rinne and phpMyFAQ Team under the [ Mozilla Public License 2.0](http://www.mozilla.org/MPL/2.0/). All rights reserved.

[back to top][64]

* * *

**2.Installation**

**2.1. <a id="2.1"></a>Requirements for phpMyFAQ**

phpMyFAQ addresses a database system via PHP. In order to install it you will need a web server that meets the following requirements:

*   **[PHP](http://www.php.net)**
    *   from version 5.3.3 (recommended: latest PHP 5.x)
    *   register_globals = off
    *   magic_quotes_gpc = off
    *   safe_mode = off (recommended)
    *   memory_limit = 64M
    *   GD support
    *   XMLWriter support
    *   JSON support
    *   Filter support
    *   SPL support
*   **Web server** ( [Apache](http://httpd.apache.org) 2.x or [nginx](http://www.nginx.net/) 0.7+ or [lighttpd](http://www.lighttpd.net) 1.0+ or [IIS](http://www.microsoft.com/) 6.0+ or Zeus Webserver)
*   **Database server**
    *   [MySQL](http://www.mysql.com) 5.x with the MySQL extension (recommended: 5.5.x)
    *   [MySQL](http://www.mysql.com) 5.x with the MySQLi extension (recommended: 5.5.x)
    *   [PostgreSQL](http://www.postgresql.org) 8.x (recommended: latest 8.x)
    *   [Microsoft SQL Server](http://www.microsoft.com/sql/) 2005, 2008, 2012
    *   [SQLite](http://www.sqlite.org)
    *   [MariaDB](http://montyprogram.com/mariadb/) 5.x (experimental)
*   correctly set: access permissions, owner, group

You can only run phpMyFAQ successfully, when the PHP directives safe_mode, register_globals and magic_quotes_gpc is set to off, further constraints affect the directives open_basedir and disable_functions, which can be set in the central php.ini or the httpd.conf respectively.

In case PHP runs as module of the Apache, you will have to be able to do a chown on the files before installation. The files and directories must be owned by the webserver's user.

You can determine which versions your web server is running by creating a file called **info.php** with the following content: `<?php phpinfo(); ?>`

Upload this file to your webspace and open it using your browser. The installation-script checks which version of PHP is installed on your server. Should you not meet the requirements, you cannot start the installation process.

In case you have PHP below 5.3.2 installed you cannot use phpMyFAQ.

phpMyFAQ uses a modern HTML5/CSS3 powered markup. The supported browsers are Mozilla Firefox 3.6 and later (Windows/OS X/Linux), Safari 5.x or later (OS X/Windows/iOS), Chrome 5 or later (Windows/OS X/Linux), Opera 11.0 or later (Windows/OS X/Linux) and Internet Explorer 7 or later for Windows. You have to enable JavaScript for the Ajax based functions as well.

We recommend to use always the latest version of Firefox, Chrome, Safari, Opera and Internet Explorer.

If you're using *lighttpd* you have to set the following configuration:

`cgi.fix_pathinfo 1`

[back to top][64]

* * *

**2.2. <a id="2.2"></a>Preparations**

You can install phpMyFAQ via one of the provided packages as .zip or .tar.gz or using Git. If you choose our package, download it and unzip the archive on your hard disk. If you want to use Git, please run the following commands on your shell:

	$ git clone git@github.com:thorsten/phpMyFAQ.git
	$ cd phpMyFAQ
	$ curl -s https://getcomposer.org/installer | php
	$ php composer.phar install

You can modify the layout of phpMyFAQ using templates. A description of how this is done can be found [below][55]. Copy all unzipped files to your web server in a directory using FTP. A good choice would be the directory **faq/**.
**Important:**
Writing permission for your script is needed in this directory to be able to write the file **config/database.php** during installation. This is the case if you're running PHP as CGI or as mod_php with disabled safe-mode. The installation script will stop when your web server isn't configured as needed.

It might help to set chmod 777 to the whole phpMyFAQ directory to avoid problems during the installation. If you're running a very restrictive mod_php installation you should keep the chmod 777 for the following files and directories even after the successful installation:

*   the directory **config/**
*   the directory **attachments/**
*   the directory **images/**

All other directories shouldn't be world-writable for your own security.

The database user needs the permissions for CREATE, DROP, ALTER, INDEX, INSERT, UPDATE, DELETE and SELECT on all tables in the database.

[back to top][64]

* * *

**2.3. <a id="2.3"></a>Setup**

Open your browser and type in the following URL:

`http://www.example.com/faq/install/setup.php`

Substitute **www.example.com** with your actual domain name. When the site is loaded enter the address of your database server (e.g. db.provider.com), your database username and password as well as the database name. The database have to be created with UTF-8 chraracter set before running the installation script. You can leave the prefix-field empty. If you are planning on using multiple FAQs in one database you will have to use a table prefix, though (i.e. *sport* for a sports FAQ, *weather* for a weather FAQ, etc.). Please note that only letters and an underline: "_" can be used as the prefix.

If your PHP was compiled with the LDAP extension you can add your LDAP information, too. You have to insert your LDAP information, too.

When using multiple FAQs you need to install them independently into different directories (e.g. faq1/, faq2/. faq3/ etc.). In addition you can enter your language, default here is English. Furthermore you should register your name, your email address and - very importantly - your password. You must enter the password twice and it have to be at least six places long. Then click the button **"install"** to initialize the tables in your database.

[back to top][64]

* * *

**2.4. <a id="2.4"></a>First Steps**

You can enter the public area of your FAQ by entering

`http://www.example.com/faq/index.php`

into your browser's address field. Your FAQ will be empty and presented in the the standard layout.

To configure phpMyFAQ point your browser to

`http://www.example.com/faq/admin/index.php`

Use the username **admin** and your selected password for your first login into the admin section.

Some variables that doesn't change regularly, they can be edited in the file *config/constants.php*. You can change the

* the time zone of your server (default: "Europe/Berlin")
* the timeout in the admin section (default: 30 minutes)
* the timeout warning pop-up in the admin section (default: 5 minutes)
* the solution id start value (default: 1000)
* the incremental value of the solution id (default: 1)
* the number of records in the Top10 (default: 10)
* the number of latest records (default: 5)
* the number of open questions in the RSS feed (default: 50)
* flag with which Latest and Top Ten RSS feeds will be forced to use the current PMF SEO URL schema (default: true)
* flag with which Google site map will be forced to use the current PMF SEO URL schema (default: true)
* the number with which the Tags Cloud list is limited to (default: 50)
* the number with which the autocomplete list is limited to (default: 20)

[back to top][64]

* * *

**2.5. <a id="2.5"></a>Notes regarding the search functionality**

*   The boolean full-text search will only work with MySQL and if there are some entries in the database (5 or more). The term you are looking for should also not be in more than 50% of all your entries, or it will automatically be excluded from search. This is not a bug, but rather a feature of MySQL.
*   The search on other databases are using currently the LIKE operator.

[back to top][64]

* * *

**2.6. <a id="2.6"></a>Automatic content negotiation**

To set the default language in your browser you have to set a variable that gets passed to the web server. How this is done depends on the browser you are using.

* Mozilla Firefox

    `Tools -> Options -> Content -> Languages`
* Mozilla Seamonkey and later versions

    `Edit -> Preferences -> Navigator -> Languages`
* Google Chrome

    `Settings -> Details -> Language settings`
* Safari

  Safari uses the OS X system preferences to determine your preferred language:
  
  `System preferences -> International -> Language`
* Opera

  `File -> Preferences -> Languages`
* Internet Explorer 

   `Tools or View or Extras -> Internet Options -> (General) Languages`

[back to top][64]

* * *

**2.7. <a id="2.7"></a>PHP settings**

* We recommend using a PHP accelerator or opcode cache like APC
* Allocate at least 64MB of memory to each PHP process
* Required extensions: GD, JSON, Session, MBString, Filter, XMLWriter, SPL
* Recommended configuration:

    	register_globals = off
    	safe_mode = off (recommended)
    	memory_limit = 64M
    	file_upload = on


[back to top][64]

* * *

**2.8. <a id="2.8"></a>Enabling support for SEO-friendly URLs**

*Apache Web server*

If you want to enable the search engine optimization you have to rename the file \_.htaccess to .htaccess in the root directory where your FAQ is located. Then you have to activate the mod\_rewrite support in the admin backend in the configuration page. You also have to edit the path information for the "RewriteBase". If you installed phpMyFAQ on root directory "/" you should set in `RewriteBase /`
Please check, if `AllowOverride All`	is set correctly in your httpd.conf file so that the .htaccess rules work.

*IIS Web server*

If you want to enable the search engine optimization you have to rename the file _httpd.ini to httpd.ini in the root directory where your FAQ is located. Then you have to activate the URL rewrite support in the admin backend in the configuration page.

*nginx Web server*

If you want to enable the search engine optimization you have to copy the rewrite rules in the file _nginx.conf to your nginx.conf. Then you have to activate the URL rewrite support in the admin backend in the configuration page.

*lighttpd Web server*

If you want to enable the search engine optimization you have to copy the rewrite rules in the file _lighttpd.conf to your lighttpd.conf. Then you have to activate the URL rewrite support in the admin backend in the configuration page.

*Zeus Web server*

If you want to enable the search engine optimization you have to use the rewrite rules in the file rewrite.script. Then you have to activate the URL rewrite support in the admin backend in the configuration page.

[back to top][64]

* * *

**2.9. <a id="2.9"></a>Enabling LDAP support**

If you're entered the correct LDAP information during the installation you have to enable the LDAP support in the configuration in the admin backend. Now your user can authenticate themselves in phpMyFAQ against your LDAP server or even an Microsoft Active Directory.

If you need special options for your LDAP or ADS configuration you have to edit the LDAP constants in the file **config/constants_ldap.php**.

If you want to add LDAP support later, you can use the file **config/ldap.php.original** as template and if you rename it to **config/ldap.php** you can use the LDAP features as well after you enabled it in the administration backend.

[back to top][64]

* * *

**2.10. <a id="2.10"></a>PDF export**

Main features of the PDF export:

*   supports all ISO page formats;
*   supports custom page formats, margins and units of measure;
*   supports UTF-8 Unicode and Right-To-Left languages;
*   supports TrueTypeUnicode, OpenTypeUnicode, TrueType, OpenType, Type1 and CID-0 fonts;
*   includes methods to publish some HTML code;
*   includes graphic (geometric) and transformation methods;
*   includes methods to set Bookmarks and print a Table of Content;
*   supports automatic page break;
*   supports automatic page numbering and page groups;
*   supports automatic line break and text justification;
*   supports JPEG and PNG images natively, all images supported by GD (GD, GD2, GD2PART, GIF, JPEG, PNG, BMP, XBM, XPM)

[back to top][64]

* * *

**2.11. <a id="2.11"></a>Mozilla Firefox, Google Chrome and IE8+ search plugins**

phpMyFAQ provides search plugins for Mozilla Firefox, Google Chrome and Internet Explorer 8+ based on the OpenSearch specification. Every user in the frontend can install it. With an installed search plugin you can search through the phpMyFAQ installation with the search box in upper right corner of Mozilla Firefox, Google Chrome or Internet Explorer.

[back to top][64]

* * *

**2.12. <a id="2.8"></a>Static solution ID**

phpMyFAQ implements a static solution ID which never changes. This ID is displayed next to the question on a FAQ record page. You may think why do you need such an ID? If you have a record ID *1042* it is now possible to enter only the ID *1042* in the input field of the full-text search box and you'll be automatically redirected to the FAQ record with the ID *1042*. By default the numbers start at ID *1000* but you can change this value in the file *inc/constants.php*. You can also change the value of the incrementation of the static IDs.

[back to top][64]

* * *

**2.13. <a id="2.13"></a>Spam protection**

phpMyFAQ performs these three checks on public forms:

1.  Check against IPv4 and IPv6 Network address
2.  Check against banned words
3.  Check against the captcha code

The IPv4 and IPv6 Network addresses can be added or removed in the configuration panel in the administration backend. If you want to add banned words to phpMyFAQ, then you have to edit the file *inc/blockedwords.txt*. Please add only one word per line.

[back to top][64]

* * *

**2.14. <a id="2.14"></a>Attachments**

phpMyFAQ supports encrypted attachments. The encryption is done using the [AES](http://en.wikipedia.org/wiki/Advanced_Encryption_Standard) algorithm implemented in mcrypt extension (if avaliable) or with native PHP Rijndael implementation. The key size vary depending on implementation used and can be max 256 bits long. Use of mcrypt extension is strongly recommended because of performance reasons, its avaliability is checked automatically at the run time.

See the migration section if you're migrating from earlier versions.

Please be aware:

* Disabling encryption will cause all files be saved unencrypted. In this case you'll benefit sparing disk space, because identical files will be saved only once.
* Do not change the default attachment encryption key once files was uploaded. Doing so will cause all the previously uploaded files to be wrong decrypted. If you need to change the default key, you will have to re-upload all files.
* Always memorize your encryption keys. There is no way to decrypt files without a correct key.
* Files are always saved with names based on a virtual hash generated from several tokens (just like key and issue id etc), so there is no way to asses a file directly using the name it was uploaded under.
* Download continuation isn't (yet?) supported.

[back to top][64]

* * *

**2.15. <a id="2.8"></a>Twitter support**

phpMyFAQ supports Twitter via OAuth. If you enable Twitter support in the social network configuration and add phpMyFAQ as a Twitter application on [twitter.com](https://dev.twitter.com/apps/new), all new FAQ additions in the administration backend will also post the question of the FAQ, the URL of the FAQ and all tags as hashtags to Twitter, e.g. the tag "phpMyFAQ" will be converted to "#phpmyfaq".

[back to top][64]

* * *

**2.16. <a id="2.8"></a>Caching**

phpMyFAQ supports server-side caching. Supported cache services are:

*   **Varnish**
    Required are Varnish Cache accelerator >=3.0 and Varnish PECL extension >= 0.9.1

    VCL sample, merge it with yours:

        backend default {
                .host = "127.0.0.1";
                .port = "8070";
        }

        sub vcl_recv {
                if (!(req.url ~ "^/admin.*")) {
                        unset req.http.cookie;
                }
        }

        sub vcl_fetch {
                if (!(req.url ~ "^/admin.*")) {
                        unset beresp.http.set-cookie;
                        unset beresp.http.expires;
                        unset beresp.http.cache-control;
                        unset beresp.http.pragma;
                        unset beresp.http.last-modified;

                        set beresp.ttl = 86000s;
                }

                if (req.url ~ "/.*(action=add|action=contact|action=ask).*") {
                        set beresp.ttl = 0s;
                }
        }


    As you can see, all cookie are deleted except the admin area, which allows the cache to perform on the frontend, but work as admin in the backend. Also for the non admin area all the cache related backend headers get deleted, and ttl is set to one day. There is almost much room for improvement. Please notify us, if you have discovered some essential VCL improvements to this sample, so we can improve this documentation.

    Once an article is saved, its cache and all related items cache is cleared.

[back to top][64]

* * *

**3. <a id="3"></a>Upgrading**

Upgrading to phpMyFAQ 2.8.x is possible from the following versions:

*   phpMyFAQ 2.5.x
*   phpMyFAQ 2.6.x
*   phpMyFAQ 2.7.x
*   phpMyFAQ 2.8.x

If you're running an older version of phpMyFAQ we recommend a new and fresh install. If you need support for updating an old FAQ from the 1.x or 2.0.x series, please send us an [e-mail][68].

[back to top][64]

* * *

**3.1. <a id="3.1"></a>Upgrading from phpMyFAQ 2.5.x**

Upgrading from 2.5.x is a major upgrade. Please make a full backup before you run the upgrade! First you have to delete all files **except**:

*   the file **data.php** in the directory **inc/**
*   all files in the **template/** directory
*   the directory **attachments/**
*   the directory **data/**
*   the directory **images/**

Open the following URL in your browser:

`http://www.example.com/faq/install/update.php`

Choose your installed phpMyFAQ version and click the button of the update script, your version will automatically be updated. You have to update a lot of your template files due to our change using the Bootstrap framework.

The update script will move your template files to a folder **backup/** in the directory **assets/template/**. The default layout will be stored in the folder **assets/template/default/**.

Note: We changed the character set for all languages and templates to UTF-8. If you notice problems with e.g. German umlauts you have to convert your templates to UTF-8 encoding.

Please copy the template file **assets/template/default/indexLogin.tpl** into your template folder.

[back to top][64]

* * *

**3.2. <a id="3.2"></a>Upgrading from phpMyFAQ 2.6.x**

Upgrading from 2.6.x is a major upgrade. Please make a full backup before you run the upgrade! First you have to delete all files **except**:

*   all files in the directory **config/**
*   all files in the directory **template/**
*   the directory **attachments/**
*   the directory **images/**

Open the following URL in your browser:

`http://www.example.com/faq/install/update.php`

Choose your installed phpMyFAQ version and click the button of the update script, your version will automatically be updated. You have to update a lot of your template files due to our change using the Bootstrap framework.

The update script will move your template files to a folder **backup/** in the directory **assets/template/**. The default layout will be stored in the folder **assets/template/default/**.

Please copy the template file **assets/template/default/indexLogin.tpl** into your template folder.

[back to top][64]

* * *

**3.3. <a id="3.3"></a>Upgrading from phpMyFAQ 2.7.x**

Upgrading from 2.7.x is a major upgrade. Please make a full backup before you run the upgrade! First you have to delete all files **except**:

*   all files in the directory **config/**
*   all files in the directory **template/**
*   the directory **attachments/**
*   the directory **images/**

Open the following URL in your browser:

`http://www.example.com/faq/install/update.php`

Choose your installed phpMyFAQ version and click the button of the update script, your version will automatically be updated. You have to update a lot of your template files due to our change using the Bootstrap framework.

The update script will move your template files to a folder **backup/** in the directory **assets/template/**. The default layout will be stored in the folder **assets/template/default/**.

[back to top][64]

* * *

**3.4. <a id="3.4"></a>Upgrading phpMyFAQ 2.8.x**

Updating an existing phpMyFAQ 2.8.x installation is fairly simple. Via FTP copy all new files from the phpMyFAQ package **except**:

*   all files in the directory **config/**
*   all files in the directory **assets/template/**
*   the directory **attachments/**
*   the directory **images/**

Open the following URL in your browser:

`http://www.example.com/faq/install/update.php`

Choose your installed phpMyFAQ version and click the button of the update script, your version will automatically be updated.

You can find the changed files between the 2.8.x versions in the file *CHANGEDFILES*.

[back to top][64]

* * *

**3.5. <a id="3.5"></a>Modifying templates for phpMyFAQ 2.8.x**

Your current 2.5.x, 2.6.x and 2.7.x templates are **barely** compatible with phpMyFAQ 2.8 because we changed the complete CSS framework to Bootstrap. We're also using a lot of Ajax based technologies and CSS3 in the frontend now. We moved the login from a dropdown form to an own page with the login form. We also added a glossary page which you might know from the administration backend from older versions.

We recommend you'll take a look at the main [Bootstrap documentation](http://getbootstrap.com/). Please don't forget that the style sheets are written with [LESS](http://lesscss.org/). You have to compile the LESS files into CSS using a LESS compiler with node.js or a tool like [CodeKit](http://incident57.com/codekit/).

If you need help with theming phpMyFAQ please don't hesitate to ask in our [forum](http://forum.phpmyfaq.de/) or visit our [new theme page](http://www.phpmyfaq.de/themes). We will also release new themes from time to time on our homepage and release them as open source on our [Github page](https://github.com/phpMyFAQ/).

Note: The character set for all languages and templates is UTF-8. If you notice problems with e.g. German umlauts you have to convert your templates to UTF-8 encoding. Please use UNIX file endings \n instead of Windows file endings with \r\n.

[back to top][64]

* * *

**4. <a id="4"></a>Frontend**

The public phpMyFAQ frontend has a simple, HTML5/CCS3 based default layout based on [Bootstrap](http://getbootstrap.com/). The header has the main links for the all categories, instant response, add FAQs, add questions, open questions, sitemap and contact. On the left side you only see the main categories. You can also change the current language at the bottom of the FAQ, use the global search in the center of the FAQ or use the login box in the upper right if you have a valid user account. On the right side you see a list of the most popular FAQ records, the latest records, and the sticky FAQ records. On the pages with the FAQ records you'll see the other records of the current category and the tag cloud if you're using tagging.

[back to top][64]

* * *

**4.1. <a id="4.1"></a>Change languages**

As written above there's a select box for changing the current language. If you're visiting a phpMyFAQ powered FAQ, the current language will be the one you're browser is using or the language which was selected by the administrator of the FAQ. If you change the language you'll see the categories and records of your chosen language. If there are no entries in this language you'll see no entries. If you're switching to languages with right to left text direction (for example Arabic, Hebrew or Farsi) the whole default layout will be switching according to the text direction.

**Note:** phpMyFAQ uses a WYSIWYG online editor that has support for multiple languages. However, phpMyFAQ comes only with English language pack installed so changing the current language will not change the language of WYSIWYG editor. If you would like to have WYSIWYG editor in another language, just download the latest [language pack](http://tinymce.moxiecode.com/download_i18n.php), extract it and upload the extracted files to admin/editor directory under your phpMyFAQ's installation directory on your web server.

[back to top][64]

* * *

**4.2. <a id="4.2"></a>RSS Feeds**

On the start page you can subscribe to three RSS feeds with the news of the FAQ, the most popular FAQs, all records per requested category and the latest records available in the FAQ. On the page with the open user questions you can subscribe to the RSS feed with these questions. All feeds are valid and compatible to the RSS 2.0 specification.

[back to top][64]

* * *

**4.3. <a id="4.3"></a>Advanced search**

On the page with the advanced search you have more possibilities to find an entry. You can search over all language if you want to. It's also possible to search only in one selected category. Additionally the link for the OpenSearch plugin is below the main search box. At the bottom of the search box you'll see a list of the most popular search terms.

[back to top][64]

* * *

**4.4. <a id="4.4"></a>Instant Response**

The Instant Response is an Ajax-powered page with direct access to the whole FAQ database and the page will return the search results while you're typing into the input form. For performance reasons only the first 10 results will be displayed on the page.

[back to top][64]

* * *

**4.5. <a id="4.5"></a>Add FAQ**

On the *Add FAQ* page it's possible for all users to add a new FAQ record. The users have to add a FAQ question, select a category, add an answer, and they have to insert their name and e-mail address. If the spam protection is enabled they have to enter the correct captcha code, too. New FAQ entries won't be displayed and have to be activated by an administrator.

If an user is logged in, the name and e-mail address are filled automatically.

[back to top][64]

* * *

**4.6. <a id="4.6"></a>Add questions**

On the *Add question* page it's possible for all users to add a new question without an answer. If the question is submitted, phpMyFAQ checks the words for the question and will do a full text search on the database with the existing FAQs. If we found some matches the user will get some recommendations depending on the question he submitted.

The users have to add a question, select a category, and they have to insert their name and e-mail address. If the spam protection is enabled they have to enter the correct captcha code, too. By default new questions won't be displayed and have to be activated by an administrator.

If an user is logged in, the name and e-mail address are filled automatically.

[back to top][64]

* * *

**4.7. <a id="4.7"></a>Open questions**

This page displays all open questions and it's possible for all users to add an answer for this question. The user will be directed to the [add FAQ][33] page. If the spam protection is enabled they have to enter the correct captcha code, too.

[back to top][64]

* * *

**4.8. <a id="4.8"></a>Submit translation**

On every FAQ record page there's a select box for languages and a button to translate an existing FAQ record to another language. The translating user will be directed to a special [add content][33] page with the original entry and a stripped down WYSIWYG editor component. If the spam protection is enabled they have to enter the correct captcha code, too.

If an user is logged in, the name and e-mail address are filled automatically.

Note: Please do not forget to add also a translated category, otherwise you won't be able to activate the translated entry.

[back to top][64]

* * *

**4.9. <a id="4.9"></a>Social networks**

On every FAQ page there are a direct links to various social networks to share the current FAQ page. The supported social networks are:

*   [Facebook](http://www.facebook.com)
*   [Twitter](http://www.twitter.com)
*   [delicious](http://www.delicious.com/)
*   [digg.com](http://www.digg.com)

You can also click on a Facebook Like button or send the FAQ page URL up to 5 friends by e-mail. A PDF export and a button to print the FAQ is also available.

[back to top][64]

* * *

**4.10. <a id="4.10"></a>Internal references**

For better usability there are some helpful links below every FAQ record. If the administrator added tags to the records they will be displayed next to five (or more) related articles. The related articles are based on the content of the current FAQ entry. On the right side you'll see links to all entries of the current category and the complete tag cloud of the whole FAQ.

[back to top][64]

* * *

**4.11. <a id="4.11"></a>Public user registration**

On the upper right border of the default layout the users of the FAQ also have the possibility to register themselves. The user generated accounts are unactivated by default and the administrator has to activate them.

[back to top][64]

* * *

**4.12. <a id="4.13"></a>Complete secured FAQ**

If enabled by the administrator a phpMyFAQ installation can be completely secured. This means all content is only available after a successful login. For RSS feeds we provide a simple HTTP auth logic within the feeds to access for registered users. To avoid crawled content on search engines you should change the meta tags in index.tpl file from *INDEX, FOLLOW* to *NOINDEX, NOFOLLOW*.

[back to top][64]

* * *

**5. <a id="5"></a>Administration**

The administration of phpMyFAQ is completely browser-based. The admin area can be found under this URL:

`http://www.example.com/faq/admin/index.php`

You can also login in the public frontend and after the successful login you'll see a link to administration backend, too.

If you have lost your password you can reset it. A new random password will be generated and sent to you via e-mail. Please change it after your successful login with the generated password.

After entering your username and password you can log into the system. On the start page you can see the administration menu on the top, some statistics about visits, entries, news and comments in the middle of the page. At the bottom of the main admin page you'll see a button for version information. If you click on that button your version of phpMyFAQ will check the latest version number from our website phpmyfaq.de. We do not log anything in this process! A second button is our online verification service. Then clicking on the button phpMyFAQ calculates a SHA-1 hash for all files and checks it against a web service provided on phpmyfaq.de. With this service it's possible to see if someone changed files.

You can switch the current language in the administration backend and you have an info box about the session timeout.

[back to top][64]

* * *

**5.1. <a id="5.1"></a>User Administration**

phpMyFAQ offers a flexible management of privileges (or rights) for different users in the admin area. To search for a certain user just start typing the username in the input form and you'll get a list of hits for usernames. It is possible to assign different privileges to real people (represented by the term users). Those privileges are very detailed and specific, so that you could allow a certain user to edit but not to delete an entry. It is very important to contemplate which user shall get which privileges. You could edit an entry by completely deleting all content, which would be equivalent to deleting the whole entry. The number of possible users is not limited by phpMyFAQ.

Keep in mind that new users have no privileges at all, you will have to assign them by editing the user's profile.

A user without any permission in the admin section can still gets read access to categories and records. You can set the permissions on categories and records in the category and record administration frontends.

[back to top][64]

* * *

**5.2. <a id="5.2"></a>Group Administration**

phpMyFAQ also offers a flexible management of privileges (or rights) for different groups in the admin area. You can set permissions for groups in the same way like for users described in the topic above.

Please note that the permissions for a group are higher rated than the permissions on a user. To enable the group permissions, please set the permission level from *basic* to *medium* in the main configuration.

[back to top][64]

* * *

**5.3. <a id="5.3"></a>Category Administration**

phpMyFAQ lets you create different categories and nested sub-categories for your FAQ. You can also re-arrange your categories in a different order. It is possible to use various languages per category, too; there's also a frontend about all translated categories. For accessibility reasons you should add a small description for every category. If you add a new category, you can set the permissions for users and groups, and you can bind an administrator to that category. This is quite nice if you want to share the work in your FAQ between various admin users.

[back to top][64]

* * *

**5.4. <a id="5.4"></a>FAQ Administration**

You can create entries directly in the admin area. Created entries are NOT published by default. All available FAQs are listed on the page "Edit FAQs". By clicking on them the same interface that lets you create records will open up, this time with all the relevant data of the specific entry. The meaning of the fields is as follows:

*   **Category**
    
    The place in the FAQ hierarchy where this entry will be published depends on these settings. You can choose one or more categories where to store the entry. If you want to add a FAQ record to more than one category you have to select the categories with your mouse and press the CTRL key.
*   **Question**
    
    This is the question or headline of your entry.
*   **Answer**
    
    The content is an answer to the question or a solution for a problem. The content can be edited with the included WYSIWYG (**W**hat **Y**ou **S**ee **I**s **W**hat **Y**ou **G**et) editor when JavaScript is enabled. You can place images where you want with the integrated image manager. The Editor can be disabled in the configuration if you want.
    
*   **Language**
    
    You can select the language of your FAQ. By default the selected language saved in the configuration will be chosen. You can create entries in multiple languages like this: Write an article in English (or any other language) and save it. Now choose *Edit FAQs* and edit your English FAQ record. Change the question, answer and keywords and change language to, let's say Brazilian Portuguese. *Save* the FAQ record. Now you can, when you click *edit records*, see both FAQs in your list, having the same id, yet different languages.
    
*	 **Attachments**
	 
	 You can add attachments like PDFs or any other binary data using the **Add attachment** button. If you click on the button, a popup opens and you can upload an attachment. Please keep in mind that the PHP configuration about upload size will be checked.
	 
*   **Keywords**
    
    Keywords are relevant for searching through the database. In case you didn't include a specific word in the FAQ itself, but it is closely related to the content you may wish to include it as a keyword, so the FAQ will come up as a search result. It is also possible to use non-related keywords so that a wrongly entered search will also lead to the right results.
    
*   **Tags**
    
    You can add some tags about the current FAQ here. An Ajax-based auto-completion method helps you while typing your tags.
    
*   **Author**

    It is possible to specify an author for your FAQ.
    
*   **Email**

    It is possible to specify the author's email for your FAQ, but the email address won't be shown in the frontend.
    
*   **Solution ID**
    
    Every FAQ generates automatically a so-called solution ID. All records can be accessed directly by putting this ID into the search box.
    
*   **Active?**
    
    If a FAQ is "active" it is visible in the public area and will be included in searches. Is it "deactivated" it will be invisible. Suggested FAQs are deactivated by default to prevent any abuse.
*   **Sticky?**

    If a FAQ is "sticky" it is a very important FAQ record and will be shown always on all pages on the right column. You should mark records as sticky if they are very important for your whole FAQ. Sticky records also appear at the top positions of the lists of FAQ entries.
    
*   **Comments?**
    
    If you do not want to allow public comments for this FAQ you can disable the feature here.
*   **Revision**
    
    Like a wiki, phpMyFAQ supports revisions of every entry. New revisions won't be created automatically but you can create a new one if you click on "yes". The old revision will be stored in the database and the new current revision will be displayed in the public frontend. You can also bring back old revisions into the frontend if you select an old revision and save them as a new one.
    
*   **Date**

    You have three options for the FAQ creation date. You can choose to refresh the date of the FAQ entry for every update, or you can keep the date, or you can set an individual date for the FAQ entry.
    
*   **Permissions**

    If you add or edit a new entry, you can set the permissions for users and groups. Please note that the permissions of the chosen category override the permissions of the FAQ itself.
    
*   **Record expiration time window**

    If you need to you can set a time frame from one date to a second date when the FAQ entry should be valid and visible. Before and after this time frame the entry isn't visible and cannot be accessed.
    
*   **Date**

    Date of the last change.
    
*   **Changed?**

    This field is reserved for comments that can reflect what changes have been applied to a certain entry. This helps multiple admins to keep track of what happened to the entry over time. Any information entered here will remain invisible in the public area.
    
*   **Changelog**

    The changelog lists all previous changes, including user and date of change.

You can edit and delete all records as well. Please note that old revisions won't be deleted until the whole FAQ is deleted.

phpMyFAQ lets visitors contribute to the FAQ by asking questions. Every visitor is able to view these open questions in the public area, and may give an answer. If you wish to get rid of open questions you can do so using this section. Alternatively you can take over a question and answer it yourself and hereby add it to the FAQ.

[back to top][64]

* * *

**5.5. <a id="5.5"></a>Comment Administration**

In this straight frontend you can see all comments that have been posted in the FAQs and the news. You cannot edit comments but you can delete them with one easy click.

[back to top][64]

* * *

**5.6. <a id="5.6"></a>Glossary**

A glossary is a list of terms in a particular domain of knowledge with the definitions for those terms. You can add, edit and delete glossary items here. The items will be automatically displayed in <abbr> tags in the frontend.

[back to top][64]

* * *

**5.7. <a id="5.7"></a>News Administration**

phpMyFAQ offers the ability to post news on the starting page of your FAQ. In the administration area you can create new items, edit existing news or delete them.

[back to top][64]

* * *

**5.8. <a id="5.8"></a>Attachment Administration**

In the attachment administration you can see an overview of all all attachments with their filename, file size, language and MIME type. You can delete them, too.

[back to top][64]

* * *

**5.9. <a id="5.9"></a>Statistics**

Below every entry visitors have the chance to rate the overall quality of a FAQ by giving ratings from 1 to 5 (whereas 1 is the worst, 5 the best rating). In the statistics the average rating and number of votes becomes visible for every rated entry. To give you a quick overview entries with an average rating of 2 or worse are displayed in red, an average above 4 results in a green number.

**View Sessions**
This functions lets you keep track of your visitors. Every visitor is assigned an ID when coming to your starting page, that identifies him during his whole visit. Using the information gathered here you could reconstruct the way visitors use your FAQ and make necessary adjustments to your categories, content or keywords.

**View Adminlog**
The adminlog allows you to track any actions taken by users in the admin area of phpMyFAQ. If you feel you have an intruder in the system you can find out for sure by checking the admin log.

**Search statistics**
On the search statistics page you'll get a report about which keywords and how often your users are searching. This information is split into keywords, the number of searches for this term, the language and the overall percentage.

**Reports**
On the reports page you can select various data columns to generate a report about content and usage of your FAQ installation. You can export the report then a CSV file.

[back to top][64]

* * *

**5.10. <a id="5.10"></a>Exports**

You can export your contents of your whole FAQ or just some selected categories into four formats:

*   a XML file
*   a plain XHTML file
*   a PDF file with a table of contents

[back to top][64]

* * *

**5.11. <a id="5.11"></a>Backup**

Using the backup function it is possible to create a copy of the database to a single file. This makes it possible to restore the FAQ after a possible "crash" or to move the FAQ from one server to another. It is recommended to make regular backups of your FAQ.

*   **backup data**
    A backup of all **data** will include all entries, users, comments, etc.
*   **backup logs**
    The sessions of visits and the adminlog will be saved (i.e. all **log** files). This information is not necessary for running phpMyFAQ, they serve only statistical purposes.

[back to top][64]

* * *

**5.12. <a id="5.12"></a>Configuration**

*   **Main FAQ configuration**
    Here you can edit the general, the record, spam protection, search and social networks settings of phpMyFAQ. Should you want to enter multiple email addresses in the configuration separate them by a comma (",").
*   **FAQ Multi-sites**
    You can see a list of all multisite installations and you're able to add new ones.
*   **Stop Words configuration**
    We need stop words for the smart answering feature. If an user is adding a new question to your FAQ the words will be checked against all FAQs in your database but without the stop words. Stop words are words with a very low relevance like the English word "the".
*   **Interface translation**
    With this interface it is possible to edit all available translations of phpMyFAQ. You only can edit translations when the language file is writable. The interface checks that for you. If you like you can also send your improved translation to the phpMyFAQ Team. You cannot change the English translation because this is the main language file.

[back to top][64]

* * *

**5.13. <a id="5.13"></a>Multisite Configuration**

*   In order to host several distinct installations (with different configs, different templates and most importantly, different database credentials), but only want to update once, you need to follow these steps:
    *   Make sure you have the *multisite/* directory in your document root and *multisite.php* in it
    *   For every separate installation there needs to be a subdirectory of *multisite/* named exactly like the hostname of the separate installation.
        For example, if you want to use *faq.example.org* and *beta.faq.example.org*, it needs to look like this:
        
            .
            |-- [...]
            |-- config
            |   |-- constants_ldap.php
            |   |-- constants.php
            |   `-- database.php
            `-- multisite
                |-- multisite.php
                `-- beta.faq.example.org
                    |-- constants_ldap.php
                    |-- constants.php
                    `-- database.php


[back to top][64]

* * *

**6. <a id="6"></a>Customizing phpMyFAQ 2.8.x**

In phpMyFAQ code and layout are separated. The layout is based on several template files, that you can modify to suit your own needs. The most important files for phpMyFAQ's default layout can be found in the directory *assets/template/default/*. All original templates are valid HTML5 and we don't use tables for layout reasons.

**Note:** You can change the layout of the admin area using the CSS file *admin/css/style.css*.

[back to top][64]

* * *

**6.1. <a id="6.1"></a>The file assets/template/default/index.tpl**

The default layout of phpMyFAQ is saved in the **index.tpl** file. This is a normal HTML5 file including some variables in curly brackets, serving as placeholders for content.

Example:

`<span class="useronline">{userOnline}</span>`

The template-parser of the FAQ converts the placeholder *{userOnline}* to the actual number of visitors online.

You can change the template as you wish, but you may want to keep the original template in case something goes wrong.

[back to top][64]

* * *

**6.2. <a id="6.2"></a>The file assets/template/default/css/style.css**

All formatting such as fonts and the like can be modified in the CSS-file **style.css** for left-to-right languages and in **style.rtl.css** for left-to-right languages.

[back to top][64]

* * *

**6.3. <a id="6.3"></a>More Templates**

You need an other template design or more HTML5/CSS3 features? Then write us an email and we can talk about it.

[back to top][64]

* * *

**7. <a id="7"></a>Customizing phpMyFAQ**

phpMyFAQ users have even more customization opportunities. The key feature is the user selectable template sets, there is a templates/default directory where the default layouts get shipped.

[back to top][64]

* * *

**7.1. <a id="7.1"></a>Creating a custom layout**

Follow these steps to create a custom template set:

*   copy the directory assets/templates/default to assets/templates/<custom\_template\_set>
*   adjust template files in assets/templates/<custom\_template\_set> to fit your needs
*   activate <custom\_template\_set> within Admin->Config->Main

**Note:** There is a magic variable *{tplSetName}* containing the name of the actual layout available in each template file.

[back to top][64]

* * *

**8. <a id="8"></a>Developer documentation**

This part of documentation is for developers who want to contribute to phpMyFAQ.

* * *

**8.1. <a id="8.1"></a>phpMyFAQ development**

phpMyFAQ is developed using PHP and JavaScript.


[back to top][64]

* * *

**8.2. <a id="8.2"></a>rest/json API**

Beginning with version 2.6 phpMyFAQ will offer more and more interfaces to access phpMyFAQ installations with other clients like the iPhone. phpMyFAQ includes a rest/json interface and offers an API for various services like fetching the phpMyFAQ version and the phpMyFAQ API version. Currently we implemented an interface for the search, the possibility to fetch all categories, all FAQ entries for a selected category and a FAQ entry.

You can call the resources with the following URIs:

*   **getVersion()**
    This method returns the phpMyFAQ version number as string.

    *   http://www.example.org/phpmyfaq/api.php?action=getVersion (standard)
    *   http://www.example.org/phpmyfaq/api/getVersion (rewrite rules enabled)

    The result will be a string value like this:

    `
        {"version":"2.8.0"}
        `
*   **getApiVersion()**
    *   http://www.example.org/phpmyfaq/api.php?action=getApiVersion (standard)
    *   http://www.example.org/phpmyfaq/api/getApiVersion (rewrite rules enabled)

    Returns the version of the API as an integer value. The version number is incremental and will be incremented every time the API changes.

    `
        {"apiVersion":1}
        `
*   **search()**
    *   http://www.example.org/phpmyfaq/api.php?action=search&lang=en&q=phpMyFAQ (standard)
    *   http://www.example.org/phpmyfaq/api/search/en/phpMyFAQ (rewrite rules enabled)

    You have two variables, *lang* for the language and *q* for the search term. You'll get an JSON object as result with the follwing structure:

    `
    [
        {
        "id":"1",
        "lang":"en",
        "category_id":"15",
        "question":"Why are you using phpMyFAQ?",
        "answer":"Because it's cool!",
        "link":"http://faq.phpmyfaq.de/index.php?action=artikel&cat=15&id=1&artlang=en"
        },
        {
        "id":"13",
        "lang":"en",
        "category_id":"5",
        "question":"Why do you like phpMyFAQ?",
        "answer":"Because it's cool!",
        "link":"http://faq.phpmyfaq.de/index.php?action=artikel&cat=5&id=13&artlang=en"
        }
    ]
        `
*   **getCategories()**
    *   http://www.example.org/phpmyfaq/api.php?action=getCategories (standard)
    *   http://www.example.org/phpmyfaq/api/getCategories (rewrite rules enabled)

    The result will be a JSON object like the following:

    `
    [
        {
        "id":"1",
        "lang":"en",
        "parent_id":"0",
        "name":"phpMyFAQ 2.6",
        "description":"Everything about phpMyFAQ 2.6",
        "user_id":"1",
        "level":0
        },
        {
        "id":"2",
        "lang":"en",
        "parent_id":"0",
        "name":"phpMyFAQ 2.8",
        "description":"Everything about phpMyFAQ 2.8",
        "user_id":"1",
        "level":0}
    ]
        `
*   **getFaqs()**
    *   http://www.example.org/phpmyfaq/api.php?action=getFaqs&lang=en&categoryId=1 (standard)
    *   http://www.example.org/phpmyfaq/api/getFaqs/en/1 (rewrite rules enabled)

    You have two variables, *lang* for the language and *categoryId* for the category id. You'll get an JSON object as result with the follwing structure:

    `
    [
        {
        "record_id":"1",
        "record_lang":"en",
        "category_id":"1",
        "record_title":"Is there life after death?",
        "record_preview":"Maybe!",
        "record_link":"\/phpmyfaq\/phpmyfaq\/index.php?action=artikel&cat=1&id=1&artlang=en",
        "record_date":"20091010175452",
        "visits":"3"
        },
        {"record_id":"2",
        "record_lang":"en",
        "category_id":"1",
        "record_title":"How can I survive without phpMyFAQ?",
        "record_preview":"It\'s easy!",
        "record_link":"\/phpmyfaq\/phpmyfaq\/index.php?action=artikel&cat=1&id=2&artlang=en",
        "record_date":"20091014181500",
        "visits":"10"
        }
    ]
        `
*   **getFaq()**
    *   http://www.example.org/phpmyfaq/api.php?action=getFaq&lang=en&recordId=1 (standard)
    *   http://www.example.org/phpmyfaq/api/getFaq/en/1 (rewrite rules enabled)

    You have two variables, *lang* for the language and *recordId* for the record id. You'll get an JSON object as result with the follwing structure:

    `
        {
        "id":"1",
        "lang":"en",
        "solution_id":"1000",
        "revision_id":"0",
        "active":"yes",
        "sticky":"0",
        "keywords":"",
        "title":"Is there life after death?",
        "content":"Maybe!",
        "author":"Thorsten Rinne",
        "email":"thorsten@phpmyfaq.de",
        "comment":"y",
        "date":"2009-10-10 17:54",
        "dateStart":"00000000000000",
        "dateEnd":"99991231235959",
        "linkState":"",
        "linkCheckDate":"0"
        }
        `

[back to top][64]

* * *

**9. <a id="9.1"></a>One more thing**

Thank you for using phpMyFAQ! :-)

Author: [Thorsten Rinne][88]

Co-Authors: [Stephan Hochhaus][89], [Markus Gläser][90]

Date: 2013-02-13

© 2001-2013 phpMyFAQ Team

This documentation is licensed under a [Creative Commons License](http://creativecommons.org/licenses/by/2.0/).

[back to top][64]

 [1]: #1
 [2]: #1.1
 [3]: #1.2
 [4]: #1.3
 [5]: #2
 [6]: #2.1
 [7]: #2.2
 [8]: #2.3
 [9]: #2.4
 [10]: #2.5
 [11]: #2.6
 [12]: #2.7
 [13]: #2.8
 [14]: #2.9
 [15]: #2.10
 [16]: #2.11
 [17]: #2.12
 [18]: #2.13
 [19]: #2.14
 [20]: #2.15
 [21]: #2.16
 [22]: #3
 [23]: #3.1
 [24]: #3.2
 [25]: #3.3
 [26]: #3.4
 [27]: #3.5
 [28]: #4
 [29]: #4.1
 [30]: #4.2
 [31]: #4.3
 [32]: #4.4
 [33]: #4.5
 [34]: #4.6
 [35]: #4.7
 [36]: #4.8
 [37]: #4.9
 [38]: #4.10
 [39]: #4.11
 [40]: #4.12
 [41]: #5
 [42]: #5.1
 [43]: #5.2
 [44]: #5.3
 [45]: #5.4
 [46]: #5.5
 [47]: #5.6
 [48]: #5.7
 [49]: #5.8
 [50]: #5.9
 [51]: #5.10
 [52]: #5.11
 [53]: #5.12
 [54]: #5.13
 [55]: #6
 [56]: #6.1
 [57]: #6.2
 [58]: #6.3
 [59]: #7
 [60]: #7.1
 [61]: #8
 [62]: #8.2
 [63]: #9
 [64]: #top
 [88]: mailto:thorsten AT phpmyfaq DOT de
 [89]: mailto:stephan AT yauh DOT de
 [90]: mailto:mgl-mail AT t-online DOT de
 [91]: #8.1