#!/bin/sh
#
# $Id: cvs2package.sh,v 1.8.2.4 2007-08-22 17:34:16 thorstenr Exp $
#
# This is the shell script for building:
# 1. a TAR.GZ package;
# 2. a ZIP package
# of phpMyFAQ using what committed into CVS.
# Note: it requires an internet connection.
#
# Before using it, just only for the first launch, you need to
# launch this command:
#
#   cvs -d:pserver:anonymous@thinkforge.org:/cvsroot/phpmyfaq login
#
# When prompted for a password for anonymous, simply press the Enter key.
#
# For creating a package simply run:
#
#   ./cvs2package.sh
#
# The script will download the source code from branch CVS_TAG and
# it will create the 2 packages plus their MD5 hashes.
#
# @author       Matteo Scaramuccia <matteo@scaramuccia.com>
# @since        2005-11-22
# @copyright:   (c) 2005-2007 phpMyFAQ Team
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

# CVS tag
CVS_TAG="PMF_2_0"
# PMF Version
PMF_VERSION="2.0.4"

# Build folder
PMF_BUILD_FOLDER="PMFBUILD_${CVS_TAG}_${PMF_VERSION}"
# PMF Package folder name
PMF_PACKAGE_FOLDER="phpmyfaq-${PMF_VERSION}"

# Prepare the package building environment
CWD=`pwd`
if [ ! -d ${PMF_BUILD_FOLDER} ]; then
    mkdir ${PMF_BUILD_FOLDER}
fi

# Enter the PMF build folder
cd "${PMF_BUILD_FOLDER}"
# Clean files
rm -f *

# Get the PMF source code from CVS using an anonymous login
# W/ CVS administrative folders
# cvs -z3 -d:pserver:anonymous@thinkforge.org:/cvsroot/phpmyfaq get -r${CVS_TAG} phpmyfaq
# W/O CVS administrative folders
cvs -z3 -d:pserver:anonymous@thinkforge.org:/cvsroot/phpmyfaq export -r${CVS_TAG} phpmyfaq

# Rename the folder in which the CVS code has been retrieved
mv phpmyfaq "${PMF_PACKAGE_FOLDER}"

# Build TAR.GZ Package
tar zcf "${PMF_PACKAGE_FOLDER}.tar.gz" "${PMF_PACKAGE_FOLDER}"
md5sum "${PMF_PACKAGE_FOLDER}.tar.gz" > "${PMF_PACKAGE_FOLDER}.tar.gz.md5"

# Build ZIP Package
zip -r "${PMF_PACKAGE_FOLDER}.zip" "${PMF_PACKAGE_FOLDER}"
md5sum "${PMF_PACKAGE_FOLDER}.zip" > "${PMF_PACKAGE_FOLDER}.zip.md5"

# Remove the code folder
rm -rf "${PMF_PACKAGE_FOLDER}"

# Back to the folder from which the script was called
cd "${CWD}"
