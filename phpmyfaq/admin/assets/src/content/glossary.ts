/**
 * Glossary administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-05
 */

import { createGlossary, deleteGlossary, getGlossary, updateGlossary } from '../api';
import { addElement, pushNotification } from '../../../../assets/src/utils';

export const handleDeleteGlossary = (): void => {
  const deleteButtons = document.querySelectorAll('.pmf-admin-delete-glossary');

  if (deleteButtons) {
    deleteButtons.forEach((button) => {
      button.addEventListener('click', async (event: Event) => {
        event.preventDefault();

        const glossaryId = button.getAttribute('data-pmf-glossary-id') as string;
        const csrfToken = button.getAttribute('data-pmf-csrf-token') as string;
        const glossaryLang = button.getAttribute('data-pmf-glossary-language') as string;

        const response = await deleteGlossary(glossaryId, glossaryLang, csrfToken);

        if (response) {
          (button.closest('tr') as HTMLElement).remove();
          pushNotification(response.success);
        }
      });
    });
  }
};

export const handleAddGlossary = (): void => {
  const saveGlossaryButton = document.getElementById('pmf-admin-glossary-add') as HTMLButtonElement | null;
  const modal = document.getElementById('addGlossaryModal') as HTMLElement | null;
  const modalBackdrop = document.getElementsByClassName('modal-backdrop fade show');

  if (saveGlossaryButton) {
    saveGlossaryButton.addEventListener('click', async (event: Event) => {
      event.preventDefault();

      const glossaryLanguage = (document.getElementById('language') as HTMLInputElement).value;
      const glossaryItem = (document.getElementById('item') as HTMLInputElement).value;
      const glossaryDefinition = (document.getElementById('definition') as HTMLInputElement).value;
      const csrfToken = (document.getElementById('pmf-csrf-token') as HTMLInputElement).value;

      const response = await createGlossary(glossaryLanguage, glossaryItem, glossaryDefinition, csrfToken);

      if (response) {
        if (modal) {
          modal.style.display = 'none';
          modal.classList.remove('show');
        }
        if (modalBackdrop[0]) {
          modalBackdrop[0].parentNode?.removeChild(modalBackdrop[0]);
        }

        const tableBody = document.querySelector('#pmf-admin-glossary-table tbody') as HTMLElement;
        const row = addElement('tr', {}, [
          addElement('td', { innerText: glossaryItem }),
          addElement('td', { innerText: glossaryDefinition }),
          addElement('td', { classList: 'text-end' }, [
            addElement(
              'button',
              {
                classList: 'btn btn-danger pmf-admin-delete-glossary',
                'data-pmfGlossaryId': glossaryItem,
                'data-pmfCsrfToken': '',
                type: 'button',
                innerText: 'Delete',
              },
              [addElement('i', { class: 'bi bi-trash' })]
            ),
          ]),
        ]);
        tableBody.appendChild(row);
        pushNotification(response.success);
      }
    });
  }
};

export const onOpenUpdateGlossaryModal = (): void => {
  const updateGlossaryModal = document.getElementById('updateGlossaryModal') as HTMLElement | null;

  if (updateGlossaryModal) {
    updateGlossaryModal.addEventListener('show.bs.modal', async (event: Event) => {
      const target = event.target as HTMLElement;
      const glossaryId = target.getAttribute('data-pmf-glossary-id') as string;
      const glossaryLang = target.getAttribute('data-pmf-glossary-language') as string;
      const response = await getGlossary(glossaryId, glossaryLang);

      (document.getElementById('update-id') as HTMLInputElement).value = response.id;
      (document.getElementById('update-language') as HTMLInputElement).value = response.language;
      (document.getElementById('update-item') as HTMLInputElement).value = response.item;
      (document.getElementById('update-definition') as HTMLInputElement).value = response.definition;
    });
  }
};

export const handleUpdateGlossary = (): void => {
  const updateGlossaryButton = document.getElementById('pmf-admin-glossary-update') as HTMLButtonElement | null;
  const modal = document.getElementById('updateGlossaryModal') as HTMLElement | null;
  const modalBackdrop = document.getElementsByClassName('modal-backdrop fade show');

  if (updateGlossaryButton) {
    updateGlossaryButton.addEventListener('click', async (event: Event) => {
      event.preventDefault();

      const glossaryId = (document.getElementById('update-id') as HTMLInputElement).value;
      const glossaryLanguage = (document.getElementById('update-language') as HTMLInputElement).value;
      const glossaryItem = (document.getElementById('update-item') as HTMLInputElement).value;
      const glossaryDefinition = (document.getElementById('update-definition') as HTMLInputElement).value;
      const csrfToken = (document.getElementById('update-csrf-token') as HTMLInputElement).value;

      const response = await updateGlossary(glossaryId, glossaryLanguage, glossaryItem, glossaryDefinition, csrfToken);

      if (response) {
        if (modal) {
          modal.style.display = 'none';
          modal.classList.remove('show');
        }
        if (modalBackdrop[0]) {
          modalBackdrop[0].parentNode?.removeChild(modalBackdrop[0]);
        }

        const item = document.querySelector(`#pmf-glossary-id-${glossaryId} td:nth-child(1) a`) as HTMLElement;
        const definition = document.querySelector(`#pmf-glossary-id-${glossaryId} td:nth-child(2)`) as HTMLElement;

        item.innerText = glossaryItem;
        definition.innerText = glossaryDefinition;

        pushNotification(response.success);
      }
    });
  }
};
