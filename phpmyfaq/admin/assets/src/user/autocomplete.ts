/**
 * Autocomplete for user management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-23
 */

import autocomplete, { AutocompleteItem } from 'autocompleter';
import { updateUser } from './users';
import { fetchUsers } from '../api';
import { addElement } from '../../../../assets/src/utils';
import { UserAutocomplete } from '../interfaces';

type UserSuggestion = UserAutocomplete & AutocompleteItem;

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
      fetch: async (text: string, update: (items: UserSuggestion[] | false) => void): Promise<void> => {
        const match: string = text.toLowerCase();
        const users = (await fetchUsers(match)) as unknown as UserSuggestion[] | undefined;
        const list: UserSuggestion[] = (users ?? []).filter((n: UserSuggestion): boolean =>
          n.label.toLowerCase().includes(match)
        );
        update(list);
      },
      render: (item: UserSuggestion, currentValue: string): HTMLDivElement => {
        const safe: string = currentValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(safe, 'gi');
        return addElement('div', {
          classList: 'pmf-user-list-result border',
          innerHTML: item.label.replace(regex, (content: string): string => `<strong>${content}</strong>`),
        }) as HTMLDivElement;
      },
      emptyMsg: 'No users found',
    });
  }
});
