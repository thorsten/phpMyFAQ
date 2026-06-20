/**
 * Search modal palette (fallback when the inline search bar is absent)
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

import { Modal } from 'bootstrap';
import { addElement, TranslationService } from '../utils';
import { attachAutocomplete } from './autocomplete';

let modalInstance: Modal | null = null;

const buildModal = (): void => {
  const input = addElement('input', {
    type: 'text',
    id: 'pmf-search-modal-input',
    classList: 'form-control form-control-lg',
    name: 'search',
    autocomplete: 'off',
    maxLength: 255,
  }) as HTMLInputElement;

  // Localize the placeholder / accessible label via the translation system.
  // Loading is async; set the labels once translations are available.
  const translator = new TranslationService();
  void translator.loadTranslations(document.documentElement.lang).then((): void => {
    const label = translator.translate('msgSearch');
    input.placeholder = `${label} …`;
    input.setAttribute('aria-label', label);
  });

  const dialog = addElement('div', { classList: 'modal-dialog modal-lg modal-dialog-scrollable' }, [
    addElement('div', { classList: 'modal-content' }, [
      addElement('div', { classList: 'modal-body pmf-search-modal-body' }, [
        addElement('div', { classList: 'search' }, [addElement('i', { classList: 'bi bi-search' }), input]),
      ]),
    ]),
  ]);

  const modalEl = addElement(
    'div',
    {
      classList: 'modal fade',
      id: 'pmf-search-modal',
      tabIndex: -1,
      'aria-hidden': 'true',
    },
    [dialog]
  );

  document.body.appendChild(modalEl);

  attachAutocomplete(input);

  modalEl.addEventListener('shown.bs.modal', (): void => {
    input.focus();
  });

  modalInstance = new Modal(modalEl);
};

export const openSearchModal = (): void => {
  if (modalInstance === null) {
    buildModal();
  }
  modalInstance?.show();
};
