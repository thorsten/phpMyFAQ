/**
 * Handle data for FAQs overview management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-02-26
 */

import { Modal } from 'bootstrap';
import { deleteFaq, fetchAllFaqsByCategory, fetchCategoryTranslations } from '../api';
import { addElement, pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { CategoryTranslations, FaqResponse, Response } from '../interfaces';

interface Faq {
  id: string;
  language: string;
  solution_id: string;
  question: string;
  created: string;
  category_id: string;
  sticky: string;
  active: string;
}

export const handleFaqOverview = async (): Promise<void> => {
  const collapsedCategories: NodeListOf<Element> = document.querySelectorAll('.accordion-collapse');

  if (collapsedCategories) {
    initializeCheckboxState();

    collapsedCategories.forEach((category: Element): void => {
      const categoryId = category.getAttribute('data-pmf-categoryId') as string;
      const language = category.getAttribute('data-pmf-language') as string;

      category.addEventListener('hidden.bs.collapse', (): void => {
        clearCategoryTable(categoryId);
      });

      category.addEventListener('shown.bs.collapse', async (): Promise<void> => {
        const onlyInactive: boolean = getInactiveCheckboxState();
        const onlyNew: boolean = getNewCheckboxState();

        const faqs = (await fetchAllFaqsByCategory(categoryId, language, onlyInactive, onlyNew)) as FaqResponse;
        await populateCategoryTable(categoryId, faqs.faqs);
        const toggleStickyFaq: NodeListOf<HTMLInputElement> = document.querySelectorAll('.pmf-admin-sticky-faq');
        const toggleActiveFaq: NodeListOf<HTMLInputElement> = document.querySelectorAll('.pmf-admin-active-faq');
        const translationDropdown: NodeListOf<HTMLElement> = document.querySelectorAll('#dropdownAddNewTranslation');

        translationDropdown.forEach((element: Element): void => {
          element.addEventListener('click', async (event: Event): Promise<void> => {
            event.preventDefault();

            const translations = (await fetchCategoryTranslations(categoryId)) as CategoryTranslations;
            const existingLink = element.nextElementSibling?.childNodes[0] as HTMLElement;
            const regionNames = new Intl.DisplayNames([language], { type: 'language' });
            const faqId = element.getAttribute('data-pmf-faq-id') as string;
            const options: string[] = [];

            for (const [languageCode] of Object.entries(translations as Record<string, unknown>)) {
              if (languageCode !== language) {
                document.querySelectorAll('#dropdownTranslation').forEach((link: Element): void => {
                  options.push((link as HTMLElement).innerText);
                });
                if (!options.includes(`→ ${regionNames.of(languageCode)}`)) {
                  const newTranslationLink: HTMLElement = addElement('a', {
                    classList: 'dropdown-item',
                    id: 'dropdownTranslation',
                    href: `./faq/translate/${faqId}/${languageCode}`,
                    innerText: `→ ${regionNames.of(languageCode)}`,
                  });
                  existingLink.insertAdjacentElement('afterend', newTranslationLink);
                }
              }
            }
          });
        });

        toggleStickyFaq.forEach((element: Element): void => {
          element.addEventListener('change', async (event: Event): Promise<void> => {
            event.preventDefault();

            const target = event.target as HTMLInputElement;
            const categoryId = target.getAttribute('data-pmf-category-id-sticky') as string;
            const faqId = target.getAttribute('data-pmf-faq-id') as string;
            const token = target.getAttribute('data-pmf-csrf') as string;

            await saveStatus(categoryId, [faqId], token, target.checked, 'sticky');
          });
        });

        toggleActiveFaq.forEach((element: Element): void => {
          element.addEventListener('change', async (event: Event): Promise<void> => {
            event.preventDefault();

            const target = event.target as HTMLInputElement;
            const categoryId = target.getAttribute('data-pmf-category-id-active') as string;
            const faqId = target.getAttribute('data-pmf-faq-id') as string;
            const token = target.getAttribute('data-pmf-csrf') as string;

            await saveStatus(categoryId, [faqId], token, target.checked, 'active');
          });
        });
      });
    });
  }
};

export const handleDeleteFaqModal = (): void => {
  const deleteFaqModalElement = document.getElementById('deleteFaqConfirmModal') as HTMLElement | null;

  if (deleteFaqModalElement) {
    const deleteFaqModal = new Modal(deleteFaqModalElement);
    const confirmDeleteFaqButton = document.getElementById('confirmDeleteFaqButton') as HTMLButtonElement;
    let currentFaqId: string = '';
    let currentFaqLanguage: string = '';
    let currentToken: string = '';

    document.addEventListener('click', (event: Event): void => {
      const target = event.target as HTMLElement;
      if (target.closest('.pmf-button-delete-faq')) {
        event.preventDefault();
        const deleteButton = target.closest('.pmf-button-delete-faq') as HTMLElement;

        currentFaqId = deleteButton.getAttribute('data-pmf-id') || '';
        currentFaqLanguage = deleteButton.getAttribute('data-pmf-language') || '';
        currentToken = deleteButton.getAttribute('data-pmf-token') || '';

        deleteFaqModal.show();
      }
    });

    confirmDeleteFaqButton.addEventListener('click', async (): Promise<void> => {
      if (!currentFaqId || !currentFaqLanguage || !currentToken) {
        return;
      }

      try {
        const result = (await deleteFaq(currentFaqId, currentFaqLanguage, currentToken)) as Response;
        if (result.success) {
          const faqTableRow = document.getElementById(`faq_${currentFaqId}_${currentFaqLanguage}`) as HTMLElement;
          if (faqTableRow) {
            faqTableRow.remove();
          }
          pushNotification(result.success);
        }
      } catch (error) {
        console.error(error);
        pushErrorNotification('Fehler beim Löschen der FAQ');
      }

      deleteFaqModal.hide();
      currentFaqId = '';
      currentFaqLanguage = '';
      currentToken = '';
    });
  }
};

const saveStatus = async (
  categoryId: string,
  faqIds: string[],
  token: string,
  checked: boolean,
  type: 'active' | 'sticky'
): Promise<void> => {
  let url;
  const languageElement = document.getElementById(`${type}_record_${categoryId}_${faqIds[0]}`) as HTMLElement;
  const faqLanguage = languageElement.getAttribute('lang') as string;

  if ('active' === type) {
    url = './api/faq/activate';
  } else {
    url = './api/faq/sticky';
  }

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: token,
        categoryId: categoryId,
        faqIds: faqIds,
        faqLanguage: faqLanguage,
        checked: checked,
      }),
    });

    if (response.ok) {
      const result = await response.json();
      if (result.success) {
        pushNotification(result.success);
      } else {
        pushErrorNotification(result.error);
      }
    } else {
      throw new Error('Network response was not ok: ' + (await response.text()));
    }
  } catch (error: any) {
    console.error(await error.cause.response.json());
  }
};

