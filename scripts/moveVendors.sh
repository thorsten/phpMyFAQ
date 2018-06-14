#!/bin/sh

cwd=`pwd`

mkdir -pv $cwd/phpmyfaq/src/libs/abraham/twitteroauth
mkdir -pv $cwd/phpmyfaq/src/libs/composer
mkdir -pv $cwd/phpmyfaq/src/libs/elasticsearch/src/Elasticsearch
mkdir -pv $cwd/phpmyfaq/src/libs/guzzlehttp/ringphp/src
mkdir -pv $cwd/phpmyfaq/src/libs/monolog/src/Monolog
mkdir -pv $cwd/phpmyfaq/src/libs/parsedown
mkdir -pv $cwd/phpmyfaq/src/libs/phpseclib/phpseclib/phpseclib/Crypt
mkdir -pv $cwd/phpmyfaq/src/libs/psr/log/Psr
mkdir -pv $cwd/phpmyfaq/src/libs/react/promise/src
mkdir -pv $cwd/phpmyfaq/src/libs/swiftmailer/swiftmailer/lib
mkdir -pv $cwd/phpmyfaq/src/libs/symfony/polyfill-ctype
mkdir -pv $cwd/phpmyfaq/src/libs/tcpdf
mkdir -pv $cwd/phpmyfaq/src/libs/myclabs/deep-copy/src

cp -r $cwd/vendor/abraham/twitteroauth/* $cwd/phpmyfaq/src/libs/abraham/twitteroauth
cp -r $cwd/vendor/autoload.php $cwd/phpmyfaq/src/libs/autoload.php
cp -r $cwd/vendor/composer/* $cwd/phpmyfaq/src/libs/composer
cp -r $cwd/vendor/elasticsearch/elasticsearch/src/Elasticsearch/* $cwd/phpmyfaq/src/libs/elasticsearch/src/Elasticsearch
cp -r $cwd/vendor/guzzlehttp/ringphp/src/* $cwd/phpmyfaq/src/libs/guzzlehttp/ringphp/src
cp -r $cwd/vendor/monolog/monolog/src/Monolog/* $cwd/phpmyfaq/src/libs/monolog/src/Monolog
cp $cwd/vendor/erusev/parsedown/Parsedown.php $cwd/phpmyfaq/src/libs/parsedown/Parsedown.php
cp $cwd/vendor/erusev/parsedown-extra/ParsedownExtra.php $cwd/phpmyfaq/src/libs/parsedown/ParsedownExtra.php
cp -r $cwd/vendor/phpseclib/phpseclib/phpseclib/Crypt $cwd/phpmyfaq/src/libs/phpseclib
cp $cwd/vendor/phpseclib/phpseclib/phpseclib/bootstrap.php $cwd/phpmyfaq/src/libs/phpseclib/phpseclib/phpseclib/
cp -r $cwd/vendor/psr/log/Psr/* $cwd/phpmyfaq/src/libs/psr/log/Psr
cp -r $cwd/vendor/react/promise/src/* $cwd/phpmyfaq/src/libs/react/promise/src
cp -r $cwd/vendor/swiftmailer/swiftmailer/lib/* $cwd/phpmyfaq/src/libs/swiftmailer/swiftmailer/lib
cp -r $cwd/vendor/symfony/polyfill-ctype/* $cwd/phpmyfaq/src/libs/symfony/polyfill-ctype
cp -r $cwd/vendor/myclabs/deep-copy/src/* $cwd/phpmyfaq/src/libs/myclabs/deep-copy/src

# TCPDF
mkdir -p $cwd/phpmyfaq/src/libs/tcpdf
mkdir -p $cwd/phpmyfaq/src/libs/tcpdf/config
mkdir -p $cwd/phpmyfaq/src/libs/tcpdf/include

cp $cwd/vendor/tecnickcom/tcpdf/*.php $cwd/phpmyfaq/src/libs/tcpdf

cp -r $cwd/vendor/tecnickcom/tcpdf/config/*.php $cwd/phpmyfaq/src/libs/tcpdf/config
cp -r $cwd/vendor/tecnickcom/tcpdf/include/*.php $cwd/phpmyfaq/src/libs/tcpdf/include