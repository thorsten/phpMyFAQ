#!/bin/bash
#
# This is the shell script for bumping the copyright year in all files
#
# This Source Code Form is subject to the terms of the Mozilla Public License,
# v. 2.0. If a copy of the MPL was not distributed with this file, You can
# obtain one at https://mozilla.org/MPL/2.0/.
#
# @package   phpMyFAQ
# @author    Florian Anderiasch <florian@phpmyfaq.de>
# @author    Thorsten Rinne <thorsten@phpmyfaq.de>
# @copyright 2012-2026 phpMyFAQ Team
# @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
# @link      https://www.phpmyfaq.de
# @since     2012-03-07

# List of file extensions to process
extensions=("php" "ts" "js" "scss" "html" "twig" "md" "sh")

# List of directories to ignore
ignore_dirs=("libs" "node_modules")

# Build the prune expression
prune_expr=""
for dir in "${ignore_dirs[@]}"; do
    prune_expr+=" -path ./${dir} -prune -o"
done

# Loop through each extension and execute the perl command
for ext in "${extensions[@]}"; do
    find . \( ${prune_expr} -name "*.${ext}" \) -exec perl -pi -w -e 's#(copyright.*-20)([0-9]{2})#${1}26#;' {} \;
done