const populateCategoryTable = async (categoryId: string, faqs: Faq[]): Promise<void> => {
  const tableBody = document.getElementById(`tbody-category-id-${categoryId}`) as HTMLElement;
  const csrfToken = tableBody.getAttribute('data-pmf-csrf') as string;

  faqs.forEach((faq: Faq): void => {
    const row: HTMLTableRowElement = document.createElement('tr');
    row.setAttribute('id', `faq_${faq.id}_${faq.language}`);

    row.append(
      addElement('td', { classList: 'align-middle text-center' }, [
        addElement('a', {
          classList: 'text-decoration-none',
          href: `./faq/edit/${faq.id}/${faq.language}`,
          innerText: faq.id,
        }),
      ])
    );
    row.append(addElement('td', { classList: 'align-middle text-center', innerText: faq.language }));
    row.append(
      addElement('td', { classList: 'align-middle text-center' }, [
        addElement('a', {
          classList: 'text-decoration-none',
          href: `./faq/edit/${faq.id}/${faq.language}`,
          innerText: faq.solution_id,
        }),
      ])
    );
    row.append(
      addElement('td', {}, [
        addElement('a', {
          classList: 'text-decoration-none',
          href: `./faq/edit/${faq.id}/${faq.language}`,
          innerText: faq.question,
        }),
      ])
    );
    row.append(addElement('td', { classList: 'small', innerText: faq.created }));
    row.append(
      addElement('td', { classList: 'align-middle' }, [
        addElement('input', {
          classList: 'form-check-input pmf-admin-sticky-faq',
          type: 'checkbox',
          'data-pmfCategoryIdSticky': faq.category_id,
          'data-pmfFaqId': faq.id,
          'data-pmfCsrf': csrfToken,
          lang: faq.language,
          id: `sticky_record_${faq.category_id}_${faq.id}`,
          checked: faq.sticky === 'yes',
        }),
      ])
    );
    row.append(
      addElement('td', { classList: 'align-middle' }, [
        addElement('input', {
          classList: 'form-check-input pmf-admin-active-faq',
          type: 'checkbox',
          'data-pmfCategoryIdActive': faq.category_id,
          'data-pmfFaqId': faq.id,
          'data-pmfCsrf': csrfToken,
          lang: faq.language,
          id: `active_record_${faq.category_id}_${faq.id}`,
          checked: faq.active === 'yes',
        }),
      ])
    );
    row.append(
      addElement('td', { classList: 'align-middle text-center' }, [
        addElement('a', { classList: 'btn btn-primary', href: `./faq/edit/${faq.id}/${faq.language}` }, [
          addElement('i', { classList: 'bi bi-pencil', 'aria-hidden': 'true' }),
        ]),
      ])
    );
    row.append(
      addElement('td', { classList: 'align-middle text-center' }, [
        addElement('a', { classList: 'btn btn-info', href: `./faq/copy/${faq.id}/${faq.language}` }, [
          addElement('i', { classList: 'bi bi-copy', 'aria-hidden': 'true' }),
        ]),
      ])
    );
    row.append(
      addElement('td', { classList: 'align-middle text-center' }, [
        addElement('div', { classList: 'checkbox' }, [
          addElement(
            'a',
            {
              classList: 'btn btn-secondary dropdown-toggle',
              href: '#',
              role: 'button',
              id: 'dropdownAddNewTranslation',
              'data-bsToggle': 'dropdown',
              'aria-haspopup': 'true',
              'aria-expanded': 'false',
              'data-pmfFaqId': faq.id,
            },
            [addElement('i', { classList: 'bi bi-globe', 'aria-hidden': 'true' })]
          ),
          addElement('div', { classList: 'dropdown-menu', 'aria-labelledby': 'dropdownAddNewTranslation' }, [
            addElement('a', { classList: 'dropdown-item', id: 'dropdownTranslation', innerText: 'n/a' }),
          ]),
        ]),
      ])
    );
    row.append(
      addElement('td', { classList: 'text-center' }, [
        addElement(
          'button',
          {
            classList: 'btn btn-danger pmf-button-delete-faq',
            type: 'button',
            'data-pmfId': faq.id,
            'data-pmfLanguage': faq.language,
            'data-pmfToken': csrfToken,
          },
          [
            addElement('i', {
              classList: 'bi bi-trash',
              'aria-hidden': 'true',
              'data-pmfId': faq.id,
              'data-pmfLanguage': faq.language,
              'data-pmfToken': csrfToken,
            }),
          ]
        ),
      ])
    );

    tableBody.appendChild(row);
  });
};

