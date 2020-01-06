/**
 * 3rd party libs for phpMyFAQ
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne
 * @copyright 2019-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2019-12-14
 */

import $ from 'jquery';
window.jQuery = $;
window.$ = $;

require('popper.js');
require('bootstrap');
require('bootstrap-3-typeahead');
require('bootstrap-datepicker');
window.bsCustomFileInput = require('bs-custom-file-input');

require('cookieconsent');
require('handlebars');
