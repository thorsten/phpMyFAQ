/**
 * JavaScript functions for checking the file size before upload
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-01-07
 */

/*global $:false */

$(document).ready(function () {
    'use strict';

    $('#fileUpload').change(function () {
        var iSize = ($('#fileUpload')[0].files[0].size / 1024);
        if (iSize / 1024 > 1) {
            if (((iSize / 1024) / 1024) > 1) {
                iSize = (Math.round(((iSize / 1024) / 1024) * 100) / 100);
                $('#filesize').html(iSize + 'GB');
            } else {
                iSize = (Math.round((iSize / 1024) * 100) / 100);
                $('#filesize').html(iSize + 'MB');
            }
        } else {
            iSize = (Math.round(iSize * 100) / 100);
            $('#filesize').html(iSize + 'kB');
        }
    });
});