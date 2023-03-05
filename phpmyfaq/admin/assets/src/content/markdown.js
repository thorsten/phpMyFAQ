/**
 * Markdown administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
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
      answer.style.height = parseInt(answerHeight);
    }

    answer.addEventListener('mouseup', (event) => {
      localStorage.setItem('phpmyfaq.answer.height', answer.style.height);
    });
  }

  // handle the Markdown preview
  if (markdownTabs) {
    const tab = document.querySelector('a[data-markdown-tab="preview"]');

    tab.addEventListener('shown.bs.tab', (event) => {
      const preview = document.getElementById('markdown-preview');
      preview.style.height = answer.style.height;

      fetch('index.php?action=ajax&ajax=markdown', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          text: answer.value,
        }),
      })
        .then(async (response) => {
          if (response.ok) {
            return response.json();
          }
          throw new Error('Network response was not ok: ', { cause: { response } });
        })
        .then((response) => {
          preview.innerHTML = response.success;
        })
        .catch(async (error) => {
          const errorMessage = await error.cause.response.json();
          console.error(errorMessage);
        });
    });
  }
};
