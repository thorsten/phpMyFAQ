/**
 * Comment functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2016-01-11
 */

$(() => {
  'use strict';

  const showLongComment = (id) => {
    $('.comment-more-' + id).removeClass('d-none');
    $('.comment-dots-' + id).addClass('d-none');
    $('.comment-show-more-' + id).addClass('d-none');
  };

  $('.show-comment-form').on('click', (event) => {
    event.preventDefault();
    $('#pmf-create-comment').removeClass('d-none');
    document.getElementById('pmf-create-comment').scrollIntoView();
  });

  $('.pmf-comments-show-more').on('click', function (event) {
    event.preventDefault();
    const commentId = $(this).data('comment-id');
    showLongComment(commentId);
  });
});
