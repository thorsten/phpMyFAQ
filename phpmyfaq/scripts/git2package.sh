#!/bin/sh
#
# This is the shell script for building:
# 1. a TAR.GZ package;
# 2. a ZIP package
# of phpMyFAQ using what committed into Git.
# Note: it requires an internet connection.
#
# For creating a package simply run:
#
#   ./git2package.sh
#
# The script will download the source code from branch CVS_TAG and
# it will create the 2 packages plus their MD5 hashes.
#
# @package   phpMyFAQ
# @author    Matteo Scaramuccia <matteo@scaramuccia.com>
# @author	 Thorsten Rinne <thorsten@phpmyfaq.de>
# @author    Rene Treffer <treffer+phpmyfaq@measite.de>
# @author    David Soria Parra <dsp@php.net>
# @since     2008-09-10
# @copyright 2008-2011 phpMyFAQ Team
# @version   Since 2.5
#
# The contents of this file are subject to the Mozilla Public License
# Version 1.1 (the "License"); you may not use this file except in
# compliance with the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS"
# basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
# License for the specific language governing rights and limitations
# under the License.

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
