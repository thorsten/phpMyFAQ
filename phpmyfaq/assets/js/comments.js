/**
 * Comment functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2016-01-11
 */

/*global $:false */

$(function () {
    'use strict';

    /**
     * Show full comment
     *
     * @param id
     */
    var showLongComment = function (id) {
        $('.comment-more-' + id).removeClass('hide');
        $('.comment-dots-' + id).addClass('hide');
        $('.comment-show-more-' + id).addClass('hide');
    };


    //
    // Event listeners
    //
    $('.show-comment-form').on('click', function(event) {
        event.preventDefault();
        $('#pmf-create-comment').removeClass('hide');
        document.getElementById('pmf-create-comment').scrollIntoView();
    });

    $('.pmf-comments-show-more').on('click', function (event) {
        var commentId = $(this).data('comment-id');
        event.preventDefault();
        showLongComment(commentId);
    });

});
