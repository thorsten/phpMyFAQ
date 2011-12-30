#!/bin/sh
. scripts/version.sh

tags=`git describe --tags --abbrev=0`
content=`cat docs/CHANGEDFILES`
filelist=`git log --pretty=format:"" --name-only $(git describe --tags --abb$rev=0)..HEAD | sed 's,^phpmyfaq,.,' | sort -u`

cat <<EOF
CHANGED FILES FROM $tags -> $PMF_VERSION
$filelist


$content
EOF
