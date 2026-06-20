/**
 * Autocomplete functionality JavaScript part
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-11-23
 */

import autocomplete from 'autocompleter';
import { fetchAutoCompleteData, fetchPopularSearches } from '../api';
import { addElement, TranslationService } from '../utils';
import { Suggestion } from '../interfaces';
import { addRecentSearch, getRecentSearches } from './recentSearches';
import { highlightMatch } from './highlight';

const buildEmptyStateItems = async (translate: (key: string) => string): Promise<Suggestion[]> => {
  const recent: Suggestion[] = getRecentSearches().map(
    (term): Suggestion =>
      ({
        type: 'recent',
        searchTerm: term,
        url: `search.html?search=${encodeURIComponent(term)}`,
        group: translate('msgRecentSearches'),
      }) as Suggestion
  );

  const popular: Suggestion[] = (await fetchPopularSearches()).map(
    (item): Suggestion =>
      ({
        type: 'popular',
        searchTerm: item.searchterm,
        count: Number(item.number),
        url: `search.html?search=${encodeURIComponent(item.searchterm)}`,
        group: translate('msgPopularSearches'),
      }) as Suggestion
  );

  return [...recent, ...popular];
};

const renderItem = (item: Suggestion, currentValue: string, translate: (key: string) => string): HTMLDivElement => {
  if (item.type === 'empty') {
    return addElement('li', { classList: 'list-group-item pmf-search-empty' }, [
      addElement('div', { classList: 'text-muted', innerText: translate('msgNoSearchResults') }),
      addElement('div', { classList: 'fw-bold text-primary' }, [
        addElement('i', { classList: 'bi bi-question-circle me-1' }),
        document.createTextNode(translate('msgAskQuestionInstead')),
      ]),
    ]) as HTMLDivElement;
  }

  if (item.type === 'recent' || item.type === 'popular') {
    const icon = item.type === 'recent' ? 'bi-clock-history' : 'bi-graph-up-arrow';
    const children: Node[] = [
      addElement('span', {}, [
        addElement('i', { classList: `bi ${icon} me-2 text-muted` }),
        document.createTextNode(item.searchTerm ?? ''),
      ]),
    ];
    if (item.type === 'popular' && typeof item.count === 'number' && !Number.isNaN(item.count)) {
      children.push(addElement('span', { classList: 'badge bg-info', innerText: `${item.count}x` }));
    }
    return addElement(
      'li',
      {
        classList: 'list-group-item d-flex justify-content-between align-items-center',
      },
      children
    ) as HTMLDivElement;
  }

  // type === 'result' (or undefined, treated as a result)
  const questionEl = addElement('span', { classList: 'pmf-searched-question' });
  questionEl.appendChild(highlightMatch(item.question ?? '', currentValue));

  return addElement('li', { classList: 'list-group-item d-flex justify-content-between align-items-start' }, [
    addElement('div', { classList: 'ms-2 me-auto' }, [
      addElement('div', { classList: 'fw-bold', innerText: item.category ?? '' }),
      questionEl,
    ]),
  ]) as HTMLDivElement;
};

export const attachAutocomplete = (input: HTMLInputElement): void => {
  const translator = new TranslationService();
  const translate = (key: string): string => translator.translate(key);
  void translator.loadTranslations(document.documentElement.lang);

  autocomplete<Suggestion>({
    debounceWaitMs: 200,
    preventSubmit: undefined,
    disableAutoSelect: false,
    showOnFocus: true,
    input,
    container: addElement('ul', { classList: 'list-group bg-dark' }) as HTMLDivElement,
    fetch: async (searchString: string, update: (items: Suggestion[]) => void): Promise<void> => {
      const query = searchString.trim().toLowerCase();

      if (query === '') {
        update(await buildEmptyStateItems(translate));
        return;
      }

      const results: Suggestion[] = (await fetchAutoCompleteData(query)).map(
        (result): Suggestion =>
          ({
            type: 'result',
            url: result.url,
            question: result.question,
            category: result.category,
          }) as Suggestion
      );

      if (results.length === 0) {
        update([{ type: 'empty', url: `search.html?search=${encodeURIComponent(query)}` } as Suggestion]);
        return;
      }

      update(results);
    },
    onSelect: (item: Suggestion): void => {
      const term = item.type === 'result' || item.type === undefined ? input.value.trim() : (item.searchTerm ?? '');
      addRecentSearch(term);
      window.location.href = item.url;
    },
    render: (item: Suggestion, currentValue: string): HTMLDivElement => renderItem(item, currentValue, translate),
  });
};

export const handleAutoComplete = (): void => {
  const autoCompleteInput = document.getElementById('pmf-search-autocomplete') as HTMLInputElement | null;
  if (autoCompleteInput) {
    attachAutocomplete(autoCompleteInput);
  }
};
