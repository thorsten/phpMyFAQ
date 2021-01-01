/**
 * Simple function to calculate the reading time
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2020-01-05
 */

export const calculateReadingTime = () => {
  const wordsPerMinute = 200;
  const answer = document.getElementsByClassName('pmf-faq-body');
  let result = '';

  let textLength = answer[0].innerText.split(' ').length;
  if (textLength > 0) {
    let value = Math.ceil(textLength / wordsPerMinute);
    result = `~${value} min`;
  }

  document.getElementById('pmf-reading-time-minutes').innerText = result;
};
