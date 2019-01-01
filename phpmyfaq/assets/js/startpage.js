/**
 * Tag cloud functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   JavaScript
 * @author    Thorsten Rinne
 * @copyright 2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-01-01
 */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  const classes = [
    'btn btn-primary',
    'btn btn-secondary',
    'btn btn-success',
    'btn btn-info',
    'btn btn-warning',
    'btn btn-default',
    'btn btn-danger'
  ];

  const tagCloud = document.querySelectorAll('.pmf-tag-cloud a');

  tagCloud.forEach((tag) => {
    tag.setAttribute('class', classes[(Math.random()*classes.length)]);
  });
});

