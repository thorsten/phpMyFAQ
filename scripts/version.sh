#!/bin/sh
# Check if PMF_VERSION is not set or empty
if [ -z "${PMF_VERSION}" ]; then
    PMF_VERSION="4.1.0-alpha.3"
fi
