/**
 * Glossary administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-05
 */

import { createGlossary, deleteGlossary, getGlossary, updateGlossary } from '../api';
import { addElement, pushNotification } from '../../../../assets/src/utils';
import { Modal } from 'bootstrap';

export const handleDeleteGlossary = (): void => {
  const deleteButtons: NodeListOf<HTMLButtonElement> = document.querySelectorAll('.pmf-admin-delete-glossary');

  if (deleteButtons) {
    deleteButtons.forEach((button: HTMLButtonElement): void => {
      button.addEventListener('click', async (event: Event): Promise<void> => {
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

  if (saveGlossaryButton) {
    saveGlossaryButton.addEventListener('click', async (event: Event) => {
      event.preventDefault();

      const glossaryLanguage = (document.getElementById('language') as HTMLInputElement).value as string;
      const glossaryItem = (document.getElementById('item') as HTMLInputElement).value as string;
      const glossaryDefinition = (document.getElementById('definition') as HTMLInputElement).value as string;
      const csrfToken = (document.getElementById('pmf-csrf-token') as HTMLInputElement).value as string;

      const response = await createGlossary(glossaryLanguage, glossaryItem, glossaryDefinition, csrfToken);

      if (response) {
        if (modal) {
          // Close modal properly using Bootstrap
          const bootstrapModal = Modal.getInstance(modal);
          bootstrapModal?.hide();

          // Reset form fields for the next entry
          (document.getElementById('item') as HTMLInputElement).value = '';
          (document.getElementById('definition') as HTMLInputElement).value = '';
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
                'dataset.pmfGlossaryId': glossaryItem,
                'dataset.pmfCsrfToken': '',
                'dataset.pmfGlossaryLanguage': glossaryLanguage,
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
    updateGlossaryModal.addEventListener('show.bs.modal', async (event: Event): Promise<void> => {
      const relatedEvent = event as unknown as { relatedTarget?: Element };
      let triggeredElement =
        (relatedEvent.relatedTarget as HTMLElement | null) ?? (document.activeElement as HTMLElement | null);

      // If focus is on a child element, climb up to the element carrying the data attributes
      if (triggeredElement && !triggeredElement.hasAttribute('data-pmf-glossary-id')) {
        triggeredElement = triggeredElement.closest('[data-pmf-glossary-id]') as HTMLElement | null;
      }

      const glossaryId = triggeredElement?.getAttribute('data-pmf-glossary-id') ?? null;
      const glossaryLang = triggeredElement?.getAttribute('data-pmf-glossary-language') ?? null;

      if (!glossaryId || !glossaryLang) {
        return;
      }

      // Pre-fill hidden fields so they are present even if the API call fails
      (document.getElementById('update-id') as HTMLInputElement).value = glossaryId;
      (document.getElementById('update-language') as HTMLInputElement).value = glossaryLang;

      try {
        const response = await getGlossary(glossaryId, glossaryLang);

        (document.getElementById('update-item') as HTMLInputElement).value = response?.item ?? '';
        (document.getElementById('update-definition') as HTMLInputElement).value = response?.definition ?? '';
      } catch {
        pushNotification('Unable to load glossary item. Please try again.');
      }
    });
  }
};

export const handleUpdateGlossary = (): void => {
  const updateGlossaryButton = document.getElementById('pmf-admin-glossary-update') as HTMLButtonElement | null;
  const modal = document.getElementById('updateGlossaryModal') as HTMLElement | null;

  if (updateGlossaryButton) {
    updateGlossaryButton.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();

      const glossaryId = (document.getElementById('update-id') as HTMLInputElement).value as string;
      const glossaryLanguage = (document.getElementById('update-language') as HTMLInputElement).value as string;
      const glossaryItem = (document.getElementById('update-item') as HTMLInputElement).value as string;
      const glossaryDefinition = (document.getElementById('update-definition') as HTMLInputElement).value as string;
      const csrfToken = (document.getElementById('update-csrf-token') as HTMLInputElement).value as string;

      const response = await updateGlossary(glossaryId, glossaryLanguage, glossaryItem, glossaryDefinition, csrfToken);

      if (response) {
        if (modal) {
          // Close modal properly using Bootstrap
          const bootstrapModal = Modal.getInstance(modal);
          bootstrapModal?.hide();
        }

        const itemLink = document.querySelector(
          `#pmf-glossary-id-${glossaryId} td:nth-child(1) a`
        ) as HTMLElement | null;
        const definitionCell = document.querySelector(
          `#pmf-glossary-id-${glossaryId} td:nth-child(2)`
        ) as HTMLElement | null;

        if (itemLink) {
          itemLink.innerText = glossaryItem;
        }
        if (definitionCell) {
          definitionCell.innerText = glossaryDefinition;
        }

        pushNotification(response.success);
      }
    });
  }
};
