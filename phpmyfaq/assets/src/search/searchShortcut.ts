/**
 * Global keyboard shortcut for search (Cmd+K / Ctrl+K)
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

import { openSearchModal } from './searchModal';

const INLINE_INPUT_ID = 'pmf-search-autocomplete';

let initialized = false;

const isEditableTarget = (target: EventTarget | null): boolean => {
  const element = target instanceof HTMLElement ? target : document.activeElement;
  if (!(element instanceof HTMLElement)) {
    return false;
  }
  if (element.id === INLINE_INPUT_ID) {
    return false;
  }
  const tag = element.tagName;
  return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || element.isContentEditable;
};

export const initSearchShortcut = (): void => {
  if (initialized) {
    return;
  }
  initialized = true;

  document.addEventListener('keydown', (event: KeyboardEvent): void => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
      if (isEditableTarget(event.target)) {
        return;
      }
      event.preventDefault();

      const inlineInput = document.getElementById(INLINE_INPUT_ID) as HTMLInputElement | null;
      if (inlineInput) {
        inlineInput.scrollIntoView?.({ behavior: 'smooth', block: 'center' });
        inlineInput.focus();
      } else {
        openSearchModal();
      }
      return;
    }

    if (event.key === 'Escape') {
      const inlineInput = document.getElementById(INLINE_INPUT_ID) as HTMLInputElement | null;
      if (inlineInput && document.activeElement === inlineInput) {
        inlineInput.blur();
      }
    }
  });
};
