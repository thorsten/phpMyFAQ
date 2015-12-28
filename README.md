# phpMyFAQ 2.9

[![Travis branch](https://img.shields.io/travis/thorsten/phpMyFAQ/2.9.svg?style=flat-square)](https://travis-ci.org/thorsten/phpMyFAQ)
[![Minimum PHP Version](https://img.shields.io/badge/PHP-%3E%3D5.5-%23777BB4.svg?style=flat-square)](https://php.net/)
[![Slack](https://phpmyfaq.herokuapp.com/badge.svg?style=flat-square)](https://phpmyfaq.herokuapp.com)

## What is phpMyFAQ?

phpMyFAQ is a multilingual, completely database-driven FAQ-system. It supports
various databases to store all data, PHP 5.5.0+ or HHVM 3.4.2+ is needed in order to
access this data. phpMyFAQ also offers a multi-language Content Management
System with a WYSIWYG editor and an Image Manager, real time search support with
Elasticsearch, flexible multi-user support with user and group based permissions 
on categories and records, a wiki-like revision feature, a news system, 
user-tracking, 40+ supported languages, enhanced automatic content negotiation, 
HTML5/CSS3 based responsive templates, PDF-support, a backup-system, a dynamic 
sitemap, related FAQs, tagging, RSS feeds, built-in spam protection systems, 
OpenLDAP and Microsoft Active Directory support, and an easy to use installation 
script.


## Requirements

phpMyFAQ is only supported on PHP 5.5.0 and up, you need a database as well. 
Supported databases are MySQL, Percona Server, PostgreSQL, Microsoft SQL 
Server, SQLite3 and MariaDB. If you want to use Elasticsearch as main search 
engine, you need Elasticsearch 2.x as well. Check our detailed requirements on 
[phpmyfaq.de](http://www.phpmyfaq.de/requirements.php) for more information.

## Installation

### Package for end-users

The best way to install phpMyFAQ is to download it on [phpmyfaq.de](http://www.phpmyfaq.de/download.php),
unzip the package and open http://www.example.org/phpmyfaq/setup/index.php in your browser.

### Git for developers

    $ git clone git://github.com/thorsten/phpMyFAQ.git
    $ cd phpMyFAQ
    $ git checkout 2.9
    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install
    $ npm install bower less grunt-cli -g
    $ npm install
    $ bower install
    $ grunt

Then just open http://www.example.org/phpmyfaq/install/setup.php in your browser.


## Testing

To run our unittest via PHPUnit v4, just execute this command on your CLI

    $ bin/phpunit

Please note that phpMyFAQ needs to be installed via Composer.


## Versioning

For transparency and insight into our release cycle, and for striving to maintain backward compatibility,
phpMyFAQ will be maintained under the Semantic Versioning guidelines as much as possible.

Releases will be numbered with the following format:

`<major>.<minor>.<patch>`

And constructed with the following guidelines:

* Breaking backward compatibility bumps the major (and resets the minor and patch)
* New additions without breaking backward compatibility bumps the minor (and resets the patch)
* Bug fixes and misc changes bumps the patch

For more information on SemVer, please visit http://semver.org/.


## Bug tracker

Have a bug or a feature request? [Please open a new issue](https://github.com/thorsten/phpMyFAQ/issues).
Before opening any issue, please search for existing issues.


## Contributing

Please check out our page about contributing on [phpmyfaq.de](http://www.phpmyfaq.de/contribute.php)


## License

Mozilla Public License 2.0, see LICENSE for more information.


## Bundled libraries

**[TinyMCE](http://tinymce.moxiecode.com/)**  

Licensed under the terms of the GNU Lesser General Public License

**[jQuery](http://jquery.com)**

Licensed under the terms of the MIT License

**[jQuery datePicker plugin](http://www.kelvinluck.com/)**

Licensed under the terms of the MIT License

**[jQuery Sparklines plugin](http://omnipotent.net/jquery.sparkline/)**

Licensed under the terms of the New BSD License

**[Modernizr](http://www.modernizr.com/)**

Licensed under the terms of the MIT and BSD licenses

**[Bootstrap](http://twbs.github.com/bootstrap/)**

Licensed under the terms of the Apache License v2.0

**[phpseclib](http://phpseclib.sourceforge.net/)**

Licensed under the terms of the GNU Lesser General Public License

**[Symfony Components](http://www.symfony.com)**

Licensed under the terms of the MIT License

**[TCPDF](http://www.tcpdf.org)**

Licensed under the terms of the GNU Lesser General Public License

**[TwitterOAuth](http://github.com/abraham/twitteroauth)**

Licensed under the terms of the MIT License

**[Font Awesome](http://fortawesome.github.com/Font-Awesome/)**

Licenced under the terms of the SIL Open Font License and MIT License

**[highlight.js](https://highlightjs.org/)**

Licensed under the terms of the BSD License

**[Monolog](http://github.com/Seldaek/monolog)**

Licensed under the terms of the MIT License

**[PHP Client for Elasticsearch](http://elastic.co)**

Licensed under the terms of the Apache License v2.0


Copyright (c) 2001-2015 Thorsten Rinne and the phpMyFAQ Team
