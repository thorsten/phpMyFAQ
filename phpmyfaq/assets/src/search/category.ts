/**
 * Category selection functionality for the search page
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-07-27
 */

import Choices from 'choices.js';
import { TranslationService } from '../utils';

export const handleCategorySelection = async (): Promise<void> => {
  const element: HTMLElement | null = document.getElementById('pmf-search-category');
  const Translator = new TranslationService();
  const language: string = document.documentElement.lang;
  await Translator.loadTranslations(language);
  if (element) {
    new Choices(element, {
      silent: false,
      items: [],
      choices: [],
      renderChoiceLimit: -1,
      maxItemCount: -1,
      closeDropdownOnSelect: 'auto',
      singleModeForMultiSelect: false,
      addChoices: false,
      addItems: true,
      addItemFilter: (value: string): boolean => !!value && value !== '',
      removeItems: true,
      removeItemButton: false,
      removeItemButtonAlignLeft: false,
      editItems: false,
      allowHTML: false,
      allowHtmlUserInput: false,
      duplicateItemsAllowed: true,
      delimiter: ',',
      paste: true,
      searchEnabled: true,
      searchChoices: true,
      searchFloor: 1,
      searchResultLimit: 4,
      searchFields: ['label', 'value'],
      position: 'auto',
      resetScrollPosition: true,
      shouldSort: true,
      shouldSortItems: false,
      shadowRoot: null,
      placeholder: true,
      placeholderValue: null,
      searchPlaceholderValue: Translator.translate('msgTypeSearchCategories'),
      prependValue: null,
      appendValue: null,
      renderSelectedChoices: 'auto',
      loadingText: Translator.translate('msgLoadingText'),
      noResultsText: Translator.translate('msgNoResultsText'),
      noChoicesText: Translator.translate('msgNoChoicesText'),
      itemSelectText: Translator.translate('msgItemSelectText'),
      uniqueItemText: Translator.translate('msgUniqueItemText'),
      customAddItemText: Translator.translate('msgCustomAddItemText'),
      valueComparer: (value1: string, value2: string): boolean => {
        return value1 === value2;
      },
      classNames: {
        containerOuter: ['choices', 'rounded', 'border', 'border-1', 'bg-white'],
        containerInner: ['choices__inner', 'border', 'border-0', 'bg-white'],
        input: ['choices__input'],
        inputCloned: ['choices__input--cloned'],
        list: ['choices__list'],
        listItems: ['choices__list--multiple'],
        listSingle: ['choices__list--single'],
        listDropdown: ['choices__list--dropdown'],
        item: ['choices__item'],
        itemSelectable: ['choices__item--selectable'],
        itemDisabled: ['choices__item--disabled'],
        itemChoice: ['choices__item--choice'],
        description: ['choices__description'],
        placeholder: ['choices__placeholder'],
        group: ['choices__group'],
        groupHeading: ['choices__heading'],
        button: ['choices__button'],
        activeState: ['is-active'],
        focusState: ['is-focused'],
        openState: ['is-open'],
        disabledState: ['is-disabled'],
        highlightedState: ['is-highlighted'],
        selectedState: ['is-selected'],
        flippedState: ['is-flipped'],
        loadingState: ['is-loading'],
        notice: ['choices__notice'],
        addChoice: ['choices__item--selectable', 'add-choice'],
        noResults: ['has-no-results'],
        noChoices: ['has-no-choices'],
      },
      fuseOptions: {
        includeScore: true,
      },
      labelId: '',
      callbackOnInit: null,
      callbackOnCreateTemplates: null,
      appendGroupInSearch: false,
    });
  }
};
