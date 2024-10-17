# 3. Update

First, please download the latest package of phpMyFAQ. Upgrading to phpMyFAQ 3.2.x is possible from the following
versions:

- phpMyFAQ 3.0.x
- phpMyFAQ 3.1.x
- phpMyFAQ 3.2.x

If you're running an older version of phpMyFAQ than listed above, we recommend a new and fresh installation. If you need
support for updating an old FAQ from the 1.x or 2.x series, [please email us](thorsten_AT_phpmyfaq_DOT_de).

## Before you upgrade

Please make sure that you're running at least PHP 8.1, otherwise the upgrade won't work.

## Upgrading from phpMyFAQ 3.0.x

Upgrading from 3.0.x is a major upgrade.
Please make a full backup before you run the upgrade!
First, log in as admin into the admin section and enable the maintenance mode.
Second, you have to delete all files **except**:

- in the directory config/
  - keep the file **database.php**
  - only if using LDAP/ActiveDirectory support also keep the file **ldap.php**
- the directory attachments/
- the directory data/
- the directory images/

Download the latest phpMyFAQ package and copy the contents into your existing FAQ directory, then open the following
URL in your browser:

`http://www.example.com/faq/setup/update.php`

Choose your installed phpMyFAQ version and click the button of the update script, your version will automatically be
updated.

## Upgrading from phpMyFAQ 3.1.x

Updating an existing phpMyFAQ 3.1.x installation is fairly simple. First, log in as admin into the admin section and
enable the maintenance mode. Second, you have to delete all files **except**:

- all files in the directory **config/**
- the directory **attachments/**
- the directory **data/**
- the directory **images/**

Download the latest phpMyFAQ package and copy the contents into your existing FAQ directory, then open the following
URL in your browser:

`http://www.example.com/faq/setup/update.php`

Choose your installed phpMyFAQ version and click the button of the update script, your version will automatically be
updated.

## Upgrading from phpMyFAQ 3.2.x

Updating an existing phpMyFAQ 3.2.x installation is fairly simple. First, log in as admin into the admin section and
enable the maintenance mode. Second, you have to delete all files **except**:

- all files in the directory **config/**
- all files in the directory **assets/themes/**
- the directory **attachments/**
- the directory **data/**
- the directory **images/**

Download the latest phpMyFAQ package and copy the contents into your existing FAQ directory, then open the following
URL in your browser:

`http://www.example.com/faq/setup/update.php`

Choose your installed phpMyFAQ version and click the button of the update script, your version will automatically be
updated.

## Modifying templates for phpMyFAQ 3.2

We recommend you take a look at the main [Bootstrap documentation](https://getbootstrap.com/).
Please remember that the style sheets are written with [SCSS](https://sass-lang.com/).
You have to compile the SCSS files into CSS using a SCSS compiler with Node.js.

If you need help with theming phpMyFAQ, please don't hesitate to ask in our [forum](https://forum.phpmyfaq.de/).

Note: The character set for all languages and templates is UTF-8. If you notice problems with e.g. German umlauts, you
have to convert your templates to UTF-8 encoding. Please use UNIX file endings \n instead of Windows file endings with
\r\n.
