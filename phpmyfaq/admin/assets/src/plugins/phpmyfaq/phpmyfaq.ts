/**
 * Jodit Editor plugin to fetch and insert internal links via REST
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-04
 */

import { Jodit } from 'jodit';
import { fetchFaqsByAutocomplete } from '../../api';
import phpmyfaq from './phpmyfaq.svg.js';

interface FaqAutocompleteResult {
  url: string;
  question: string;
}

interface FaqAutocompleteResponse {
  success: FaqAutocompleteResult[];
}

Jodit.modules.Icon.set('phpmyfaq', phpmyfaq);

/**
 * Builds one selectable search result. The question and URL come from the database and are
 * assigned as text and property values so they can never be interpreted as markup.
 */
const buildResultOption = (result: FaqAutocompleteResult): HTMLLabelElement => {
  const label = document.createElement('label');
  label.className = 'form-check-label';

  const radio = document.createElement('input');
  radio.className = 'form-check-input';
  radio.type = 'radio';
  radio.name = 'faqURL';
  radio.value = result.url;

  label.append(radio, ` ${result.question}`);

  return label;
};

Jodit.plugins.add('phpMyFAQ', (editor: Jodit): void => {
  // Register the button
  editor.registerButton({
    name: 'phpMyFAQ',
    group: 'insert',
  });

  // Register the command
  editor.registerCommand('phpMyFAQ', (): void => {
    const dialog = editor.dlg({ closeOnClickOverlay: true });

    const content = `<form class="row row-cols-lg-auto g-3 align-items-center m-4">
      <div class="col-12">
        <label class="visually-hidden" for="pmf-search-internal-links">Search</label>
        <input type="text" class="form-control" id="pmf-search-internal-links" placeholder="Search">
      </div>
    </form>
    <div class="m-4" id="pmf-search-results"></div>
    <div class="m-4">
      <button type="button" class="btn btn-primary" id="select-faq-button">Select FAQ</button>
    </div>`;

    dialog
      .setMod('theme', editor.o.theme)
      .setHeader('phpMyFAQ Plugin')
      .setContent(content)
      .setSize(Math.min(900, screen.width), Math.min(640, screen.width));

    dialog.open();

    const searchInput = document.getElementById('pmf-search-internal-links') as HTMLInputElement;
    const resultsContainer = document.getElementById('pmf-search-results') as HTMLDivElement;
    const csrfToken = (document.getElementById('pmf-csrf-token') as HTMLInputElement).value;
    const selectLink = document.getElementById('select-faq-button') as HTMLButtonElement;

    searchInput.addEventListener('keyup', async (): Promise<void> => {
      const query = searchInput.value;
      if (query.length > 0) {
        try {
          const response = (await fetchFaqsByAutocomplete(query, csrfToken)) as FaqAutocompleteResponse;

          resultsContainer.replaceChildren();
          response.success.forEach((result: FaqAutocompleteResult) => {
            resultsContainer.append(buildResultOption(result), document.createElement('br'));
          });
        } catch (error) {
          console.error('Error:', (error as Error).message);
        }
      } else {
        resultsContainer.innerHTML = '';
      }
    });

    selectLink.addEventListener('click', (): void => {
      const selected = document.querySelector('input[name=faqURL]:checked') as HTMLInputElement;
      if (selected) {
        const anchor = document.createElement('a');
        anchor.href = selected.value;
        anchor.textContent = selected.parentNode?.textContent?.trim() || '';
        editor.selection.insertHTML(anchor.outerHTML);
        dialog.close();
      } else {
        alert('Please select an FAQ.');
      }
    });
  });
});
