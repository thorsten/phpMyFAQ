/**
 * FAQ page
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   JavaScript
 * @author    Thorsten Rinne
 * @copyright 2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2018-01-06
 */

/*global document: false, $: false, saveVoting: false, hljs: false */

/*
 * Voting
 *
 */
$(document).ready(function() {
  'use strict';
  $(function () {
    $('div.pmf-stars > div.pmf-star-rating > span').on('click', function (e) {
      var numStars = $(e.target).data('stars');
      saveVoting('faq', '{{ id }}', numStars, '{{ lang }}');
    });
  });
});

/**
 * HighlightJS
 *
 */
$('pre code').each(function(i, block) {
  'use strict';
  hljs.highlightBlock(block);
});

/**
 * bxSlider
 *
 */
$('.bxslider').bxSlider();