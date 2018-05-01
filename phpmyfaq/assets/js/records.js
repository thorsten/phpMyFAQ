/**
 * FAQ record functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   JavaScript
 * @author    Hamed Ayari <hamed.ayari@maxdome.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2016-03-25
 */

/*global $: false, hljs: false, saveFormValues: false, mermaid: false */

$(document).ready(function () {
    'use strict';

    //
    // Show rating
    //
    var rating = $('#rating span').data('rating');
    if (0 < rating) {
        rating = Math.floor(rating);
        $('.pmf-star-rating').children('span').each(function () {
            if ($(this).data('stars') <= rating) {
                $(this).text('★');
            }
        });
    }

    //
    // Save comments
    //
    $('form#formValues').on('submit', function (e) {
        e.preventDefault();
        saveFormValues('savecomment', 'comment');
        return false;
    });

    //
    // Tooltips
    //
    $('[data-toggle="tooltip"]').tooltip();

    //
    // Initialize Mermaid
    //
    var config = {
        startOnLoad:true,
        arrowMarkerAbsolute:true
    };
    mermaid.initialize(config);
});
