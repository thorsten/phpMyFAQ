#!/bin/sh
#
# This is the shell script for building a docker image tagged as:
# 1. phpmyfaq/phpmyfaq:${PMF_VERSION}
# 2. phpmyfaq/phpmyfaq:
# of phpMyFAQ using tar.gz package build by ./git2package.sh
#
# For creating an image simply run:
#
#   ./git2package.sh
#
# Then:
#
#   ./package2docker.sh
#
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
# @author    Florian Anderiasch <florian@phpmyfaq.de>
# @author    Adrien Estanove <adrien.estanove@gmail.com>
# @copyright 2008-2018 phpMyFAQ Team
# @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
# @link      http://www.phpmyfaq.de
# @version   2018-01-06

# Exit on error and trace execution
set -e

# phpMyFAQ Version
. scripts/version.sh

# Does we use a specific docker binary ?
[[ "x$DOCKERBIN" = "x" ]] && DOCKERBIN="$(which docker)"

# Package release
[[ "x$PMF_PACKAGE_FOLDER" = "x" ]] && PMF_PACKAGE_FOLDER="phpmyfaq-${PMF_VERSION}"

cwd=$( pwd )

# TODO: Execute composer and yarn with docker
# TODO: Replace with own management
if [[ ! -f ${PMF_PACKAGE_FOLDER}.tar.gz ]]; then
    >&2 echo "no package named \"${PMF_PACKAGE_FOLDER}.tar.gz\" found."
    >&2 echo "run git2package.sh script before this one."
    exit 1
fi

# Set docker options
docker_opts=""
[[ "x$NO_CACHE" != "x" ]] && docker_opts="$docker_opts --no-cache"

docker_stdout=""
[[ "x$SILENT" != x ]] && docker_stdout="1> /dev/null"

# Set docker tags
if [[ "x$IMAGENAME" = "x" ]]; then
    IMAGENAME="phpmyfaq"
fi

# Building docker image
$DOCKERBIN build $docker_opts -t $IMAGENAME . $docker_stdout

# Create a temp container from previous image
targetContainer=$( $DOCKERBIN create $IMAGENAME )

# Copying release to the temp container
mkdir -p $cwd/build/package/${PMF_PACKAGE_FOLDER}
tar -xf ${PMF_PACKAGE_FOLDER}.tar.gz -C $cwd/build/package/${PMF_PACKAGE_FOLDER}
mv $cwd/build/package/${PMF_PACKAGE_FOLDER}/phpmyfaq $cwd/build/package/${PMF_PACKAGE_FOLDER}/html

$DOCKERBIN cp $cwd/build/package/${PMF_PACKAGE_FOLDER}/html $targetContainer:/var/www

rm -rf $cwd/build/package/${PMF_PACKAGE_FOLDER}

# Commiting container changes to a new image
$DOCKERBIN commit $targetContainer $IMAGENAME:$PMF_VERSION
$DOCKERBIN rm $targetContainer

echo "Docker image \"$IMAGENAME:$PMF_VERSION\" built succesfully."

# Remote registry management.
# $REGISTRY var must look like [REGSITRY[:PORT]/]NAMESPACE
# If you only set a namespace it's meant your want to push to your docker
# daemon default registry.
if [[ "x$REGISTRY" != "x" ]]; then
    # docker login
    if [[ "$REGISTRY" =~ "/" ]]; then
        $DOCKERBIN login $REGISTRY
    else
        # if no registry spefified pushing to docker.io
        $DOCKERBIN login
    fi

    $DOCKERBIN tag $IMAGENAME:$PMF_VERSION $REGISTRY/$IMAGENAME:$PMF_VERSION
    $DOCKERBIN push $REGISTRY/$IMAGENAME:$PMF_VERSION

    if [[ "x$LATEST" != "x" ]]; then
        $DOCKERBIN tag $IMAGENAME:$PMF_VERSION $REGISTRY/$IMAGENAME:latest
        $DOCKERBIN push $REGISTRY/$IMAGENAME:latest
    fi
    if [[ "x$STABLE" != "x" ]]; then
        $DOCKERBIN tag $IMAGENAME:$PMF_VERSION $REGISTRY/$IMAGENAME:stable
        $DOCKERBIN push $REGISTRY/$IMAGENAME:stable
    fi
fi

echo "done.";
