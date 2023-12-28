/**
 * Autocomplete for FAQ management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-12-27
 */

import autocomplete from 'autocompleter';
import { fetchFaqsByAutocomplete } from '../api';
import { addElement } from '../../../../assets/src/utils';

document.addEventListener('DOMContentLoaded', () => {
  const autoComplete = document.getElementById('pmf-faq-overview-search-input');

  if (autoComplete) {
    const csrfToken = document.getElementById('pmf-csrf-token').value;
    autocomplete({
      input: autoComplete,
      minLength: 1,
      onSelect: (event) => {
        window.location.href = event.adminUrl;
      },
      fetch: async (text, callback) => {
        const match = text.toLowerCase();
        const faqs = await fetchFaqsByAutocomplete(match, csrfToken);
        callback(
          faqs.success.filter((n) => {
            return n.question.toLowerCase().indexOf(match) !== -1;
          })
        );
      },
      render: (item, value) => {
        const regex = new RegExp(value, 'gi');
        return addElement('div', {
          classList: 'pmf-faq-list-result border',
          innerHTML: item.question.replace(regex, function (match) {
            return `<strong>${match}</strong>`;
          }),
        });
      },
      emptyMsg: 'No users found',
    });
  }
});
