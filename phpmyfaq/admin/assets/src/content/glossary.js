/**
 * Glossary administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-05
 */

import { createGlossary, deleteGlossary, getGlossary, updateGlossary } from '../api';
import { addElement } from '../../../../assets/src/utils';
import { pushNotification } from '../utils';

export const handleDeleteGlossary = () => {
  const deleteButtons = document.querySelectorAll('.pmf-admin-delete-glossary');

  if (deleteButtons) {
    deleteButtons.forEach((button) => {
      button.addEventListener('click', async (event) => {
        event.preventDefault();

        const glossaryId = button.getAttribute('data-pmf-glossary-id');
        const csrfToken = button.getAttribute('data-pmf-csrf-token');
        const glossaryLang = button.getAttribute('data-pmf-glossary-language');

        const response = await deleteGlossary(glossaryId, glossaryLang, csrfToken);

        if (response) {
          button.closest('tr').remove();
          pushNotification(response.success);
        }
      });
    });
  }
};

export const handleAddGlossary = () => {
  const saveGlossaryButton = document.getElementById('pmf-admin-glossary-add');
  const modal = document.getElementById('addGlossaryModal');

  if (saveGlossaryButton) {
    saveGlossaryButton.addEventListener('click', async (event) => {
      event.preventDefault();

      const glossaryLanguage = document.getElementById('language').value;
      const glossaryItem = document.getElementById('item').value;
      const glossaryDefinition = document.getElementById('definition').value;
      const csrfToken = document.getElementById('pmf-csrf-token').value;

      const response = await createGlossary(glossaryLanguage, glossaryItem, glossaryDefinition, csrfToken);

      if (response) {
        // Close modal properly using Bootstrap
        const bootstrapModal = bootstrap.Modal.getInstance(modal);
        bootstrapModal.hide();

        // Reset form fields for the next entry
        document.getElementById('item').value = '';
        document.getElementById('definition').value = '';

        const tableBody = document.querySelector('#pmf-admin-glossary-table tbody');
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

export const onOpenUpdateGlossaryModal = () => {
  const updateGlossaryModal = document.getElementById('updateGlossaryModal');

  if (updateGlossaryModal) {
    updateGlossaryModal.addEventListener('show.bs.modal', async (event) => {
      const glossaryId = event.relatedTarget.getAttribute('data-pmf-glossary-id');
      const glossaryLang = event.relatedTarget.getAttribute('data-pmf-glossary-language');
      const response = await getGlossary(glossaryId, glossaryLang);

      document.getElementById('update-id').value = response.id;
      document.getElementById('update-language').value = response.language;
      document.getElementById('update-item').value = response.item;
      document.getElementById('update-definition').value = response.definition;
    });
  }
};

export const handleUpdateGlossary = () => {
  const updateGlossaryButton = document.getElementById('pmf-admin-glossary-update');
  const modal = document.getElementById('updateGlossaryModal');
  const modalBackdrop = document.getElementsByClassName('modal-backdrop fade show');

  if (updateGlossaryButton) {
    updateGlossaryButton.addEventListener('click', async (event) => {
      event.preventDefault();

      const glossaryId = document.getElementById('update-id').value;
      const glossaryLanguage = document.getElementById('update-language').value;
      const glossaryItem = document.getElementById('update-item').value;
      const glossaryDefinition = document.getElementById('update-definition').value;
      const csrfToken = document.getElementById('update-csrf-token').value;

      const response = await updateGlossary(glossaryId, glossaryLanguage, glossaryItem, glossaryDefinition, csrfToken);

      if (response) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        modalBackdrop[0].parentNode.removeChild(modalBackdrop[0]);

        const item = document.querySelector(`#pmf-glossary-id-${glossaryId} td:nth-child(1) a`);
        const definition = document.querySelector(`#pmf-glossary-id-${glossaryId} td:nth-child(2)`);

        item.innerText = glossaryItem;
        definition.innerText = glossaryDefinition;

        pushNotification(response.success);
      }
    });
  }
};
