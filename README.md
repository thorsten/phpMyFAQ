<<<<<<< HEAD
# phpMyFAQ 3.0.0-dev

[![Build Status 3.0.0-dev](https://secure.travis-ci.org/thorsten/phpMyFAQ.png?branch=master)](http://travis-ci.org/thorsten/phpMyFAQ)
[![Build Status 2.9.x](https://secure.travis-ci.org/thorsten/phpMyFAQ.png?branch=2.9)](http://travis-ci.org/thorsten/phpMyFAQ)
[![Build Status 2.8.x](https://secure.travis-ci.org/thorsten/phpMyFAQ.png?branch=2.8)](http://travis-ci.org/thorsten/phpMyFAQ)
=======
# phpMyFAQ 2.10

[![Travis branch](https://img.shields.io/travis/thorsten/phpMyFAQ/2.10.svg?style=flat-square)](https://travis-ci.org/thorsten/phpMyFAQ)
[![Minimum PHP Version](https://img.shields.io/badge/PHP-%3E%3D5.6-%23777BB4.svg?style=flat-square)](https://php.net/)
[![Slack](https://phpmyfaq.herokuapp.com/badge.svg?style=flat-square)](https://phpmyfaq.herokuapp.com)
>>>>>>> 2.10

## What is phpMyFAQ?

phpMyFAQ is a multilingual, completely database-driven FAQ-system. It supports
<<<<<<< HEAD
various databases to store all data, PHP 5.4.4+ or HHVM 3.4.2+ is needed in order to
=======
various databases to store all data, PHP 5.6+ or HHVM 3.4.2+ is needed in order to
>>>>>>> 2.10
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

phpMyFAQ is only supported on PHP 5.6.0 and up, you need a database as well. 
Supported databases are MySQL, Percona Server, PostgreSQL, Microsoft SQL 
Server, SQLite3 and MariaDB. If you want to use Elasticsearch as main search 
engine, you need Elasticsearch 2.x as well. Check our detailed requirements on 
[phpmyfaq.de](http://www.phpmyfaq.de/requirements.php) for more information.


## Installation

### Package for end-users

The best way to install phpMyFAQ is to download it on [phpmyfaq.de](http://www.phpmyfaq.de/download.php),
unzip the package and open http://www.example.org/phpmyfaq/setup/index.php in your browser.
<<<<<<< HEAD
=======

>>>>>>> 2.10

### Git for developers
If you are behind a proxy, run following:

    git config --global url.http://git.code.sf.net/p/tcpdf/code.insteadOf git://git.code.sf.net/p/tcpdf/code

or add the following to your ~/.gitconfig:

    [url "http://git.code.sf.net/p/tcpdf/code"]
        insteadOf = git://git.code.sf.net/p/tcpdf/code

To install run:


    $ git clone git://github.com/thorsten/phpMyFAQ.git
    $ cd phpMyFAQ
    $ git checkout 2.10
    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install
<<<<<<< HEAD
    # Don't run the following commands as root, otherwise they fail
    $ npm install
    $ bower install
    $ grunt
=======
    $ curl -o- -L https://yarnpkg.com/install.sh | bash
    $ yarn install
    $ yarn build
>>>>>>> 2.10

Then just open http://www.example.org/phpmyfaq/setup/index.php in your browser.


## Testing

<<<<<<< HEAD
To run our unittest via PHPUnit v4.x, just execute this command on your CLI
=======
To run our unittest via PHPUnit v5, just execute this command on your CLI
>>>>>>> 2.10

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

**[Bootstrap](http://getbootstrap.com/)**

Licensed under the terms of the Apache License v2.0

**[phpseclib](http://phpseclib.sourceforge.net/)**

Licensed under the terms of the GNU Lesser General Public License

**[Symfony Components](http://www.symfony.com)**

Licensed under the terms of the MIT License

**[TCPDF](http://www.tcpdf.org)**

Licensed under the terms of the GNU Lesser General Public License

**[TwitterOAuth](http://github.com/abraham/twitteroauth)**

Licensed under the terms of the MIT License

**[Font Awesome](http://fontawesome.io/)**

Licenced under the terms of the SIL Open Font License and MIT License

<<<<<<< HEAD
**[Twig](http://twig.sensiolabs.org/)**

Licensed under the terms of the New BSD License
=======
**[highlight.js](https://highlightjs.org/)**

Licensed under the terms of the BSD License

**[Monolog](http://github.com/Seldaek/monolog)**

Licensed under the terms of the MIT License

**[PHP Client for Elasticsearch](http://elastic.co)**

Licensed under the terms of the Apache License v2.0

**[bootstrap-fileinput](http://plugins.krajee.com/file-input)**

Licensed under the terms of the BSD 3-Clause License
>>>>>>> 2.10



Copyright (c) 2001-2017 Thorsten Rinne and the phpMyFAQ Team
