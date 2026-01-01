#!/bin/sh
#
# This is the shell script for building a docker image and pushes it
# to the Github registry
#
# This Source Code Form is subject to the terms of the Mozilla Public License,
# v. 2.0. If a copy of the MPL was not distributed with this file, You can
# obtain one at https://mozilla.org/MPL/2.0/.
#
# @package   phpMyFAQ
# @author    Thorsten Rinne <thorsten@phpmyfaq.de>
# @copyright 2019-2026 phpMyFAQ Team
# @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
# @link      https://www.phpmyfaq.de
# @since     2019-11-09

# phpMyFAQ Version
. scripts/version.sh

# Build docker image
docker build -t phpmyfaq/phpmyfaq:${PMF_VERSION} .

# Tag the Docker image
docker tag phpmyfaq/phpmyfaq:${PMF_VERSION} docker.pkg.github.com/thorsten/phpmyfaq/phpmyfaq:${PMF_VERSION}

# Push the Docker image to Github Registry
docker push docker.pkg.github.com/thorsten/phpmyfaq/phpmyfaq:${PMF_VERSION}
