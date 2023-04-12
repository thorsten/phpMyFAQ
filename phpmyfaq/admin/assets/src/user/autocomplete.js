/**
 * Autocomplete for user management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-23
 */

import autocomplete from 'autocompleter';
import { updateUser } from './users';
import { fetchUsers } from '../api';
import { addElement } from '../../../../assets/src/utils';

document.addEventListener('DOMContentLoaded', () => {
  const autoComplete = document.getElementById('pmf-user-list-autocomplete');

  if (autoComplete) {
    autocomplete({
      input: autoComplete,
      minLength: 1,
      onSelect: async (item, input) => {
        input.value = item.label;
        await updateUser(item.value);
      },
      fetch: async (text, callback) => {
        const match = text.toLowerCase();
        const users = await fetchUsers(match);
        callback(
          users.filter((n) => {
            return n.label.toLowerCase().indexOf(match) !== -1;
          })
        );
      },
      render: (item, value) => {
        const regex = new RegExp(value, 'gi');
        return addElement('div', {
          classList: 'pmf-user-list-result border',
          innerHTML: item.label.replace(regex, function (match) {
            return `<strong>${match}</strong>`;
          }),
        });
      },
      emptyMsg: 'No users found',
    });
  }
});
