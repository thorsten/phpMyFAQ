# phpMyFAQ 3.1.9

**Codename "Poseidon"**

## CHANGELOG

This is a log of major user-visible changes in each phpMyFAQ release.

### phpMyFAQ v3.1.9 - 2022-11-

- fixed multiple security vulnerabilities (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v3.1.8 - 2022-10-24

- fixed multiple security vulnerabilities (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v3.1.7 - 2022-10-02

- fixed CSRF vulnerability (KhanhCM, Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v3.1.6 - 2022-07-23

- fixed XSS vulnerability (jhond0e, Thorsten)
- fixed dismiss error for cookie consent (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v3.1.5 - 2022-06-27

- added compatibility with Elasticsearch v8+ (Thorsten)
- added trust of self-signed certificates with MS SQL (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v3.1.4 - 2022-04-25

- added missing assets (Thorsten)

### phpMyFAQ v3.1.3 - 2022-04-24

- fixed login via LDAP (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v3.1.2 - 2022-03-16

- fixed minor bugs (Thorsten)
- updated bundled dependencies (Thorsten)

### phpMyFAQ v3.1.1 - 2022-02-14

- fixed enabled debug mode (Thorsten)
- updated bundled dependencies (Thorsten)

### phpMyFAQ v3.1.0 - 2022-02-12

- Happy 21st Birthday, phpMyFAQ!
- changed PHP requirement to PHP 7.4+ (Thorsten)
- added support for PHP 8.0 and PHP 8.1 (Thorsten)
- added support for Elasticsearch v6+ (Thorsten)
- added drag'n'drop sorting for main categories (Paolo Caparrelli, Thorsten)
- added possibility to add users without a password (Thorsten)
- added export of all users as CSV (Thorsten)
- added ChartJS as new charting library (Thorsten)
- added REST API v2.1 to register users and add FAQs (Thorsten)
- added API client tokens for REST API v2.1 (Thorsten)
- added opt-in for displaying user data (Thorsten)
- added mail notifications for new FAQs in admin section (Thorsten)
- added possibility to login via email address (Thorsten)
- updated to Bootstrap v4.6 (Thorsten)
- updated to Composer v2 and improved build (Alexander M. Turek, Thorsten)
- updated to Twitter API v2 (Thorsten)
- improved install and update scripts (Thorsten)
- removed REST API v1 (Thorsten)
- removed RSS support (Thorsten)
- removed rewrite support for IIS (Thorsten)
- removed password hashing with MD5 and SHA-1 (Thorsten)
- removed OpenSearch support (Thorsten)
- removed XML export (Thorsten)
- removed auto-save for FAQs during editing (Thorsten)
- removed obsolete DbUnit tests (Thorsten)
- fixed minor bugs (Nico Schmitz-Laux, Thorsten)

### phpMyFAQ v3.0.12 - 2022-01-22

- fixed broken LDAP authentication (Thorsten)
- updated bundled dependencies (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v3.0.11 - 2022-01-18

- fixed enabled debug mode (Thorsten)
- updated bundled dependencies (Thorsten)

### phpMyFAQ v3.0.10 - 2022-01-17

- fixed multiple XSS and CSRF vulnerabilities (0x7zed, M0rphling, justinp09010, Dennis Yassine, Thorsten)
- fixed many minor bugs (Thorsten)

### phpMyFAQ v3.0.9 - 2021-04-17

- fixed minor bugs (Thorsten)

### phpMyFAQ v3.0.8 - 2021-02-24

- updated to Bootstrap v4.6 (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v3.0.7 - 2020-12-23

- fixed XSS vulnerability (Curtis Robinson, Thorsten)
- added TOC plugin for TinyMCE (Thorsten)
- removed support for deprecated data-vocabulary.org schema (Thorsten)
- removed Travis CI build (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v3.0.6 - 2020-11-27

- added support for PHP 8.0
- fixed minor bugs (Thorsten)

### phpMyFAQ v3.0.5 - 2020-10-17

- minor improvements (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v3.0.4 - 2020-07-26

- session timeout extended to 5 hours (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v3.0.3 - 2020-05-21

- improved FAQ editing (Thorsten)
- updated to Bootstrap v4.5 (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v3.0.2 - 2020-04-16

- improved handling of multiple homepage categories (Thorsten)
- improved FAQ editing (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v3.0.1 - 2020-03-17

- re-added tag cloud on several pages (Thorsten)
- fixed minor bugs (Thorsten)
- fixed update issues for PostgreSQL (Thorsten)

### phpMyFAQ v3.0.0 - 2020-02-16

- changed PHP requirement to PHP 7.2+ (Thorsten)
- added PHP namespaces (Thorsten)
- added Docker support (Adrien Estanove)
- added experimental large permissions support with sections (Timo Wolf, Thorsten)
- added support for Elasticsearch v5+ (Thorsten)
- added LDAP configuration frontend (Thorsten)
- added configuration for enable/disable XML sitemap (Thorsten)
- added support for category images (Thorsten)
- added support for categories on homepage (Thorsten)
- added filter functionality in templates (Thorsten)
- added improved attachment overview (Thorsten)
- added HTML5 export (Thorsten)
- added support for EU General Data Protection Regulation (Jochen Steinhilber)
- added multiple attachment upload (Thorsten)
- added 404 page template (Thorsten)
- added Mongolian translation (khaidaw@gmail.com)
- added support for adding own meta content in templates (Thorsten)
- added new REST API v2 (includes login) (Thorsten)
- improved sticky records (Thorsten)
- improved brute force handling (Thorsten)
- switched CSS development from LESS to SCSS (Thorsten)
- template variable syntax compatible with Twig/Handlebars (Thorsten)
- updated Turkish translation (Can Kirca)
- updated bundled dependencies (Thorsten)
- deprecated RSS feeds (Thorsten)
- deprecated REST API v1 (Thorsten)
- removed translation admin frontend (Thorsten)
- removed XHTML export (Thorsten)
- removed support for ext/mssql (Thorsten)
- removed bundled Symfony ClassLoader (Thorsten)
- removed Bower, now using Yarn only (Thorsten)
- removed Grunt, now using Webpack (Thorsten)
- removed Modernizr (Thorsten)
- removed share on Facebook link (Thorsten)
- removed Facebook Like Button support (Thorsten)

### phpMyFAQ v2.9.13 - 2019-02-14

- fixed XSS vulnerabilities in the bundled Bootstrap version (Thorsten)

### phpMyFAQ v2.9.12 - 2019-02-12

- updated bundled Bootstrap to v3.4.0 (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.9.11 - 2018-09-02

- fixed multiple vulnerabilities (Thorsten)
- updated bundled jQuery to v1.12.4 (Thorsten)
- updated bundled Handlebars to v4.0.11 (Thorsten)

### phpMyFAQ v2.9.10 - 2018-02-17

- updated Dutch translation (https://github.com/joskevos)
- updated bundled SwiftMailer to v5.4.9 (Thorsten)
- updated bundled phpseclib to v2.0.9 (Thorsten)
- updated bundled TinyMCE to v4.6.7 (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.9.9 - 2017-10-19

- fixed multiple XSS and CSRF vulnerabilities (Thorsten)
- updated bundled Bootstrap to v3.3.7 (Thorsten)
- updated bundled Font Awesome to v4.7.0 (Thorsten)
- updated bundled TinyMCE to v4.5.7 (Thorsten)
- updated bundled HighlightJS to v9.12.0 (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.9.8 - 2017-07-12

- fixed improper restriction (Thorsten)
- add LDAP search in sub groups (Thorsten)
- updated French translation
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.9.7 - 2017-04-02

- fixed stored XSS vulnerability (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.9.6 - 2017-01-27

- fixed possible arbitrary PHP code execution (Thorsten)
- ready for PHP 7.1 (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.9.5 - 2016-08-31

- fixed minor bugs (Thorsten, Matt Brennan)

### phpMyFAQ v2.9.4 - 2016-08-02

- deactivated debug mode (Thorsten)
- updated bundled TinyMCE to v4.4.1 (Thorsten)
- updated bundled Bootstrap to v3.3.7 (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.9.3 - 2016-08-01

- fixed minor bugs (Thorsten)

### phpMyFAQ v2.9.2 - 2016-07-05

- updated bundled phpseclib to v2.0.2 (Thorsten)
- updated bundled TinyMCE to v4.4.0 (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.9.1 - 2016-05-31

- fixed stored XSS vulnerability (Kacper Szurek)
- added new source code paste plugin (Thorsten)
- removed American English translation (Thorsten)
- updated bundled TinyMCE to v4.3.12 (Thorsten)
- updated bundled Typeahead.js to v0.11.0 (Thorsten)
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.9.0 - 2016-05-13

- ready for PHP 7 (Thorsten)
- changed PHP requirement to PHP 5.5+ and PHP 7 (Thorsten)
- added support for HHVM 3.4.2+ (Thorsten)
- added support for Elasticsearch (Thorsten)
- added new mobile first, touch-friendly default layout (Thorsten)
- added tag intersection based search (Tomer Weinberg, Thorsten)
- added support for Markdown (Jerry van Kooten, Thorsten)
- added permissions for guests (Thorsten)
- added support for multiple LDAP/AD servers (Bernhard Müller, Thorsten)
- added experimental support for LDAP dynamic user binding (Thorsten)
- added frontend dependency management using Bower (Thorsten)
- added possibility to delete all logged search terms (Thorsten)
- added support for MySQL sockets
- added configuration for enable/disable highlighting search terms (Thorsten)
- added configuration for enable/disable RSS feeds (Thorsten)
- added configuration to enable/disable gzip compression (Thorsten)
- added configuration for inactive, hidden categories (Thorsten)
- added configuration to disable registration (Thorsten)
- added configuration to allow anonymous downloads (Thorsten)
- added configuration to override user passwords by admin (Thorsten)
- added configuration to reset voting and visits (Thorsten)
- added configuration to disable smart answering (Thorsten)
- added tag management frontend (Thorsten)
- added full support for SQLite3 (Peter Kehl)
- added control for meta robots handling (Thorsten)
- added configuration for auto-activation of new users (Christopher Andrews)
- added support for SMTP (Christopher Andrews)
- added bundled Twitter Typeahead.js (Thorsten)
- added support for custom headers and footers in PDF exports (Thorsten)
- added support for language specific open questions (Thorsten)
- added moderator groups to categories (Thorsten)
- added FAQ overview page (Thorsten)
- added JSON export (Thorsten)
- added private notes to FAQs (Thorsten)
- added experimental support for bcrypt (Thorsten)
- extended REST/JSON API (Thorsten)
- added simple bash based backup script (Thorsten)
- code base PSR-1 and PSR-2 compatible (Thorsten)
- updated bundled Symfony ClassLoader to v2.6.13 (Thorsten)
- updated bundled jQuery to v1.11.2 (Thorsten)
- updated bundled Bootstrap to v3.3.5 (Thorsten)
- updated bundled Font Awesome to v4.4.0 (Thorsten)
- updated bundled Modernizr to v2.8.3 (Thorsten)
- updated bundled TinyMCE to v4.2.7 (Thorsten)
- updated bundled SwiftMailer to v5.4.1 (Christopher Andrews, Thorsten)
- updated bundled Parsedown to v1.5.3 (Jerry van Kooten, Thorsten)
- updated bundled HighlightJS to v8.9.1 (Thorsten)
- updated bundled Elasticsearch Client to v2.1.5 (Thorsten)
- updated Russian translation
- removed bundled SyntaxHighlighter (Thorsten)
- dropped support for ext/mysql (Thorsten)
- dropped support for SQLite2 (Thorsten)
- dropped support for Zeus Webserver, IIS 6 and lighttpd (Thorsten)
- fixed a lot of minor bugs (Thorsten)

### phpMyFAQ v2.8.29 - 2016-05-31

- fixed stored XSS vulnerability (Kacper Szurek)

### phpMyFAQ v2.8.28 - 2016-05-13

- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.27 - 2016-04-11

- fixed CSRF security issue (High-Tech Bridge Security Research Lab)
- added possibility to use fullscreen videos (Thorsten)
- added compatibility with MySQL 5.7 (Thorsten)
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.26 - 2016-02-12

- 15 years of phpMyFAQ Edition
- dropped support for Internet Explorer 9 and 10 (Thorsten)
- updated Italian translation (Agnese Morettini)
- updated Norwegian Bokmål translation (Stian Svarholt)
- updated bundled Symfony ClassLoader to v2.6.13 (Thorsten)
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.25 - 2015-12-05

- ready for PHP 7 (Thorsten)
- added American English translation (Stewart Day)
- updated bundled Symfony ClassLoader to v2.6.11 (Thorsten)
- updated Japanese translation
- updated Brazilian Portuguese translation
- updated Italian translation (Amedeo Fragai)
- updated Dutch translation
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.24 - 2015-07-27

- updated Farsi translation (aysabzevar)
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.23 - 2015-06-13

- updated bundled Symfony ClassLoader to v2.6.9 (Thorsten)
- fixed "remember me" issues (Thorsten)
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.22 - 2015-03-31

- updated Czech translation
- updated Brazilian Portuguese translation
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.21 - 2015-02-28

- added experimental rewrite rules for IIS7 and IIS8 (Thorsten)
- improved usability in admin backend (Thorsten)
- improved HTML5 support in editor (Thorsten)
- improved code coverage (Thorsten)
- updated Brazilian Portuguese translation
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.20 - 2015-02-07

- added experimental support for HHVM 3.4.2+
- added experimental support for PHP 7.0
- updated Brazilian Portuguese translation
- updated French translation (Olivier Binet)
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.19 - 2014-12-31

- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.18 - 2014-11-30

- added clickjacking prevention (Thorsten, Narendra Bhati)
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.17 - 2014-11-05

- fixed typo in update script (Thorsten)

### phpMyFAQ v2.8-16 - 2014-11-03

- fixed restore from backup (Thorsten)
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.15 - 2014-10-02

- fixed broken installation (Thorsten)
- updated Farsi translation

### phpMyFAQ v2.8.14 - 2014-10-01

- fixed installation compatibility with MySQL 5.1 and MySQL 5.5 (Thorsten)
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.13 - 2014-09-16

- fixed multiple security vulnerabilities (Nikhil Srivastava, Thorsten)
- backported full support for SQLite3 (Peter Kehl)
- updated Chinese (Traditional) translation (Barlos Lee)
- updated Farsi translation
- updated Italian translation
- updated Spanish translation
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.12 - 2014-08-02

- updated Hebrew translation
- updated to Twitter OAuth v1.1 (Thorsten)
- updated bundled Symfony ClassLoader to v2.5.1 (Thorsten)
- fixed RSS/Atom feed compatibility (Thorsten)

### phpMyFAQ v2.8.11 - 2014-06-28

- updated German translation (Thorsten)
- updated Romanian translation
- updated Spanish translation
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.10 - 2014-05-30

- dropped support for Internet Explorer 7 and 8 (Thorsten)
- updated Brazilian Portuguese translation (Joao Tafarelo)
- updated bundled TinyMCE to v3.5.11 (Thorsten)
- updated bundled TCPDF library to v6.0.078 (Thorsten)
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.9 - 2014-04-28

- updated Thai translation
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.8 - 2014-03-18

- improved API security (Thorsten)
- fixed search with Hebrew characters (Thorsten)
- fixed PDF export with Czech and Slovak characters (Thorsten)
- fixed a lot of bugs (Thorsten)

### phpMyFAQ v2.8.7 - 2014-02-05

- fixed PHP 5.4 related issue introduced in 2.8.6 (Thorsten)

### phpMyFAQ v2.8.6 - 2014-02-04

- fixed IE8/9 only XSS and CSRF vulnerabilities (JPCERT, Thorsten)
- updated Hungarian translation
- updated Spanish translation (Luis Carvalho)
- updated bundled Symfony ClassLoader to v2.4.1 (Thorsten)
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.5 - 2013-12-31

- fixed SSO logins with mod_auth_kerb (Stephane Lapie)
- improved HTTPS handling (Thorsten)
- updated Dutch translation
- updated European Portuguese translation
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.4 - 2013-11-26

- fixed possible arbitrary PHP code execution (Thorsten)
- updated Chinese (Traditional) translation
- updated Dutch translation
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.3 - 2013-11-18

- fixed missing permission check on Image Manager (Thorsten)
- improved HTML / CSS code (Michael Meister)
- updated Brazilian Portuguese translation
- updated Japanese translation
- updated Spanish translation
- updated Swedish translation
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.2 - 2013-07-25

- added French Canada translation (Jacqueline Gazaille Tétreault)
- improved attachment upload dialog with HTML5 File API (Thorsten)
- updated Finnish translation (Pasi Pajukoski)
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.1 - 2013-06-26

- added support for Grunt (Thorsten)
- added minified CSS and JavaScript code (Thorsten)
- improved usability for group administration (Thorsten)
- fixed broken updates for MS SQL and SQLite (Thorsten)
- fixed IE8 related JavaScript issues (Thorsten)
- fixed HTML5 validation errors (Thorsten)
- fixed Arabic translation (Thorsten)
- fixed some minor bugs (Thorsten)

### phpMyFAQ v2.8.0 - 2013-05-21

- changed PHP requirement to PHP 5.3.3 and later (Thorsten)
- phpMyFAQ is now licensed under the terms of Mozilla Public License 2.0 (Thorsten, Florian)
- added new and improved frontend and backend user interface based on Twitter Bootstrap (Thorsten)
- added multi site support (Florian, Thorsten)
- added auto-save for FAQs during editing (Anatoliy)
- added improved advanced search in subcategories (Thorsten)
- added possibility to close and delete open questions (Peter Caesar)
- added possibility to delete multiple open questions (Thorsten)
- added user control panel (Thorsten)
- added support for Composer (Thorsten, Alexander M. Turek)
- added online verification check (Thorsten, Florian)
- added configurable maintenance mode (Thorsten)
- added user notification for answered questions (Thorsten)
- added possibility to deactivate complete FAQ export (Thorsten)
- added possibility to random sorting of FAQs (Thorsten)
- added documentation in Markdown with GitHub style HTML export (Thorsten)
- added support for Gravatar (Thorsten)
- added user statistic report in admin dashboard (Thorsten)
- improved usability of administration backend (Thorsten)
- improved setup and update (Thorsten, Florian)
- improved security with salted passwords (Thorsten)
- improved attachment functionality (Thorsten)
- improved CSS development with LESS (Thorsten)
- improved minified CSS output (Thorsten)
- simplified the link verification (Thorsten)
- dropped support for IBM DB2, Interbase/Firebird and Sybase (Thorsten)
- dropped support for PHP register_globals and magic_quotes_gpc (Thorsten)
- dropped support for Google Translate API v1 (Thorsten)
- removed Delicious support (Thorsten)
- updated bundled jQuery to v1.9.1 (Thorsten)
- updated bundled Modernizr to v2.6.2 (Thorsten)
- updated bundled Bootstrap to v2.3.2 (Thorsten)
- updated bundled TinyMCE to v3.5.7 (Thorsten)
- updated bundled Symfony ClassLoader to v2.2.1 (Alexander M. Turek)
- updated bundled Font Awesome to v3.1 (Thorsten)
- updated bundled jQuery Sparklines to v2.1.1 (Thorsten)
- updated PHP dependency management using Composer (Thorsten)
- updated Czech translation (Jaroslav Síka)
- updated Portuguese translation
- fixed some bugs (Thorsten, Florian, Alexander M. Turek)

### phpMyFAQ v2.7.9 - 2012-11-02

- updated Czech translation (Jaroslav Síka)
- updated Dutch translation
- updated French translation
- improved English translation
- fixed some bugs (Thorsten)

### phpMyFAQ v2.7.8 - 2012-08-22

- added experimental rewrite rules for Zeus Webserver (Chris Crawshaw)
- updated Arabic translation
- updated Dutch translation
- updated Spanish translation (Lisandro López Villatoro)
- improved LDAP handling (Thorsten)
- fixed some bugs (Thorsten)

### phpMyFAQ v2.7.7 - 2012-07-01

- added Bosnian translation (Alen Durmo)
- improved complete PDF export (Thorsten)
- updated Polish translation
- updated Simplified Chinese translation
- fixed Arabian and Bengali translation (Thorsten)
- fixed some bugs (Thorsten)

### phpMyFAQ v2.7.6 - 2012-05-16

- updated Dutch translation
- updated Polish translation
- updated Russian translation
- fixed some bugs (Thorsten)

### phpMyFAQ v2.7.5 - 2012-04-14

- fixed serious security issue in bundled ImageManager library (Thorsten)
- full support for Microsoft SQL Server Driver for PHP (Thorsten)
- added online verification check (Thorsten, Florian)
- added experimental support for SQLite3 (Thorsten)
- updated Spanish translation (Reynaldo Martinez P.)
- fixed Slovak translation (Thorsten)
- fixed IE9 related JavaScript issues (Thorsten)
- fixed some bugs (Thorsten)

### phpMyFAQ v2.7.4 - 2012-02-22

- added PDF export for complete FAQ in frontend (Thorsten)
- updated Japanese translation
- updated Russian translation (Alexander Melnik)
- updated Spanish translation
- fixed backup issue (Thorsten)
- fixed some bugs (Alexander Melnik, Thorsten, Aco Mitevski)

### phpMyFAQ v2.7.3 - 2012-01-16

- improved PDF export (Thorsten)
- updated Dutch translation (Almar van Pel)
- fixed some PostgreSQL related issues (Vince, Thorsten)
- fixed some attachment related issues (Thorsten)
- fixed some bugs (Thorsten)

### phpMyFAQ v2.7.2 - 2011-12-31

- improved PDF export and install script (Thorsten)
- updated Finnish translation (Petteri Hirvonen, Niklas Lampén)
- updated French translation (Cédric Frayssinet)
- updated Chinese (Simplified) translation
- updated bundled TCPDF library to v5.9.136 (Thorsten)
- updated bundled jQuery datePicker plugin (Thorsten)
- fixed some bugs (Thorsten)

### phpMyFAQ v2.7.1 - 2011-10-25

- fixed security issue in bundled ImageManager library (Thorsten)
- added missing translations (Thorsten)
- added configurable encryption type for passwords (Thorsten)
- added support for anonymous login for LDAP servers (Thorsten)
- added table of contents in PDF exports (Thorsten)
- fixed some bugs (Thorsten)

### phpMyFAQ v2.7.0 - 2011-09-30

- changed PHP requirement to PHP 5.2.3 (Thorsten)
- dropped support for MySQL 4.1 (Thorsten)
- dropped support for Internet Explorer 6 + 7 (Thorsten)
- dropped support for obsolete Microsummaries (Thorsten)
- added new HTML5/CSS3 powered layout and administration backend (Thorsten)
- added configurable search relevance functionality (Gustavo Solt)
- added automatic translations with Google Translate (Gustavo Solt)
- added Twitter support (Thorsten, Thomas Zeithaml)
- added Facebook Like Button support (Thorsten)
- added "Bookmark on Delicious" support (Thorsten)
- added attachment administration frontend (Anatoliy)
- added basic authentication support for LDAP groups (Thorsten)
- added basic support for HTML5 microdata (Thorsten)
- added functionality of the TinyMCE save button (Gustavo Solt)
- added basic reporting functionality (Gustavo Solt, Thorsten)
- added IPv6 support (David Soria Parra)
- added support for Single Sign On authentication (Thorsten)
- added support for complete secured FAQ installations (Thorsten)
- added user configurable date formatting (Thorsten)
- added possibility to delete user generated search terms (Thorsten)
- improved usability in frontend and administration backend (Thorsten)
- enabling Gzip compression by default (Thorsten, Lorenzo Milesi)
- updated bundled jQuery to v1.6.2 (Thorsten)
- updated bundled Modernizr to v2.0 (Thorsten)
- updated bundled TinyMCE to v3.4.2 (Thorsten)
- updated bundled SyntaxHighlighter to v3.0.83 (Thorsten)
- updated bundled TCPDF library to v5.9.110 (Thorsten)
- updated Dutch translation (ronald AT proudsites DOT com)
- updated German translation (Thorsten)
- updated Japanese translation (hiromi-suzuki AT garage DOT co DOT jp)
- updated Portuguese translation (Fernando G. Monteiro)
- updated Russian translation (cyber-01 AT yandex DOT ru)
- updated Spanish translation (Jason)
- fixed a lot of bugs (Thorsten)

### phpMyFAQ v2.6.18 - 2011-09-28

- updated Spanish translation
- fixed Danish translation
- fixed minor bugs (Thorsten, Matthew Robinson)

### phpMyFAQ v2.6.17 - 2011-06-08

- updated Portuguese translation
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.6.16 - 2011-05-31

- updated Portuguese translation
- updated Dutch translation
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.6.15 - 2011-02-23

- added Malay translation (Ahmad Kamil Zailani)
- updated Danish translation
- updated Brazilian Portuguese translation
- updated Spanish translation (Martin Schenk)
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.6.14 - 2011-01-24

- added rewrite rules for nginx (Florian Anderiasch)
- added compatibility for MySQL 5.5 with ext/mysql (Thorsten)
- added support to ban IPs from user tracking files (Thorsten)
- improved restore functionality (Thorsten)
- updated Dutch translation (Hans)
- updated German translation (Thorsten)
- Fixed issue with sending mails to category administrators (Gustavo Solt)
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.6.13 - 2010-12-15

- clean package after compromised server (Thorsten)

### phpMyFAQ v2.6.12 - 2010-12-13

- added experimental support for MariaDB (Thorsten)
- added empty LDAP configuration file as example (Thorsten)
- updated Arabic translation
- updated Dutch translation (Werner Helmich)
- updated French translation
- updated Norwegian Bokmål translation (harald@delelisten.no)
- fixed minor bugs (Thorsten, Gustavo Solt)

### phpMyFAQ v2.6.11 - 2010-11-03

- fixed some bugs introduced with phpMyFAQ 2.6.10 (Thorsten)

### phpMyFAQ v2.6.10 - 2010-11-02

- added several security enhancements (Thorsten)
- updated Chinese (Simplified) translation
- updated German translation (Thorsten)
- updated Portuguese translation (Fernando G. Monteiro)
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.6.9 - 2010-09-28

- fixed XSS vulnerability (Yam Mesicka, Thorsten)
- added Slovak translation (Tibor)
- added minor usability improvement in administration backend (Thorsten)
- fixed issue with embedded Google Analytics code (Thorsten)

### phpMyFAQ v2.6.8 - 2010-08-31

- dropped Oracle database support (Thorsten)
- added new TinyMCE plugin for adding internal links by a suggest search (Thorsten)
- added basic HTML5 powered administration backend (Thorsten)
- improved setup and update functionality (Thorsten, Bram)
- updated bundled TCPDF library to v5.6.000 (Thorsten)
- updated bundled TinyMCE editor component to v3.3.8 (Thorsten)
- updated bundled phpseclib to v0.2.1 (Thorsten)
- updated Russian translation
- updated Portuguese translation
- fixed XML rendering issue (Thorsten)
- fixed many bugs (Thorsten, Bram)

### phpMyFAQ v2.6.7 - 2010-07-29

- refactored search backend (Thorsten)
- improved pagination functionality (Thorsten)
- fixed various problems with Microsoft SQL Server Driver for PHP (Thorsten)
- fixed minor bugs (Thorsten, Aaron Burgie)

### phpMyFAQ v2.6.6 - 2010-06-21

- refactored database abstraction (Thorsten)
- fixed OpenLDAP authentication (Franky)
- fixed duplication of search results (Aaron Burgie)
- fixed issue with duplicate sitemap characters (Aaron Burgie)
- fixed solution ID issues (Thorsten)
- improved Glossary deletion workflow (Thorsten)
- updated Brazilian Portuguese translation
- updated Norwegian Bokmål translation
- fixed minor bugs (Thorsten, Philip Mikas)

### phpMyFAQ v2.6.5 - 2010-05-24

- updated bundled TCPDF library to v5.0.009 (Thorsten)
- updated bundled TinyMCE editor component to v3.3.5.1 (Thorsten)
- refactored OpenSearch plugin code base (Thorsten)
- fixed small update issues in update script (Thorsten)
- fixed missing language fallback issue in instant response (Thorsten)
- fixed user deletion in admin backend (Thorsten)
- fixed backup/restore bug (Thorsten)
- updated Polish translation
- updated Russian translation
- updated Thai translation
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.6.4 - 2010-04-18

- added experimental support for Microsoft SQL Server Driver for PHP (Thorsten)
- improved FAQ record administration frontend (Thorsten)
- improved Sitemap titles (Thorsten)
- updated German translation (Thorsten)
- updated Romanian translation
- updated Swedish translation
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.6.3 - 2010-03-03

- fixed various mail issues (Thorsten)
- improved links rewriting (Guillaume Le Maout)
- improved sitemap for Chinese, Japanese and Korean content (Thorsten)
- improved meta keyword handling
- updated Portuguese translation (Fernando G. Monteiro)
- updated French translation
- fixed minor bugs (Thorsten)

### phpMyFAQ v2.6.2 - 2010-02-01

- fixed update script issue (Thorsten)
- fixed display of Chinese, Japanese and Korean content in PDFs (Thorsten)
- fixed issues with RSS feeds (Thorsten)
- fixed broken Spanish translation (Thorsten)
- updated French translation (Cédric Frayssinet)
- minor bugfixes (Thorsten)

### phpMyFAQ v2.6.1 - 2010-01-24

- updated bundled TCPDF library to v4.8.026 (Thorsten)
- improved cookie handling (Thorsten)
- minor bugfixes (Thorsten)

### phpMyFAQ v2.6.0 - 2010-01-11

- moved all language files to UTF-8 (Anatoliy)
- added TCPDF as replacement for FPDF library (Thorsten)
- added support for TinyMCE translations (Aurimas Fišeras)
- added support for multi-language news (Thorsten)
- added user adjustable template sets and styles (Anatoliy)
- added support plurals in translations (Aurimas Fišeras)
- added share on Facebook link (Thorsten)
- added a rest/json API (Thorsten)
- added secure login if SSL is available (Tobias Hommel)
- added FAQ entry dependent meta description (Thorsten)
- added experimental support for Microsoft Windows Web App Gallery (Thorsten)
- improved attachment functionality (Anatoliy)
- improved install script (Thorsten)
- improved category view with description (Thorsten)
- improved FAQ entry activation (Anatoliy)
- improved user administration (Max Köhler)
- improved FAQ entry workflow (Anatoliy, Thorsten)
- removed Docbook XML export (Thorsten)
- removed Net_IDNA library (Thorsten)
- updated Greek translation (Periklis Tsirakidis)
- updated Lithuanian translation (Aurimas Fišeras)
- updated Japanese translation

### phpMyFAQ v2.5.7 - 2010-01-19

- improved glossary detection (Guillaume Le Maout)
- updated Dutch translation
- updated Indonasian translation
- updated Chinese (Simplified) translation
- updated Japanese translation
- updated Norwegian Bokmal translation
- many minor bugfixes (Thorsten, Guillaume Le Maout)

### phpMyFAQ v2.5.6 - 2009-12-22

- updated bundled TinyMCE editor component to v3.2.7 (Thorsten)
- updated bundled SyntaxHighlighter library to v2.1.364 (Thorsten)
- updated bundled SyntaxHighlighter plugin for TinyMCE (Thorsten)
- updated Brazilian Portuguese translation
- updated Spanish translation
- minor bugfixes (Thorsten)

### phpMyFAQ v2.5.5 - 2009-12-01

- fixed IE6/7 only XSS vulnerabilities (Amol Naik, Thorsten)
- many minor bugfixes (Thorsten)

### phpMyFAQ v2.5.4 - 2009-11-10

- fixed various PostgreSQL related issues (Anatoliy, Thorsten)
- updated Dutch translation
- updated Brazilian Portuguese translation
- many minor bugfixes (Thorsten)

### phpMyFAQ v2.5.3 - 2009-10-19

- switched repository from SVN to git (David Soria Parra, Thorsten)
- added missing Perl syntax hightlighting (Thorsten)
- fixed missing info link in news (Thorsten)
- fixed broken group permission check for instant response (Thorsten)
- fixed broken attachment download (Thorsten)
- fixed SQLite problems (David Soria Parra)
- updated Japanese translation

### phpMyFAQ v2.5.2 - 2009-09-01

- fixed IE6/7 only XSS vulnerability (Thorsten)
- updated Polish language file (Dariusz Grzesista)
- updated Chinese language file
- fixed problem with Czech translation (Anatoliy)
- many minor bugfixes (Thorsten)

### phpMyFAQ v2.5.1 - 2009-08-10

- added support for LDAP-datamapping, e.g. against an Active Directory Server (Lars Scheithauer)
- added support for multi-domain-authentication, e.g. against an ADS-Global Catalog (Lars Scheithauer)
- added support for PHP LDAP options (Lars Scheithauer)
- added Lithuanian translation (Aurimas Fiseras)
- fixed LDAP issues (Lars Scheithauer)
- fixed some Oracle issues (Thorsten)
- improved svn2package script (Rene Treffer)
- many minor bugfixes (Thorsten)

### phpMyFAQ v2.5.0 - 2009-07-21

- changed PHP requirement to PHP 5.2 (Thorsten)
- dropped support for MySQL 4.0 and MaxDB (Thorsten)
- dropped support for XML-RPC (Thorsten)
- refactored complete code base to PHP 5.2+ (Thorsten, Matteo)
- added new and improved new basic layout theme (Charles A. Landemaine)
- added new administration layout (Thorsten)
- added new Image Manager in administration backend (Thorsten)
- added RSS feed for every category (Thorsten)
- added ability to copy and duplicate FAQ entries (Thorsten)
- added support for blocks in template engine (Jan Mergler)
- added public user registration (Elger Thiele)
- added LDAP authentication (Alberto Cabello Sanchez)
- added HTTP authentication (Thorsten)
- added Mail sub system (Matteo)
- added enable/disable WYSIWYG Editor flag (Thorsten)
- added most popular searches list (Thorsten)
- added frontend for search logs statistics (Anatoliy Belsky)
- added sticky FAQ records (Anatoliy Belsky)
- added smart answering for user questions (Anatoliy Belsky)
- added string wrapper for better UTF-8 handling (Anatoliy Belsky)
- added translation frontend (Anatoliy Belsky)
- added jQuery 1.3 as replacement for Prototype/Script.aculo.us (Thorsten)
- added configurable attachment directory (Anatoliy Belsky)
- added approval permission for FAQs (Anatoliy Belsky)
- added Hindi translation (Sumeet Raj Aggarwal)
- improved user administration frontend (Sarah Hermann)
- improved performance (Thorsten)
- improved language files (Anatoliy Belsky, Thorsten)
- ajaxified comment and record administration (Thorsten)
- updated bundled Net_IDNA to v0.6.3 (Thorsten)
- updated bundled TinyMCE editor component to v3.2.5 (Thorsten)
- updated bundled FPDF library to v1.6 (Thorsten)
- updated Turkish translation (Evren Yurtesen)

### phpMyFAQ v2.0.15 - 2009-06-02

- fixed XSS vulnerability (Thorsten)

### phpMyFAQ v2.0.14 - 2009-05-21

- updated Vietnamese translation (Julien Petitperrin)
- improved tagging implementation (Thorsten)
- fixed authentication bypass (Thorsten)
- fixed content type for RSS feeds (Thorsten)
- minor bugfixes (Thorsten)

### phpMyFAQ v2.0.13 - 2009-04-20

- added new blocked words for spam protection (Kai)
- fixed fatal error in PHP 5.3 (David Soria Parra, Thorsten)
- fixed redirect problem with multiviews in .htaccess (Antonio)
- fixed problem with visible questions in RSS feed (Thorsten)
- minor bugfixes (Thorsten)

### phpMyFAQ v2.0.12 - 2009-02-17

- fixed bug with MySQL 6 and ext/mysqli (Johannes)
- fixed stat call bug in installer on OpenSuse 10.3 (Thorsten)
- improved SVN checkout script (Rene Treffer)
- minor bugfixes (Thorsten)

### phpMyFAQ v2.0.11 - 2009-01-22

- updated English and Turkish translation and switched to UTF-8 (Evren Yurtesen)
- updated Simplified Chinese translation (Techice Young)
- fixed possible infinity loop bug in categories (Kaoru Izutani)
- fixed permission bypass issue (Thorsten)
- many minor bugfixes (Thorsten)

### phpMyFAQ v2.0.10 - 2008-11-26

- fixed image bug in PDF (Thorsten)
- fixed isses in admin log display (Thorsten)
- re-added missing XML-RPCS library files (Thorsten)
- fixed PHP warnings and notices (Thorsten)
- minor bugfixes (Thorsten, Andreas Hansson)

### phpMyFAQ v2.0.9 - 2008-10-17

- dedicated to my uncle Werner
- fixed content deletion bug (Thorsten)
- fixed SVN export script (Thorsten)
- improved comment spam protection (Thorsten)
- re-added missing css color file (Thorsten)
- minor bugfixes (Thorsten)

### phpMyFAQ v2.0.8 - 2008-09-11

- fixed security vulnerability in XSS filter (Alexios Fakos at n.runs.com)
- switched repository from CVS to SVN (Thorsten)
- fixed session bug with MS SQL (Simon Stewart)
- fixed login errors with IBM DB2 (Thorsten, Helmut Tessarek)
- updated French translation (Julien Ross)
- updated Serbian translation (Slavisa Milojkovic)

### phpMyFAQ v2.0.7 - 2008-05-12

- added Bengali translation (Md. Masum Billah)
- added Ukrainian translation (Oleg P. Suvolokin and Denis A. Barybin)
- updated Czech translation (Petr Silon)
- minor bugfixes (Thorsten)

### phpMyFAQ v2.0.6 - 2008-02-24

- permission setting related fix (Carlos Eduardo Nogueira Gon�alves)
- minor bugfixes (Thorsten)

### phpMyFAQ v2.0.5 - 2008-01-20

- fixed lighttpd rewrite rules (Markus Kohlmeyer)
- minor bugfixes (Thorsten)

### phpMyFAQ v2.0.4 - 2007-11-18

- add Thai translation (Thanadon Somdee)
- improved DiggIt link (Thorsten
- fixed French translation (Thorsten)
- many minor bugfixes (Thorsten)

### phpMyFAQ v2.0.3 - 2007-08-18

- some permission related fixes (Thorsten)
- some glossary related fixes (Thorsten)
- many minor bugfixes (Thorsten)

### phpMyFAQ v2.0.2 - 2007-07-08

- some performance improvements (Thorsten)
- some permission related fixes (Adrianna Musiol)
- some update fixes (Matteo)
- updated Danish translation (Tommy Ipsen)
- some minor bugfixes (Thorsten)

### phpMyFAQ v2.0.1 - 2007-06-01

- fixed broken update script (Thorsten, Matteo)
- fixed bugs with basic permission level (Thorsten, Matteo)
- fixed PHP segfaults with Zend Optimizer extension (Thorsten, Matteo)
- updated Japanese translation (Tadashi Jokagi)
- many minor bugfixes (Thorsten, Matteo)

### phpMyFAQ v2.0.0 - 2007-05-22

- added rewritten and enhanced user management (Lars)
- added rewritten and enhanced authorization management (Lars)
- added user and group based permissions for categories and records (Thorsten)
- added Ajax support (Thorsten, Matteo)
- added automatic link verification (Minoru Toda)
- added user binding to a category (Thorsten)
- added glossary support (Thorsten)
- added possibility to delete admin log (Thorsten)
- added configurable visibility of new questions (Thorsten)
- added improved and template-based XHTML export (Thorsten)
- added dynamic related articles (Thorsten, Marco Enders, Thomas Zeithaml)
- added support for Google, Yahoo!, and MSN sitemaps (Matteo)
- added improved WYSIWYG editor and Image Manager (Thorsten)
- added improved News module (Matteo, Thorsten)
- added tagging (Thorsten)
- added DiggIt! link (Thorsten)
- added Microsummaries (Matteo)
- added OpenSearch support (Thorsten)
- added "submit translation" link (Matteo)
- added experimental support for Oracle (Thorsten)
- added experimental support for Interbase/Firebird (Thorsten)
- added sorting by id, title and date for records in admin backend (Thorsten)
- added comment administration frontend (Thorsten)
- added configurable simple reordering of records (Thorsten)
- added questionnaire for statistics in installer (Johannes)
- added Ajax-powered Instant Response (Thorsten)
- added editable default values for record configuration (Thorsten)
- added blacklist for search bots (Marco Fester, Thorsten)
- added stylesheets for right-to-left text-direction (Roy Ronen)
- improved export functions (Matteo)
- improved URL rewrite functions (Matteo)
- improved category management (Thorsten, Rudi Ferrari)
- improved administration backend (Thorsten, Matteo)
- updated Arabic translation (Ahmed Shalaby)
- updated German translation (Thorsten)
- updated Hewbrew translation (Roy Ronen)
- updated Italian translation (Matteo)
- updated Japanese translation (Tadashi Jokagi)
- updated Spanish translation (Eduardo Polidor)

### phpMyFAQ v1.6.11 - 2007-03-31

- updated Finnish translation
- fixed problems with unsupported charsets in PHP
- fixed some minor bugs

### phpMyFAQ v1.6.10 - 2007-02-18

- fixed a serious security issue
- improved performance
- fixed some minor bugs

### phpMyFAQ v1.6.9 - 2007-01-28

- updated bundled PHP XMLRPC to v2.1
- fixed the backup download permissions
- fixed some minor bugs

### phpMyFAQ v1.6.8 - 2006-12-15

- fixed a possible security issue
- fixed the blank dropdown in the installer
- fixed some minor bugs

### phpMyFAQ v1.6.7 - 2006-11-27

- added Persian (Farsi) translation
- fixed PHP 5.2.0 related issues
- fixed some minor bugs

### phpMyFAQ v1.6.6 - 2006-10-28

- updated Arabic translation (also moved to UTF-8)
- updated bundled Net_IDNA to v0.4.4
- fixed some minor bugs

### phpMyFAQ v1.6.5 - 2006-09-21

- added Welsh translation
- added French documentation
- updated Czech translation
- fixed some minor bugs

### phpMyFAQ v1.6.4 - 2006-08-19

- updated Brazilian Portuguese translation
- updated Dutch translation
- updated French translation
- updated Italian translation
- updated Japanese translation
- updated Portuguese translation
- fixed some minor bugs

### phpMyFAQ v1.6.3 - 2006-07-16

- added German documentation
- updated bundled NET_IDNA class to v0.4.3
- released a spec file for building an RPM package of phpMyFAQ
- updated Simplified Chinese translation (also moved to utf-8)
- fixed some minor bugs

### phpMyFAQ v1.6.2 - 2006-06-17

- added user tracking data deletion
- improved PHP 5 support for MySQL and SQLite
- updated Dutch translation
- updated French translation
- updated Italian translation
- updated Swedish translation
- fixed some minor bugs

### phpMyFAQ v1.6.1 - 2006-05-13

- added spam control center
- added mod_rewrite support for lighttpd
- added Microsoft Internet Explorer 7 search plugin
- added automatic admin session expiry notice
- improved record administration
- improved user administration
- improved category administration
- improved highlighting of searched words
- updated Italian translation
- updated German translation
- updated Danish translation
- fixed some minor bugs

### phpMyFAQ v1.6.0 - 2006-04-21

- fixed PHP security issue
- added unique solution id
- added revision system
- added support for captchas
- added bad word list blocker
- added search in one selected category
- added linked breadcrumb
- added RSS feed for open questions
- added Brazilian Portuguese translation
- improved record administration
- improved spam protection
- improved language detection and handling
- updated Italian translation
- updated Japanese translation
- updated Korean translation
- fixed some minor bugs

### phpMyFAQ v1.5.9 - 2006-04-21

- fixed PHP security issue

### phpMyFAQ v1.5.8 - 2006-04-09

- updated Korean translation
- fixed some minor bugs

### phpMyFAQ v1.5.7 - 2006-03-02

- fixed some minor bugs

### phpMyFAQ v1.5.6 - 2006-01-27

- added Basque translation
- improved spam protection
- fixed some minor bugs

### phpMyFAQ v1.5.5 - 2005-12-19

- added support for MaxDB
- added keywords into meta keywords
- added search by record ID
- improved language detection and handling
- updated Italian translation
- fixed some minor bugs

### phpMyFAQ v1.5.4 - 2005-11-18

- fixed security issues
- full support for IBM DB2 databases
- added re-ordering of sub-categories
- added Firefox/Mozilla search plugin
- updated Hebrew translation
- updated Simplified Chinese translation
- updated Spanish translation
- updated Italian translation
- updated Japanese translation
- updated Danish translation
- some minor improvements
- many minor bugfixes

### phpMyFAQ v1.5.3 - 2005-10-15

- added several security enhancements
- added Greek translation
- improved RSS support
- improved backup/restore frontend
- updated Indonasian translation
- updated bundled Net_IDNA class
- many minor bugfixes

### phpMyFAQ v1.5.2 - 2005-09-23

- fixed serious security issues
- full support for SQLite
- more compliance with phpMyFAQ 1.4.x templates
- many minor bugfixes

### phpMyFAQ v1.5.1 - 2005-09-19

- added experimental support for SQLite
- added dynamic Sitemap
- added Norwegian Bokm�l translation
- improved image handling in PDF export
- some minor bugfixes

### phpMyFAQ v1.5.0 - 2005-08-20

- full support for PostgreSQL databases
- full support for Sybase databases
- full support for MS SQL databases
- full support for MySQL 4.1/5.0 databases
- experimental support for IBM DB2 databases
- LDAP support as an additional option
- one entry in various categories
- mod_rewrite support
- faster template engine parses PHP code
- rewritten PDF export
- complete XML, XHTML and DocBook XML exports
- many code and performance improvements and code cleanup
- better RSS support
- updated bundled htmlArea
- PHP 5.x compatible

### phpMyFAQ v1.4.10 - 2005-08-01

- compatibility to PHP 4.4.0

### phpMyFAQ v1.4.9 - 2005-06-29

- fixed serious security issue in bundled XML-RPC component
- some minor bugfixes

### phpMyFAQ v1.4.8 - 2005-04-11

- fixed bug with images in PDFs
- fixed bug with URLs in export
- updated Japanese language file
- updated Korean translation
- some minor bugfixes

### phpMyFAQ v1.4.7 - 2005-03-06

- fixed possible SQL injection bug
- some minor bugfixes

### phpMyFAQ v1.4.6 - 2005-02-20

- updated Polish language file
- updated French language file
- some minor bugfixes

### phpMyFAQ v1.4.5 - 2005-01-21

- updated Japanese language file
- updated Chinese (Traditional) translation
- some minor bugfixes

### phpMyFAQ v1.4.4 - 2004-12-06

- added Romanian translation
- added Chinese (Traditional) translation
- many bugfixes

### phpMyFAQ v1.4.3 - 2004-11-05

- added Turkish translation
- added Indonesian translation
- updated German language file
- many bugfixes

### phpMyFAQ v1.4.2 - 2004-10-10

- added Finnish translation
- added Hebrew translation
- fulltext search inside admin section
- some accessibility improvements
- many bugfixes

### phpMyFAQ v1.4.1 - 2004-08-16

- improved category administration
- added Swedish translation
- added Korean translation
- added Japanese translation
- custom admin menu according to user permissions
- easier record deleting
- session and language stored in a cookie
- less SQL queries in admin section
- updated install script
- updated Chinese language file
- updated Portoguese language file
- improved IDN domain support
- improved accessibility
- many bugfixes

### phpMyFAQ v1.4.0a - 2004-07-27

- fixed security vulnerability

### phpMyFAQ v1.4.0 - 2004-07-22

- added WYSIWYG Editor
- added Image Manager
- added new category module
- added support for XML-RPC
- added support for timezones
- added ISO 639 support in language files
- added script that converts BBCode in XHTML
- better PDF export with table of contents
- new and better installer
- new XHTML based templates
- automatic language detection
- improved IDN domain support
- password reset function
- include internal links with dropdown box
- many code improvements and code cleanup
- many bug fixes

### phpMyFAQ v1.3.14 - 2004-06-09:

- added Slovenian translation
- added Serbian translation
- added Danish translation
- improved performance on Windows Server 2003 and PHP with ISAPI
- fixed some bugs

### phpMyFAQ v1.3.13 - 2004-05-17:

- fixed serious security vulnerability (Stefan Esser)
- fixed some bugs

### phpMyFAQ v1.3.12 - 2004-04-21:

- added Hungarian language file
- fixed some bugs

### phpMyFAQ v1.3.11a - 2004-04-13:

- fixed some annoying bugs

### phpMyFAQ v1.3.11 - 2004-04-07:

- added Chinese translation
- added Czech translation
- added support for IDN domains
- many bugfixes

### phpMyFAQ v1.3.10 - 2004-02-12

- updated bundled FPDF class
- added Arabic language file
- many bugfixes

### phpMyFAQ v1.3.9pl1 - 2004-01-02

- added Vietnamese language file
- bugfixes

### phpMyFAQ v1.3.9 - 2003-11-26

- improvements at highlighting searched words
- updated english language file
- bBCode support for the news
- better category browsing
- graphical analysis of votings
- date informations ISO 8601 compliant
- some optimized code
- added Russian language file
- enhanced BB-Code Editor
- fixed some multibyte issues
- improved display of images in PDF export
- some fixes for PHP5
- some bug fixes

### phpMyFAQ v1.3.8 - 2003-10-23

- added latvian language file
- fixed italian language file
- bugfix in backup module (IE problem)
- support for MySQL 4.0 full text search
- some improvements in installer (Bug #0000017)
- many bugfixes in the backup module
- better performance in admin area
- fixed cookie problem
- fixed a bug in the BB-Code Parser
- fixed a bug in the language files
- many, many minor bug fixes

### phpMyFAQ v1.3.7 - 2003-09-19

- dedicated to Johnny Cash
- added a patch against Verisign
- fixed Windows bug in Send2Friend
- some improvements in the BB-Code-Parser
- fixed some layout problems
- many, many minor bug fixes

### phpMyFAQ v1.3.6 - 2003-09-01

- fixed bug in installer

### phpMyFAQ v1.3.5 - 2003-08-31

- basic internal linking of FAQ records
- RSS-Feed-Export via Cronjob
- solved some PDF problems
- updated english language file
- some improvements in the BB-Code-Parser
- some bug fixes

### phpMyFAQ v1.3.4 - 2003-08-03

- improvements at highlighting searched words
- fixed bug in installer
- added new BB-Code-Parser
- added italian translation
- updated dutch and french language file
- little changes in the language files
- many, many minor bug fixes

### phpMyFAQ v1.3.3 - 2003-06-26

- better installation
- default password removed
- added IP ban lists
- added portuguese translation
- added spanish translation
- selection of default language and setting the password at installation
- changed default language from german to english
- better PDF export with support for pictures
- export all records to one PDF file
- user management improved
- added allow/disallow comment function
- added copyright notice and url in the printer friendly page and the pdf file
- backup files are called phpmyfaq.<date>.sql instead of attachment.php
- fixed bug at installation when using MySQL 4
- fixed bug when deleting news
- fixed bug when deleting comments
- many minor bug fixes

### phpMyFAQ v1.3.2 - 2003-05-25

- more verifications in update script
- added new category sorting
- added polish language file
- fixed bug in backup
- added reload locking in voting module
- BB-Code help with multi-language support
- better navigation
- minor bug fixes

### phpMyFAQ v1.3.1 - 2003-05-02

- added preview at record editing
- added RSS-Feeds from Top 10, News and latest records
- better navigation in admin area
- system information in admin area
- added french language file
- fixed bug in session search
- fixed bugs in adding records
- solved cookie problems
- fixed a problem with send2friend link
- fixed delimiter bug with Apache2 and PHP 4.3
- minor bug fixes

### phpMyFAQ v1.3.0 - 2003-04-17

- support for multi language records
- enhanced security
- encrypted passwords
- admin area uses modules
- PDF support
- more support of XML with XML namespaces and XML schema
- BBCode editor, support for more bb code
- PHP syntax highlighting
- database abstraction layer
- english documentation
- many bugfixes

### phpMyFAQ v1.2.5b - 2003-03-24

- bugfixes

### phpMyFAQ v1.2.5a - 2003-03-04

- uBB code bugfixes
- top ten bugfix

### phpMyFAQ v1.2.5 - 2003-02-02

- bugfixes

### phpMyFAQ v1.2.4 - 2003-01-31

- better checking of variables
- bugs in admin area fixed
- better printing function

### phpMyFAQ v1.2.3 - 2002-11-30

- check whether installation oder update script isn't deleted
- fixed bugs in language files, the news module and open questions
- automatic langauge detection in admin area

### phpMyFAQ v1.2.2 - 2002-11-04

- minor bug fixes

### phpMyFAQ v1.2.1 - 2002-10-24

- better update function and language selection
- solved cookie problems
- many bug fixes, thanks to sascha AT rootforum DOT de

### phpMyFAQ v1.2.0 - 2002-10-09

- phpMyFAQ is now Open Source software
- template system for free layouts
- fully compatible with PHP 4.1, PHP 4.2 and PHP 4.3 (register_globals = off)
- all color and font definitions with CSS
- better SQL queries
- better category navigation
- better search engine
- better Send2Friend function
- better installation script
- many bugfixes

### phpMyFAQ v1.1.5 - 2002-06-23

- minor bug fixes
- russian language file

### phpMyFAQ v1.1.4a - 2002-06-08

- minor bug fixes for PHP 4.1.0

### phpMyFAQ v1.1.4 - 2002-05-24

- minor bug fixes
- rewrite of PHP code for better performance
- change of the CSS file from style.php to style.css
- better HTML code
- voting can be switched off

### phpMyFAQ v1.1.3 - 2002-05-01

- fixed bug in UBB parser
- fixed bugs in viewing comments
- rewrite of the PHP code

### phpMyFAQ v1.1.2 - 2002-03-22

- added Send2Friend function
- minor bug fixes

### phpMyFAQ v1.1.1 - 2002-03-06

- minor bug fixes

### phpMyFAQ v1.1.0 - 2002-02-11

- porting to PHP4
- many bugfixes
- more functions in the attachments module
- support for sub categories
- user tracking and admin logging can be switched off
- better installation script
- better PHP code
- admin area supports Netscape 4.x
- no actions could be performed without any records
- better admin logging
- admin account cannot be deleted (security fix)
- better documention

### phpMyFAQ v1.0.1a - 2001-10-15

- file ending .php instead of .php3

### phpMyFAQ v1.0.1 - 2001-10-10

- fixed bugs in installation and update script

### phpMyFAQ v1.0 - 2001-09-30

- minor bug fixes

### phpMyFAQ v0.95 - 2001-09-11

- cleaned MySQL table names
- documentation
- phpMyFAQ is HTML 4.0 valid
- minor bug fixes

### phpMyFAQ v0.90 - 2001-08-23

- added update function for phpMyFAQ v0.80
- added question _ answer _ system
- configurable design of the admin area
- minor bug fixes

### phpMyFAQ v0.87 - 2001-07-20

- top Ten and newest records can be switched off
- minor bug fixes

### phpMyFAQ v0.86 - 2001-07-10

- uBB parser fixed
- minor bug fixes

### phpMyFAQ v0.85 - 2001-07-08

- added backup module (Import and Export)
- added UBB-Code support
- records can be exported to XML
- minor bug fixes

### phpMyFAQ v0.80a - 2001-06-07

- minor bug fixes

### phpMyFAQ v0.80 - 2001-05-30

- added form for questions
- added Top 5 of the newest articles
- added support for attachments for records in admin area
- added support for adding records in admin area
- configuration editable in admin area
- showing number of users online
- print function
- better support in writing comments
- bugfix: fixed bad output in comments with HTML

### phpMyFAQ v0.70 - 2001-04-27

- installation script
- better right management in admin area
- free designs in configuration possible
- added support for language files (german, english)
- bugfix: fixed a problem when deleting comments

### phpMyFAQ v0.666 - 2001-04-10

- added support for categories
- added voting system
- added support for deleting comments
- minor bug fixes

### phpMyFAQ v0.65 - 2001-03-18

- added support for comments
- added support for FAQ news
- better search engine

### phpMyFAQ v0.60 - 2001-02-22

- first released version

All versions before 0.60 were internal developer versions.

This Source Code Form is subject to the terms of the Mozilla Public License,
v. 2.0. If a copy of the MPL was not distributed with this file, You can
obtain one at http://mozilla.org/MPL/2.0/.

Software distributed under the License is distributed on an "AS IS"
basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
License for the specific language governing rights and limitations
under the License.

Copyright © 2001-2022 Thorsten Rinne and the phpMyFAQ Team
