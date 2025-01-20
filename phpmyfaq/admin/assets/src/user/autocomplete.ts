/**
 * Autocomplete for user management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-23
 */

import autocomplete, { AutocompleteItem } from 'autocompleter';
import { updateUser } from './users';
import { fetchUsers } from '../api';
import { addElement } from '../../../../assets/src/utils';

interface User {
  label: string;
  value: string;
}

type UserSuggestion = User & AutocompleteItem;

document.addEventListener('DOMContentLoaded', () => {
  const autoComplete = document.getElementById('pmf-user-list-autocomplete') as HTMLInputElement;

  if (autoComplete) {
    autocomplete<UserSuggestion>({
      input: autoComplete,
      minLength: 1,
      onSelect: async (item: UserSuggestion, input: HTMLInputElement | HTMLTextAreaElement) => {
        input.value = item.label;
        await updateUser(item.value);
      },
      fetch: async (text: string, callback: (items: UserSuggestion[]) => void) => {
        const match = text.toLowerCase();
        const users = await fetchUsers(match);
        callback(
          users?.filter((n: UserSuggestion) => {
            return n.label.toLowerCase().indexOf(match) !== -1;
          })
        );
      },
      render: (item: UserSuggestion, currentValue: string): HTMLDivElement => {
        const regex = new RegExp(currentValue, 'gi');
        return addElement('div', {
          classList: 'pmf-user-list-result border',
          innerHTML: item.label.replace(regex, (match) => `<strong>${match}</strong>`),
        }) as HTMLDivElement;
      },
      emptyMsg: 'No users found',
    });
  }
});
