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
 * @copyright 2013-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2013-11-17
 */

/*global $:false */

if (window.jQuery) {

    (function () {

        'use strict';

        $('button.toggleConfig').on('click', function (e) {
            e.preventDefault();
            var configContainer = $('#config' + $(this).data('toggle'));

            if ('hide' === configContainer.attr('class')) {
                $.get('index.php', {
                    action: 'ajax',
                    ajax: 'config_list',
                    conf: $(this).data('toggle').toLowerCase()
                }, function (data) {
                    configContainer.empty().append(data);
                });
                configContainer.fadeIn('slow').removeAttr('class');
            } else {
                configContainer.fadeOut('slow').attr('class', 'hide').empty();
            }
        });
    })();
}
