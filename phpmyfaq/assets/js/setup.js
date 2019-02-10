/**
 * Setup functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   JavaScript
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-24
 */

/*global $: false */

$(document).ready(function() {

    'use strict';

    var setupForm = $('.form-horizontal'),
        setupType = $('#sql_type'),
        setupTypeOptions = $('#sql_type option'),
        $dbSqlite = $('#dbsqlite'),
        $dbFull = $('#dbdatafull');

    var addInput = function (event) {

        var current = $(event.currentTarget);
        var tag = current.parent().parent();

        if ('add' === current.attr('data-action')) {
            tag.after(tag.clone().find('input').val('').find('span.input-group-addon').remove().end());
        }

        return false;
    };

    var selectDatabaseSetup = function () {

        switch ($(this).val()) {
            case 'sqlite3':
                $dbSqlite.show().removeClass('hide');
                $dbFull.hide();
                break;
            default:
                $dbSqlite.hide();
                $dbFull.show();
                break;
        }
    };
    
    setupForm.find('a').on('click', addInput);
    setupType.on('change', selectDatabaseSetup);

    if (setupTypeOptions.length === 1 && setupType.val() === 'sqlite3') {
        $dbSqlite.show().removeClass('hide');
        $dbFull.hide();
    }

});