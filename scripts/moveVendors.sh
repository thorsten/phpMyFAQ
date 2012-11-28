#!/bin/sh

# phpMyFAQ Version
. scripts/version.sh

cwd=`pwd`

mkdir -p $cwd/build/${PMF_PACKAGE_FOLDER}/phpmyfaq/inc/libs/phpseclib/Crypt
cp -r $cwd/vendor/phpseclib/phpseclib/Crypt $cwd/build/${PMF_PACKAGE_FOLDER}/phpmyfaq/inc/libs/phpseclib/Crypt
cp -r $cwd/vendor/twitteroauth/twitteroauth $cwd/build/${PMF_PACKAGE_FOLDER}/phpmyfaq/inc/libs/twitteroauth