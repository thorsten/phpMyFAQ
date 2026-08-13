# 5. Updating phpMyFAQ

First, please download the latest package of phpMyFAQ. Upgrading to phpMyFAQ 4.2 is possible from the following
versions:

- phpMyFAQ 3.1.x
- phpMyFAQ 3.2.x
- phpMyFAQ 4.0.x
- phpMyFAQ 4.1.x

If you're running an older version of phpMyFAQ than listed above, we recommend a new and fresh installation. If you need
support for updating an old FAQ from the 1.x, 2.x, or 3.0 series, [please email us](mailto:thorsten_AT_phpmyfaq_DOT_de).

Please note that the requirements of phpMyFAQ have to be fulfilled.

## Before you upgrade

Please make sure that you're running at least PHP 8.4, otherwise the upgrade won't work.

## The update token

The update wizard changes your database and your configuration, so it only runs for people who are allowed to do that.
If you are still logged in as an administrator when you open the update wizard, nothing changes for you: the wizard
starts right away.

If you are not logged in — which happens on major upgrades, where the old database doesn't match what the new code
expects, so a login is impossible — the wizard asks for an update token instead. phpMyFAQ writes that token into the
file `content/core/config/update-token.php` on your server. Open that file with your FTP client or on the console,
copy the token from it, and paste it into the update wizard. The token is valid for one hour, reload the update page to
get a new one, and phpMyFAQ deletes it once the database has been updated. The file cannot be read over HTTP.

If phpMyFAQ can't create the token file, make the directory `content/core/config/` writable for your web server.

## Upgrading from phpMyFAQ 3.1.x

Upgrading from 3.1.x is a major upgrade.
Your existing templates will not work with phpMyFAQ 4.2.
Please make a full backup before you run the upgrade!
First, log in as admin into the admin section and enable the maintenance mode.
(Configuration >> Edit Configuration >> Set FAQ in maintenance mode)
Second, you have to delete all files **except**:

