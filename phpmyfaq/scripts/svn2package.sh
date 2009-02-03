#!/bin/sh
#
# This is the shell script for building:
# 1. a TAR.GZ package;
# 2. a ZIP package
# of phpMyFAQ using what committed into SVN.
# Note: it requires an internet connection.
#
# Before using it, just only for the first launch, you need to
# launch this command:
#
#   svn checkout svn+ssh://anonymous@thinkforge.org/svnroot/phpmyfaq
#
# When prompted for a password for anonymous, simply press the Enter key.
#
# For creating a package simply run:
#
#   ./svn2package.sh
#
# The script will download the source code from branch CVS_TAG and
# it will create the 2 packages plus their MD5 hashes.
#
# @package   phpMyFAQ
# @author    Matteo Scaramuccia <matteo@scaramuccia.com>
# @author    Thorsten Rinne <thorsten@phpmyfaq.de>
# @author    Rene Treffer <treffer+phpmyfaq@measite.de>
# @since     2008-09-10
# @copyright 2008-2009 phpMyFAQ Team
# @version   SVN: $Id$
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

# SVN Branch
SVN_BRANCH="PMF_2_0"
# PMF Version
PMF_VERSION="2.0.12"

# Build folder
PMF_BUILD_FOLDER="PMFBUILD_${SVN_BRANCH}_${PMF_VERSION}"

( # Prepare the package building environment
    if [ ! -d ${PMF_BUILD_FOLDER} ]; then
        mkdir ${PMF_BUILD_FOLDER}
    fi

    # Enter the PMF build folder
    cd "${PMF_BUILD_FOLDER}"
    # Clean files
    rm -f *

    # Get the PMF source code from SVN using an anonymous login
    SVN_SSH="ssh -p 20022" svn export svn+ssh://anonymous@thinkforge.org/svnroot/phpmyfaq/branches/${SVN_BRANCH}/phpmyfaq phpmyfaq

    # Rename the folder in which the SVN code has been retrieved
    mv phpmyfaq "${PMF_PACKAGE_FOLDER}"

    # Build TAR.GZ Package
    tar zcf "${PMF_PACKAGE_FOLDER}.tar.gz" "${PMF_PACKAGE_FOLDER}"
    md5sum "${PMF_PACKAGE_FOLDER}.tar.gz" > "${PMF_PACKAGE_FOLDER}.tar.gz.md5"

    # Build ZIP Package
    zip -r "${PMF_PACKAGE_FOLDER}.zip" "${PMF_PACKAGE_FOLDER}"
    md5sum "${PMF_PACKAGE_FOLDER}.zip" > "${PMF_PACKAGE_FOLDER}.zip.md5"

    # Remove the code folder
    rm -rf "${PMF_PACKAGE_FOLDER}"

) # Back to the folder from which the script was called
