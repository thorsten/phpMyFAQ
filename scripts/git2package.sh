#!/bin/sh
#
# This is the shell script for building:
# 1. a TAR.GZ package;
# 2. a ZIP package
# of phpMyFAQ using what committed into Git.
#
# For creating a package simply run:
#
#   ./git2package.sh
#
# The script will download the source code from branch and
# it will create the 2 packages plus their MD5 hashes.
#
# This Source Code Form is subject to the terms of the Mozilla Public License,
# v. 2.0. If a copy of the MPL was not distributed with this file, You can
# obtain one at http://mozilla.org/MPL/2.0/.
#
# @package   phpMyFAQ
# @author    Matteo Scaramuccia <matteo@scaramuccia.com>
# @author    Thorsten Rinne <thorsten@phpmyfaq.de>
# @author    Rene Treffer <treffer+phpmyfaq@measite.de>
# @author    David Soria Parra <dsp@php.net>
# @copyright 2008-2012 phpMyFAQ Team
# @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
# @link      http://www.phpmyfaq.de
# @version   2008-09-10

# phpMyFAQ Version
. scripts/version.sh

if [ "x${MD5BIN}" = "x" ]; then
    if which md5 > /dev/null; then
        MD5BIN="$(which md5)"
    else
        MD5BIN="$(which md5sum)"
    fi
fi

# Package Folder
if [ "x${PMF_PACKAGE_FOLDER}" = "x" ]; then
    PMF_PACKAGE_FOLDER="phpmyfaq-${PMF_VERSION}"
fi

cwd=`pwd`
git checkout-index -f -a --prefix=$cwd/build/${PMF_PACKAGE_FOLDER}/

# add deps
composer install
mkdir -p $cwd/build/${PMF_PACKAGE_FOLDER}/phpmyfaq/inc/libs/phpseclib/Crypt
cp -r $cwd/vendor/phpseclib/phpseclib/Crypt $cwd/build/${PMF_PACKAGE_FOLDER}/phpmyfaq/inc/libs/phpseclib/Crypt
cp -r $cwd/vendor/twitteroauth/twitteroauth $cwd/build/${PMF_PACKAGE_FOLDER}/phpmyfaq/inc/libs/twitteroauth

# prepare packaging
mkdir $cwd/build/${PMF_PACKAGE_FOLDER}/phpmyfaq/phpmyfaq
mv $cwd/build/${PMF_PACKAGE_FOLDER}/phpmyfaq/* $cwd/build/${PMF_PACKAGE_FOLDER}/phpmyfaq/phpmyfaq/

# build packages
tar cfvz ${PMF_PACKAGE_FOLDER}.tar.gz -C $cwd/build/${PMF_PACKAGE_FOLDER}/phpmyfaq .
cd $cwd/build/${PMF_PACKAGE_FOLDER}/phpmyfaq
zip -r $cwd/${PMF_PACKAGE_FOLDER}.zip phpmyfaq

# md5sum
$MD5BIN "${PMF_PACKAGE_FOLDER}.tar.gz" > "${PMF_PACKAGE_FOLDER}.tar.gz.md5"
$MD5BIN "${PMF_PACKAGE_FOLDER}.zip" > "${PMF_PACKAGE_FOLDER}.zip.md5"

# clean up
rm -rf $cwd/build/${PMF_PACKAGE_FOLDER}/phpmyfaq
