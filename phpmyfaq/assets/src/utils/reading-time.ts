/**
 * Simple function to calculate the reading time
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-01-05
 */

export const calculateReadingTime = (): void => {
  const wordsPerMinute: number = 200;
  const answer: HTMLCollectionOf<Element> = document.getElementsByClassName('pmf-faq-body');
  let result: string = '';

  if (answer.length > 0) {
    const textLength: number = answer[0].innerHTML.split(' ').length;
    if (textLength > 1) {
      const value: number = Math.ceil(textLength / wordsPerMinute);
      result = `~${value} min`;
    } else {
      result = '0 min';
    }

    const readingTimeElement: HTMLElement | null = document.getElementById('pmf-reading-time-minutes');
    if (readingTimeElement) {
      readingTimeElement.innerText = result;
    }
  }
};
