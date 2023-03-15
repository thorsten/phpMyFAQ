#!/bin/bash
find . -name "*.php" -exec perl -pi -w -e 's#(copyright.*-20)([0-9]{2})#${1}22#;' {} \;
find . -name "*.js" -exec perl -pi -w -e 's#(copyright.*-20)([0-9]{2})#${1}22#;' {} \;
find . -name "*.scss" -exec perl -pi -w -e 's#(copyright.*-20)([0-9]{2})#${1}22#;' {} \;
find . -name "*.html" -exec perl -pi -w -e 's#(copyright.*-20)([0-9]{2})#${1}22#;' {} \;
find . -name "*.md" -exec perl -pi -w -e 's#(copyright.*-20)([0-9]{2})#${1}22#;' {} \;
find . -name "*.sh" -exec perl -pi -w -e 's#(copyright.*-20)([0-9]{2})#${1}22#;' {} \;
