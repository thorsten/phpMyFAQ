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
# obtain one at https://mozilla.org/MPL/2.0/.
#
# @package   phpMyFAQ
# @author    Thorsten Rinne <thorsten@phpmyfaq.de>
# @copyright 2015-2025 phpMyFAQ Team
# @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
# @link      https://www.phpmyfaq.de
# @version   2015-12-29

current_dir=$(pwd)

mkdir -p "$current_dir/backup"

source_dir="$current_dir/phpmyfaq/"
backup_dir="$current_dir/backup/"
filename="phpmyfaq-$(date +%Y-%m-%d)-$(date +%T).tgz"

if tar cfzP "$backup_dir$filename" -C "$source_dir" .; then
    echo "Backup created successfully: $backup_dir$filename"
else
    echo "Error: Failed to create backup" >&2
    exit 1
fi

echo "done."
