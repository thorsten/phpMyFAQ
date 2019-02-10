/**
 * JavaScript functions for all phpMyFAQ configuration stuff
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
 * @since     2013-11-17
 */

/*global $:false */

$(document).ready(function () {
    'use strict';

    var tabLoaded = false;

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {

        e.preventDefault();

        var target = $(e.target).attr('href');

        $.get('index.php', {
            action: 'ajax',
            ajax: 'config_list',
            conf: target.substr(1)
        }, function (data) {
            $(target).empty().append(data);
        });

        tabLoaded = true;
    });

    if (!tabLoaded) {

        $.get('index.php', {
            action: 'ajax',
            ajax: 'config_list',
            conf: 'main'
        }, function (data) {
            $('#main').empty().append(data);
        });
    }
});