#!/bin/sh

cwd=`pwd`

mkdir -p $cwd/phpmyfaq/inc/libs/bulletproof
mkdir -p $cwd/phpmyfaq/inc/libs/parsedown
mkdir -p $cwd/phpmyfaq/inc/libs/phpseclib/Crypt
mkdir -p $cwd/phpmyfaq/inc/libs/swiftmailer
mkdir -p $cwd/phpmyfaq/inc/libs/tcpdf

cp -r $cwd/vendor/erusev/parsedown/Parsedown.php $cwd/phpmyfaq/inc/libs/parsedown/Parsedown.php
cp -r $cwd/vendor/erusev/parsedown-extra/ParsedownExtra.php $cwd/phpmyfaq/inc/libs/parsedown/ParsedownExtra.php
cp -r $cwd/vendor/phpseclib/phpseclib/Crypt $cwd/phpmyfaq/inc/libs/phpseclib
cp -r $cwd/vendor/samayo/bulletproof/src/bulletproof.php $cwd/phpmyfaq/inc/libs/bulletproof/bulletproof.php
cp -r $cwd/vendor/swiftmailer/swiftmailer/lib/* $cwd/phpmyfaq/inc/libs/swiftmailer
cp -r $cwd/vendor/symfony/class-loader/* $cwd/phpmyfaq/inc/libs/
cp -r $cwd/vendor/thorsten/twitteroauth/twitteroauth $cwd/phpmyfaq/inc/libs/twitteroauth

# TCPDF
mkdir -p $cwd/phpmyfaq/inc/libs/tcpdf
mkdir -p $cwd/phpmyfaq/inc/libs/tcpdf/config
mkdir -p $cwd/phpmyfaq/inc/libs/tcpdf/include

cp $cwd/vendor/tecnick.com/tcpdf/*.php $cwd/phpmyfaq/inc/libs/tcpdf

cp -r $cwd/vendor/tecnick.com/tcpdf/config/*.php $cwd/phpmyfaq/inc/libs/tcpdf/config
cp -r $cwd/vendor/tecnick.com/tcpdf/include/*.php $cwd/phpmyfaq/inc/libs/tcpdf/include
