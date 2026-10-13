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

import autocomplete, { type AutocompleteItem, type PreventSubmit } from 'autocompleter';
import { fetchFaqsByAutocomplete } from '../api';
import { addElement } from '../../../../assets/src/utils';

interface Faq {
  question: string;
  adminUrl: string;
}

type FaqItem = Faq & AutocompleteItem;

interface SearchResponse {
  success?: FaqItem[];
}

export const escapeRegExp = (value: string): string => {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
};

export const escapeHtml = (value: string): string => {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
};

export const highlightMatch = (question: string, searchTerm: string): string => {
  const escapedQuestion = escapeHtml(question);
  const term = searchTerm.trim();

  if (term === '') {
    return escapedQuestion;
  }

  const regex = new RegExp(escapeRegExp(escapeHtml(term)), 'gi');

  return escapedQuestion.replace(regex, (match: string): string => `<strong>${match}</strong>`);
};

export const initFaqSearchAutocomplete = (): void => {
  const searchInput = document.getElementById('pmf-faq-overview-search-input') as HTMLInputElement | null;

  if (!searchInput) {
    return;
  }

  const csrfToken = (document.getElementById('pmf-csrf-token') as HTMLInputElement | null)?.value ?? '';
  const searchForm = document.getElementById('pmf-faq-search-autocomplete') as HTMLFormElement | null;
  const searchButton = document.getElementById('pmf-faq-overview-search-button') as HTMLButtonElement | null;

  const autocompleteResult = autocomplete<FaqItem>({
    input: searchInput,
    minLength: 1,
    debounceWaitMs: 150,
    // PreventSubmit.Always; the library declares a const enum without a runtime export
    preventSubmit: 1 as PreventSubmit,
    onSelect: (item: FaqItem): void => {
      window.location.href = item.adminUrl;
    },
    fetch: async (text: string, update: (items: FaqItem[]) => void): Promise<void> => {
      const searchTerm = text.trim();

      if (searchTerm === '') {
        update([]);
        return;
      }

      try {
        const response = (await fetchFaqsByAutocomplete(searchTerm, csrfToken)) as SearchResponse | undefined;
        update(response?.success ?? []);
      } catch {
        update([]);
      }
    },
    render: (item: FaqItem, currentValue: string): HTMLDivElement => {
      return addElement('div', {
        classList: 'pmf-faq-list-result border',
        innerHTML: highlightMatch(item.question, currentValue),
      }) as HTMLDivElement;
    },
    emptyMsg: searchInput.dataset.pmfEmptyMsg ?? 'No results found',
  });

  // The overview route only accepts GET; a native form submit must never happen.
  if (searchForm) {
    searchForm.addEventListener('submit', (event: Event): void => {
      event.preventDefault();
      autocompleteResult.fetch();
    });
  }

  if (searchButton) {
    searchButton.addEventListener('click', (): void => {
      searchInput.focus();
      autocompleteResult.fetch();
    });
  }
};

document.addEventListener('DOMContentLoaded', initFaqSearchAutocomplete);
