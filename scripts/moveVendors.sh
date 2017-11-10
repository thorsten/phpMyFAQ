#!/bin/sh

cwd=`pwd`

<<<<<<< HEAD
mkdir -p $cwd/phpmyfaq/inc/libs/phpseclib/Crypt
cp -r $cwd/vendor/phpseclib/phpseclib/phpseclib/Crypt $cwd/phpmyfaq/inc/libs/phpseclib
cp -r $cwd/vendor/kertz/twitteroauth/twitteroauth $cwd/phpmyfaq/inc/libs/twitteroauth
cp -r $cwd/vendor/symfony/*/* $cwd/phpmyfaq/inc/libs/
cp -r $cwd/vendor/twig/twig/lib/Twig $cwd/phpmyfaq/inc/libs/
=======
mkdir -p $cwd/phpmyfaq/src/libs/elasticsearch/src/Elasticsearch
mkdir -p $cwd/phpmyfaq/src/libs/guzzlehttp/ringphp/src
mkdir -p $cwd/phpmyfaq/src/libs/monolog/src/Monolog
mkdir -p $cwd/phpmyfaq/src/libs/parsedown
mkdir -p $cwd/phpmyfaq/src/libs/phpseclib/Crypt
mkdir -p $cwd/phpmyfaq/src/libs/psr/log/Psr
mkdir -p $cwd/phpmyfaq/src/libs/react/promise/src
mkdir -p $cwd/phpmyfaq/src/libs/swiftmailer
mkdir -p $cwd/phpmyfaq/src/libs/symfony/class-loader
mkdir -p $cwd/phpmyfaq/src/libs/tcpdf

cp -r $cwd/vendor/elasticsearch/elasticsearch/src/Elasticsearch/* $cwd/phpmyfaq/src/libs/elasticsearch/src/Elasticsearch
cp -r $cwd/vendor/guzzlehttp/ringphp/src/* $cwd/phpmyfaq/src/libs/guzzlehttp/ringphp/src
cp -r $cwd/vendor/monolog/monolog/src/Monolog/* $cwd/phpmyfaq/src/libs/monolog/src/Monolog
cp -r $cwd/vendor/erusev/parsedown/Parsedown.php $cwd/phpmyfaq/src/libs/parsedown/Parsedown.php
cp -r $cwd/vendor/erusev/parsedown-extra/ParsedownExtra.php $cwd/phpmyfaq/src/libs/parsedown/ParsedownExtra.php
cp -r $cwd/vendor/phpseclib/phpseclib/Crypt $cwd/phpmyfaq/src/libs/phpseclib
cp -r $cwd/vendor/psr/log/Psr/* $cwd/phpmyfaq/src/libs/psr/log/Psr
cp -R $cwd/vendor/react/promise/src/* $cwd/phpmyfaq/src/libs/react/promise/src
cp -r $cwd/vendor/swiftmailer/swiftmailer/lib/* $cwd/phpmyfaq/src/libs/swiftmailer
cp -r $cwd/vendor/symfony/class-loader/* $cwd/phpmyfaq/src/libs/symfony/class-loader
cp -r $cwd/vendor/thorsten/twitteroauth/twitteroauth $cwd/phpmyfaq/src/libs/twitteroauth

# TCPDF
mkdir -p $cwd/phpmyfaq/src/libs/tcpdf
mkdir -p $cwd/phpmyfaq/src/libs/tcpdf/config
mkdir -p $cwd/phpmyfaq/src/libs/tcpdf/include

cp $cwd/vendor/tecnickcom/tcpdf/*.php $cwd/phpmyfaq/src/libs/tcpdf

cp -r $cwd/vendor/tecnickcom/tcpdf/config/*.php $cwd/phpmyfaq/src/libs/tcpdf/config
cp -r $cwd/vendor/tecnickcom/tcpdf/include/*.php $cwd/phpmyfaq/src/libs/tcpdf/include
>>>>>>> 2.10
