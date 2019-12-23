/**
 * Comment functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2016-01-11
 */

$(() => {
  'use strict';

  const showLongComment = id => {
    $('.comment-more-' + id).removeClass('hide');
    $('.comment-dots-' + id).addClass('hide');
    $('.comment-show-more-' + id).addClass('hide');
  };

  $('.show-comment-form').on('click', event => {
    event.preventDefault();
    $('#pmf-create-comment').removeClass('hide');
    document.getElementById('pmf-create-comment').scrollIntoView();
  });

  $('.pmf-comments-show-more').on('click', event => {
    const commentId = $(this).data('comment-id');
    event.preventDefault();
    showLongComment(commentId);
  });
});
