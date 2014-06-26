#!/bin/sh

cwd=`pwd`

mkdir -p $cwd/phpmyfaq/inc/libs/phpseclib/Crypt
cp -r $cwd/vendor/phpseclib/phpseclib/phpseclib/Crypt $cwd/phpmyfaq/inc/libs/phpseclib
cp -r $cwd/vendor/kertz/twitteroauth/twitteroauth $cwd/phpmyfaq/inc/libs/twitteroauth
cp -r $cwd/vendor/symfony/*/* $cwd/phpmyfaq/inc/libs/
cp -r $cwd/vendor/twig/twig/lib/Twig $cwd/phpmyfaq/inc/libs/
