#!/bin/bash
find . -name "*.php" -exec perl -pi -w -e 's#(copyright.*-20)([0-9]{2})#${1}21#;' {} \;
find . -name "*.js" -exec perl -pi -w -e 's#(copyright.*-20)([0-9]{2})#${1}21#;' {} \;
find . -name "*.scss" -exec perl -pi -w -e 's#(copyright.*-20)([0-9]{2})#${1}21#;' {} \;
find . -name "*.html" -exec perl -pi -w -e 's#(copyright.*-20)([0-9]{2})#${1}21#;' {} \;
find . -name "*.md" -exec perl -pi -w -e 's#(copyright.*-20)([0-9]{2})#${1}21#;' {} \;
find . -name "*.sh" -exec perl -pi -w -e 's#(copyright.*-20)([0-9]{2})#${1}21#;' {} \;
