#!/bin/sh

cwd=`pwd`

mkdir -p $cwd/phpmyfaq/inc/libs/phpseclib/Crypt
mkdir -p $cwd/phpmyfaq/inc/libs/swiftmailer
cp -r $cwd/vendor/phpseclib/phpseclib/Crypt $cwd/phpmyfaq/inc/libs/phpseclib
cp -r $cwd/vendor/thorsten/twitteroauth/twitteroauth $cwd/phpmyfaq/inc/libs/twitteroauth
cp -r $cwd/vendor/symfony/class-loader/* $cwd/phpmyfaq/inc/libs/
cp -r $cwd/vendor/swiftmailer/swiftmailer/lib/* $cwd/phpmyfaq/inc/libs/swiftmailer
