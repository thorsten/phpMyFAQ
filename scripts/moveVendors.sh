#!/bin/sh

cwd=`pwd`

mkdir -p $cwd/phpmyfaq/inc/libs/elasticsearch/src/Elasticsearch
mkdir -p $cwd/phpmyfaq/inc/libs/guzzlehttp/ringphp/src
mkdir -p $cwd/phpmyfaq/inc/libs/monolog/src/Monolog
mkdir -p $cwd/phpmyfaq/inc/libs/parsedown
mkdir -p $cwd/phpmyfaq/inc/libs/phpseclib/Crypt
mkdir -p $cwd/phpmyfaq/inc/libs/psr/log/Psr
mkdir -p $cwd/phpmyfaq/inc/libs/react/promise/src
mkdir -p $cwd/phpmyfaq/inc/libs/swiftmailer
mkdir -p $cwd/phpmyfaq/inc/libs/tcpdf

cp -r $cwd/vendor/elasticsearch/elasticsearch/src/Elasticsearch/* $cwd/phpmyfaq/inc/libs/elasticsearch/src/Elasticsearch
cp -r $cwd/vendor/guzzlehttp/ringphp/src/* $cwd/phpmyfaq/inc/libs/guzzlehttp/ringphp/src
cp -r $cwd/vendor/monolog/monolog/src/Monolog/* $cwd/phpmyfaq/inc/libs/monolog/src/Monolog
cp -r $cwd/vendor/erusev/parsedown/Parsedown.php $cwd/phpmyfaq/inc/libs/parsedown/Parsedown.php
cp -r $cwd/vendor/erusev/parsedown-extra/ParsedownExtra.php $cwd/phpmyfaq/inc/libs/parsedown/ParsedownExtra.php
cp -r $cwd/vendor/phpseclib/phpseclib/phpseclib/Crypt $cwd/phpmyfaq/inc/libs/phpseclib
cp -r $cwd/vendor/psr/log/Psr/* $cwd/phpmyfaq/inc/libs/psr/log/Psr
cp -R $cwd/vendor/react/promise/src/* $cwd/phpmyfaq/inc/libs/react/promise/src
cp -r $cwd/vendor/swiftmailer/swiftmailer/lib/* $cwd/phpmyfaq/inc/libs/swiftmailer
cp -r $cwd/vendor/symfony/class-loader/* $cwd/phpmyfaq/inc/libs/
cp -r $cwd/vendor/abraham/twitteroauth/src $cwd/phpmyfaq/inc/libs/twitteroauth

# TCPDF
mkdir -p $cwd/phpmyfaq/inc/libs/tcpdf
mkdir -p $cwd/phpmyfaq/inc/libs/tcpdf/config
mkdir -p $cwd/phpmyfaq/inc/libs/tcpdf/include

cp $cwd/vendor/tecnickcom/tcpdf/*.php $cwd/phpmyfaq/inc/libs/tcpdf

cp -r $cwd/vendor/tecnickcom/tcpdf/config/*.php $cwd/phpmyfaq/inc/libs/tcpdf/config
cp -r $cwd/vendor/tecnickcom/tcpdf/include/*.php $cwd/phpmyfaq/inc/libs/tcpdf/include
