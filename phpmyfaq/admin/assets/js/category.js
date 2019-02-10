/**
 * JavaScript functions for all FAQ category administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-06-02
 */

/*global $:false */

$(document).ready(function () {
    'use strict';

    $('h4.category-header').click(function () {
        var div = $('#div_' + $(this).data('category-id'));
        if (div.css('display') === 'none') {
            div.fadeIn('fast');
        } else {
            div.fadeOut('fast');
        }
    });
});
