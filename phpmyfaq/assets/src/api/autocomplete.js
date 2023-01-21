/**
 * Autocomplete functionality JavaScript part
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-11-23
 */

import autocomplete from 'autocompleter';
import { addElement } from '../utils';

export const handleAutoComplete = () => {
  const autoCompleteInput = document.getElementById('pmf-search-autocomplete');
  autocomplete({
    debounceWaitMs: 200,
    preventSubmit: true,
    disableAutoSelect: false,
    input: autoCompleteInput,
    container: addElement('ul', { classList: 'list-group bg-dark' }),
    fetch: async (searchString, update) => {
      searchString = searchString.toLowerCase();
      const fetchedData = await fetchAutoCompleteData(searchString);
      const suggestions = fetchedData.filter((item) => item.faqQuestion.search(searchString));
      update(suggestions);
    },
    onSelect: (event) => {
      window.location.href = event.faqLink;
    },
    render: (item, currentValue) => {
      return addElement('li', { classList: 'list-group-item d-flex justify-content-between align-items-start' }, [
        addElement('div', { classList: 'ms-2 me-auto' }, [
          addElement('div', { classList: 'fw-bold', innerText: item.categoryName }),
          addElement('span', { classList: 'pmf-searched-question', textContent: item.faqQuestion }),
        ]),
      ]);
    },
  });
};

const fetchAutoCompleteData = async (searchString) => {
  return await fetch(`api.search.php?search=${searchString}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  })
    .then(async (response) => {
      if (response.status === 200) {
        return response.json();
      }
      throw new Error('Network response was not ok.');
    })
    .then((response) => {
      return response;
    });
};
