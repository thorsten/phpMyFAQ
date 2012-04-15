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

# PMF Version
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
gitdir=`git rev-parse --git-dir`
dir=`dirname ${gitdir}`/phpmyfaq
cd $dir

(git archive --worktree-attributes --format=tar --prefix="${PMF_PACKAGE_FOLDER}/" HEAD | gzip -9 > $cwd/"${PMF_PACKAGE_FOLDER}.tar.gz" &&
 git archive --worktree-attributes --format=zip --prefix="${PMF_PACKAGE_FOLDER}/" --output="$cwd/${PMF_PACKAGE_FOLDER}.zip" HEAD) && 
(cd $cwd && $MD5BIN "${PMF_PACKAGE_FOLDER}.tar.gz" > "${PMF_PACKAGE_FOLDER}.tar.gz.md5" &&
    $MD5BIN "${PMF_PACKAGE_FOLDER}.zip" > "${PMF_PACKAGE_FOLDER}.zip.md5"
) # Back to the folder from which the script was called