- in the directory **config/**
    - keep the file **database.php**
    - only if using LDAP/ActiveDirectory support also keep the file **ldap.php**
- the directory **attachments/**
- the directory **data/**
- the directory **images/**

Download the latest phpMyFAQ package and copy the contents into your existing FAQ directory, then open the following
URL in your browser:

`http://www.example.com/faq/update`

Click the button of the update script, your version will automatically be updated.

## Upgrading from phpMyFAQ 3.2.x

Upgrading from 3.2.x is a major upgrade.
Your existing templates will not work with phpMyFAQ 4.2.
Please make a full backup before you run the upgrade!
First, log in as admin into the admin section and enable the maintenance mode.
(Configuration >> Edit Configuration >> Set FAQ in maintenance mode)
Second, you have to delete all files **except**:

- in the directory **config/**
    - keep the file **database.php**
    - only if using LDAP/ActiveDirectory support also keep the file **ldap.php**
    - only if using EntraID support also keep the file **azure.php**
- the directory **attachments/**
- the directory **data/**
- the directory **images/**

Download the latest phpMyFAQ package and copy the contents into your existing FAQ directory, then open the following
URL in your browser:

`http://www.example.com/faq/update`

Click the button of the update script, your version will automatically be updated.

## Upgrading from phpMyFAQ 4.0.x

### Manual upgrade

Please make a full backup before you run the upgrade!
First, log in as admin into the admin section and enable the maintenance mode.
(Configuration >> Edit Configuration >> Set FAQ in maintenance mode)
Second, you have to delete all files **except**:

- all files in the directory **content/**

Download the latest phpMyFAQ package and copy the contents into your existing FAQ directory, the open the following
URL in your browser:

`http://www.example.com/faq/update`

Click the button of the update script, your version will automatically be updated.

### Online update

If you're running phpMyFAQ 4.0 or later, you can use the built-in online update feature.
Log in as admin into the admin section and enable the maintenance mode.
(Configuration >> Edit Configuration >> Set FAQ in maintenance mode)
Then go to the "phpMyFAQ Update" page in the configuration section and click through the update wizard:

1. Check for System Health: this checks if your system is ready for the upgrade
2. Check for Updates: this checks if there is a new version of phpMyFAQ available
3. Download of phpMyFAQ: this downloads the latest version of phpMyFAQ in the background, this can take some seconds
4. Extracting phpMyFAQ: this extracts the downloaded archive, this can take a while
5. Install downloaded package: first, it creates a backup of your current installation, then it copies the downloaded
   files into your installation, and in the end, the database is updated

Note:
The online update feature is experimental and might not work in all environments.

## Upgrading from phpMyFAQ 4.1.x

### Manual upgrade

Please make a full backup before you run the upgrade!
First, log in as admin into the admin section and enable the maintenance mode.
(Configuration >> Edit Configuration >> Set FAQ in maintenance mode)
Second, you have to delete all files **except**:

- all files in the directory **content/**

Download the latest phpMyFAQ package and copy the contents into your existing FAQ directory, the open the following
URL in your browser:

`http://www.example.com/faq/update`

Click the button of the update script, your version will automatically be updated.

### Online update

Log in as admin into the admin section and enable the maintenance mode.
(Configuration >> Edit Configuration >> Set FAQ in maintenance mode)
Then go to the "phpMyFAQ Update" page in the configuration section and click through the update wizard:

1. Check for System Health: this checks if your system is ready for the upgrade
2. Check for Updates: this checks if there is a new version of phpMyFAQ available
3. Download of phpMyFAQ: this downloads the latest version of phpMyFAQ in the background, this can take some seconds
4. Extracting phpMyFAQ: this extracts the downloaded archive, this can take a while
5. Install downloaded package: first, it creates a backup of your current installation, then it copies the downloaded
   files into your installation, and in the end, the database is updated

Note:
The online update feature is experimental and might not work in all environments.

## Upgrading from phpMyFAQ 4.2.x

### Manual upgrade

Please make a full backup before you run the upgrade!
First, log in as admin into the admin section and enable the maintenance mode.
(Configuration >> Edit Configuration >> Set FAQ in maintenance mode)
Second, you have to delete all files **except**:

- all files in the directory **content/**

Download the latest phpMyFAQ package and copy the contents into your existing FAQ directory, the open the following
URL in your browser:

`http://www.example.com/faq/update`

Click the button of the update script, your version will automatically be updated.

### Online update (Experimental feature)

If you're running phpMyFAQ 4.0.0 or later, you can use the built-in online update feature.
Log in as admin into the admin section and enable the maintenance mode.
(Configuration >> Edit Configuration >> Set FAQ in maintenance mode)
Then go to the "phpMyFAQ Update" page in the configuration section and click through the update wizard:

1. Check for System Health: this checks if your system is ready for the upgrade
2. Check for Updates: this checks if there is a new version of phpMyFAQ available
3. Download of phpMyFAQ: this downloads the latest version of phpMyFAQ in the background, this can take some seconds
4. Extracting phpMyFAQ: this extracts the downloaded archive, this can take a while
5. Install downloaded package: first, it creates a backup of your current installation, then it copies the downloaded
   files into your installation, and in the end, the database is updated

Note:
The online update feature is experimental and might not work in all environments.

### Permission changes in phpMyFAQ 4.2

The update separates reading, writing and publishing FAQs into independent rights, without changing what anybody can
currently do:

- The existing `View FAQs` (`view_faqs`) right starts being enforced for logged-in users. The update grants it to every
  existing user and group, and new accounts receive it automatically, so nothing becomes invisible. Anonymous visitors
  are not affected by the right at all.
- A new `Publish FAQs` (`faq_publish`) right decides whether an FAQ may be set live. The update grants it to every user
  and group that currently holds `Approve FAQs` (`approverec`), including their category and language restrictions, so
  everyone who can publish today can still publish afterwards.

Afterwards you can revoke `faq_publish` from editors who should be able to write but not publish. See section 5.1.5 of
the administration documentation for the details.

## Modifying templates for phpMyFAQ 4.2

We recommend you take a look at the main [Bootstrap documentation](https://getbootstrap.com/).
Please remember that the style sheets are written with [SCSS](https://sass-lang.com/).
You have to compile the SCSS files into CSS using a SCSS compiler with Node.js.

If you need help with theming phpMyFAQ, please don't hesitate to ask in our [forum](https://forum.phpmyfaq.de/).

Note: The character set for all languages and templates is UTF-8. If you notice problems with e.g., German umlauts, you
have to convert your templates to UTF-8 encoding. Please use UNIX file endings \n instead of Windows file endings with
\r\n.
