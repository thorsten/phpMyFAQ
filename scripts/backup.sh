#!/usr/bin/env bash
#
# This is the shell script for backing up the phpmyfaq/ folder with all
# content.
#
# For creating a backup simply run:
#
#   ./scripts/backup.sh
#
# The script will create a backup file with the current date.
#
# This Source Code Form is subject to the terms of the Mozilla Public License,
# v. 2.0. If a copy of the MPL was not distributed with this file, You can
# obtain one at http://mozilla.org/MPL/2.0/.
#
# @package   phpMyFAQ
# @author    Thorsten Rinne <thorsten@phpmyfaq.de>
# @copyright 2015-2016 phpMyFAQ Team
# @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
# @link      https://www.phpmyfaq.de
# @version   2015-12-29

cwd=`pwd`

mkdir -p $cwd/backup

SRCDIR=$cwd/phpmyfaq/
DESTDIR=$cwd/backup/
FILENAME=phpmyfaq-$(date +%-Y%-m%-d)-$(date +%-T).tgz
tar cfzP $DESTDIR$FILENAME -C $SRCDIR*

echo "done.";