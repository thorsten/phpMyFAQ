/**
 * Category functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   JavaScript
 * @author    Hamed Ayari <hamed.ayari@maxdome.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2016-03-25
 */

/*global $: false */

$(document).ready(function () {
    'use strict';

    var menuCounter = 0;
    $('.pmf-category-overview').find('li').not('ul:first').each(function () {
        if ($(this).children('ul').length > 0) {
            $(this).addClass('pmf-main-category');
            if ($(this).data('category-level') === 0) {
                $(this).prepend('<i class="fa fa-arrow-circle-o-right"></i> ');
            } else {
                $(this).prepend('<i class="fa fa-circle-o"></i> ');
            }
            var subCat = $(this).find('a:first');
            subCat.addClass('pmf-sub-category');
            ++menuCounter;

            if (subCat.parent('li').children('ul').length > 0) {
                subCat.parent('li').children('ul').addClass('pmf-sub-category-list');
            } else {
                ++menuCounter;
            }
        } else {
            $(this).prepend('<i class="fa fa-circle-o"></i> ');
        }
    });

    if (menuCounter > 50) {
        $('.pmf-main-category').find('ul').hide();
        $('.pmf-sub-category-list').hide();
    } else{
        $('.pmf-main-category').find('ul').show();
    }

    // Toggle
    $('.fa.fa-arrow-circle-o-right').on('click', function (event) {
        event.preventDefault();
        var parentList = $(this).parent('li');
        if(parentList.has( 'ul').length > 0) {
            parentList.find('ul').slideToggle();
        }
    });
});