const clearCategoryTable = (categoryId: string): void => {
  const tableBody = document.getElementById(`tbody-category-id-${categoryId}`) as HTMLElement;
  tableBody.innerHTML = '';
};

const initializeCheckboxState = (): void => {
  const filterForInactive = document.getElementById('pmf-checkbox-filter-inactive') as HTMLInputElement | null;
  const filterForNew = document.getElementById('pmf-checkbox-filter-new') as HTMLInputElement | null;

  const storedInactiveState: string | null = localStorage.getItem('pmfCheckboxFilterInactive');
  const storedNewState: string | null = localStorage.getItem('pmfCheckboxFilterNew');

  if (filterForInactive && storedInactiveState !== null) {
    filterForInactive.checked = JSON.parse(storedInactiveState);
  }

  if (filterForNew && storedNewState !== null) {
    filterForNew.checked = JSON.parse(storedNewState);
  }

  if (filterForInactive) {
    filterForInactive.addEventListener('change', (): void => {
      localStorage.setItem('pmfCheckboxFilterInactive', JSON.stringify(filterForInactive.checked));
    });
  }

  if (filterForNew) {
    filterForNew.addEventListener('change', (): void => {
      localStorage.setItem('pmfCheckboxFilterNew', JSON.stringify(filterForNew.checked));
    });
  }
};

// Getter for the inactive checkbox state
const getInactiveCheckboxState = (): boolean => {
  const storedInactiveState = localStorage.getItem('pmfCheckboxFilterInactive');
  return storedInactiveState !== null ? JSON.parse(storedInactiveState) : false;
};

// Getter for the new checkbox state
const getNewCheckboxState = (): boolean => {
  const storedNewState = localStorage.getItem('pmfCheckboxFilterNew');
  return storedNewState !== null ? JSON.parse(storedNewState) : false;
};
