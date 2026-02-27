/**
 * Autocomplete for FAQ management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-12-27
 */

import autocomplete, { AutocompleteItem } from 'autocompleter';
import { fetchFaqsByAutocomplete } from '../api';
import { addElement } from '../../../../assets/src/utils';

interface Faq {
  question: string;
  adminUrl: string;
}

type FaqItem = Faq & AutocompleteItem;

document.addEventListener('DOMContentLoaded', () => {
  const autoComplete = document.getElementById('pmf-faq-overview-search-input') as HTMLInputElement | null;

  if (autoComplete) {
    const csrfToken = (document.getElementById('pmf-csrf-token') as HTMLInputElement).value;
    const debounceDelay = 300;
    let debounceTimer: ReturnType<typeof setTimeout> | undefined;
    let fetchRequestId = 0;

    autocomplete<FaqItem>({
      input: autoComplete,
      minLength: 2,
      onSelect: (item: FaqItem) => {
        window.location.href = item.adminUrl;
      },
      fetch: (text: string, update: (items: FaqItem[]) => void) => {
        if (debounceTimer) {
          clearTimeout(debounceTimer);
        }

        const match = text.toLowerCase();
        const currentRequestId = ++fetchRequestId;

        debounceTimer = setTimeout(async () => {
          const faqs = (await fetchFaqsByAutocomplete(match, csrfToken)) as { success: FaqItem[] };

          if (currentRequestId !== fetchRequestId) {
            return;
          }

          update(
            faqs.success.filter((n: FaqItem) => {
              return n.question.toLowerCase().indexOf(match) !== -1;
            })
          );
        }, debounceDelay);
      },
      render: (item: FaqItem, currentValue: string): HTMLDivElement => {
        const regex = new RegExp(currentValue, 'gi');
        return addElement('div', {
          classList: 'pmf-faq-list-result border',
          innerHTML: item.question.replace(regex, (match) => `<strong>${match}</strong>`),
        }) as HTMLDivElement;
      },
      emptyMsg: 'No users found',
    });
  }
});
