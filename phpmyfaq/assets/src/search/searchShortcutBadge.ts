/**
 * Keyboard shortcut hint badge for the inline search bar
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-06-20
 */

import { getShortcutHintLabel } from '../utils';

export const initSearchShortcutBadge = (): void => {
  const searchHint: HTMLElement | null = document.getElementById('pmf-search-hint');
  if (searchHint === null) {
    return;
  }

  searchHint.textContent = getShortcutHintLabel();

  const searchInput: HTMLInputElement | null = document.getElementById(
    'pmf-search-autocomplete'
  ) as HTMLInputElement | null;

  const toggleHint = (): void => {
    const hide = searchInput !== null && (document.activeElement === searchInput || searchInput.value !== '');
    searchHint.classList.toggle('d-none', hide);
  };

  toggleHint();
  searchInput?.addEventListener('focus', toggleHint);
  searchInput?.addEventListener('blur', toggleHint);
  searchInput?.addEventListener('input', toggleHint);
};
