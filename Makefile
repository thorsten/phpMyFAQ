#
# phpMyFAQ build file
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
#
# @package   phpMyFAQ
# @author    Thorsten Rinne <thorsten@phpmyfaq.de>
# @copyright 2012 phpMyFAQ Team
# @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
# @link      http://www.phpmyfaq.de
# @version   2012-02-09
#

#
# Constants
#
CSS = ./phpmyfaq/template/default/css/style.css
LESS = ./vendor/bootstrap/less/bootstrap.less

#
# Update Git submodules
#
submodules:
	git submodule update --init --recursive

#
# Build CSS from .less files
#
