/**
 * Markdown administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-05
 */

export const handleMarkdownForm = () => {
  const answerHeight = localStorage.getItem('phpmyfaq.answer.height');
  const answer = document.getElementById('answer-markdown');
  const markdownTabs = document.getElementById('markdown-tabs');

  // Store the height of the textarea
  if (answer) {
    if (answerHeight !== 'undefined') {
      answer.style.height = answerHeight;
    }

    answer.addEventListener('mouseup', (event) => {
      localStorage.setItem('phpmyfaq.answer.height', answer.style.height);
    });
  }

  // handle the Markdown preview
  if (markdownTabs) {
    const tab = document.querySelector('a[data-markdown-tab="preview"]');

    tab.addEventListener('shown.bs.tab', async () => {
      const preview = document.getElementById('markdown-preview');
      preview.style.height = answer.style.height;

      try {
        const response = await fetch(window.location.pathname + 'api/content/markdown', {
          method: 'POST',
          headers: {
            Accept: 'application/json, text/plain, */*',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            text: answer.value,
          }),
        });

        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        const responseData = await response.json();
        preview.innerHTML = responseData.success;
      } catch (error) {
        if (error instanceof Error) {
          console.error(error);
        } else {
          console.error('Unknown error:', error);
        }
      }
    });
  }
};
