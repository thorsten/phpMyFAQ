/**
 * Handle data for FAQs overview management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-02-26
 */

import { Modal } from 'bootstrap';
import { deleteFaq, fetchAllFaqsByCategory, fetchCategoryTranslations } from '../api';
import { normalizeLanguageCode } from '../utils';
import { addElement, pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { Faq } from '../interfaces';

export const handleFaqOverview = async (): Promise<void> => {
  const collapsedCategories: NodeListOf<Element> = document.querySelectorAll('.accordion-collapse');

  if (collapsedCategories) {
    initializeFilterState();

    collapsedCategories.forEach((category: Element): void => {
      const categoryId = category.getAttribute('data-pmf-categoryId') as string;

      category.addEventListener('hidden.bs.collapse', (): void => {
        clearCategoryTable(categoryId);
      });

      category.addEventListener('shown.bs.collapse', async (): Promise<void> => {
        await refreshCategoryTable(category);
      });
    });
  }
};

/**
 * A filter change must re-fetch every currently expanded category — persisting
 * the value alone would only apply it on the next collapse/expand cycle.
 */
const refreshExpandedCategoryTables = async (): Promise<void> => {
  const expandedCategories: NodeListOf<Element> = document.querySelectorAll('.accordion-collapse.show');
  for (const category of Array.from(expandedCategories)) {
    await refreshCategoryTable(category);
  }
};

const refreshCategoryTable = async (category: Element): Promise<void> => {
  const categoryId = category.getAttribute('data-pmf-categoryId') as string;
  const language = category.getAttribute('data-pmf-language') as string;
  const statusFilter: string = getStatusFilterState();
  const onlyNew: boolean = getNewCheckboxState();

  clearCategoryTable(categoryId);
  const faqs = await fetchAllFaqsByCategory(categoryId, language, statusFilter, onlyNew);
  await populateCategoryTable(categoryId, faqs.faqs, faqs.isAllowedToTranslate);

  // Scope the wiring to this category's freshly built rows — a document-wide query
  // would re-bind the controls of every other expanded category on each refresh,
  // stacking duplicate change listeners.
  const tableBody = document.getElementById(`tbody-category-id-${categoryId}`) as HTMLElement;
  const toggleStickyFaq: NodeListOf<HTMLInputElement> = tableBody.querySelectorAll('.pmf-admin-sticky-faq');
  const toggleStatusFaq: NodeListOf<HTMLSelectElement> = tableBody.querySelectorAll('.pmf-admin-status-faq');
  const translationDropdown: NodeListOf<HTMLElement> = tableBody.querySelectorAll('#dropdownAddNewTranslation');

  translationDropdown.forEach((element: Element): void => {
    element.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();

      const translations = await fetchCategoryTranslations(categoryId);
      const parentElement = element.parentElement;
      if (!parentElement) return;

      const dropdownMenu = parentElement.querySelector('.dropdown-menu') as HTMLElement;
      const faqId = element.getAttribute('data-pmf-faq-id') as string;
      const options: string[] = [];

      dropdownMenu.querySelectorAll('#dropdownTranslation').forEach((link) => {
        options.push((link as HTMLElement).innerText);
      });

      for (const [languageCode] of Object.entries(translations as Record<string, unknown>)) {
        if (languageCode !== language) {
          let displayName;
          try {
            const normalizedCode: string = normalizeLanguageCode(languageCode);
            displayName = new Intl.DisplayNames([language], { type: 'language' }).of(normalizedCode);
          } catch {
            displayName = null;
          }

          if (displayName && !options.includes(`→ ${displayName}`)) {
            const newTranslationLink: HTMLElement = addElement('a', {
              classList: 'dropdown-item',
              id: 'dropdownTranslation',
              href: `./faq/translate/${faqId}/${languageCode}`,
              innerText: `→ ${displayName}`,
            });
            dropdownMenu.appendChild(newTranslationLink);
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

      await saveStickyFlag(categoryId, [faqId], token, target.checked);
    });
  });

  toggleStatusFaq.forEach((element: Element): void => {
    let previousStatus = (element as HTMLSelectElement).value;

    element.addEventListener('change', async (event: Event): Promise<void> => {
      event.preventDefault();

      const target = event.target as HTMLSelectElement;
      const categoryId = target.getAttribute('data-pmf-category-id-status') as string;
      const faqId = target.getAttribute('data-pmf-faq-id') as string;
      const token = target.getAttribute('data-pmf-csrf') as string;

      const succeeded = await saveFaqStatus(categoryId, [faqId], token, target.value);
      if (succeeded) {
        previousStatus = target.value;
        return;
      }

      // A rejected change must not keep pretending in the UI — roll the select back
      // to the last state the server accepted.
      target.value = previousStatus;
    });
  });
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
        const result = await deleteFaq(currentFaqId, currentFaqLanguage, currentToken);
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

const postStatusChange = async (url: string, body: Record<string, unknown>): Promise<boolean> => {
  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(body),
    });

    if (response.ok) {
      const result = await response.json();
      if (result.success) {
        pushNotification(result.success);
        return true;
      }
      pushErrorNotification(result.error);
      return false;
    }

    const errorText = await response.text();
    console.error('Network response was not ok:', errorText);
    pushErrorNotification('Network response was not ok: ' + errorText);
    return false;
  } catch (error: unknown) {
    console.error('Error saving status:', error instanceof Error ? error.message : String(error));
    pushErrorNotification('An error occurred while saving the status.');
    return false;
  }
};

const saveStickyFlag = async (categoryId: string, faqIds: string[], token: string, checked: boolean): Promise<void> => {
  const languageElement = document.getElementById(`sticky_record_${categoryId}_${faqIds[0]}`) as HTMLElement;
  const faqLanguage = languageElement.getAttribute('lang') as string;

  await postStatusChange('./api/faq/sticky', {
    csrf: token,
    categoryId: categoryId,
    faqIds: faqIds,
    faqLanguage: faqLanguage,
    checked: checked,
  });
};

const saveFaqStatus = async (categoryId: string, faqIds: string[], token: string, status: string): Promise<boolean> => {
  const languageElement = document.getElementById(`status_record_${categoryId}_${faqIds[0]}`) as HTMLElement;
  const faqLanguage = languageElement.getAttribute('lang') as string;

  return await postStatusChange('./api/faq/status', {
    csrf: token,
    categoryId: categoryId,
    faqIds: faqIds,
    faqLanguage: faqLanguage,
    status: status,
  });
};

// The tbody carries the translated status labels as data attributes (rendered server-side via
// the `translate` filter, see faq.overview.twig) so a non-English install shows the same
// language in the row controls as in the filter dropdown above them. The English literals are
// only a fallback for the (unexpected) case the attribute is missing.
const readStatusLabels = (tableBody: HTMLElement): Record<Faq['status'], string> => ({
  draft: tableBody.dataset.pmfStatusDraft || 'Draft',
  review: tableBody.dataset.pmfStatusReview || 'In review',
  published: tableBody.dataset.pmfStatusPublished || 'Published',
});

// The tbody carries the translated column-header wording (data-pmf-status-select-label,
// rendered server-side via the `translate` filter, see faq.overview.twig) so the select's
// accessible name matches the visible "status" wording in the current install's language.
const readStatusSelectAriaLabel = (tableBody: HTMLElement): string =>
  tableBody.dataset.pmfStatusSelectLabel || 'FAQ status';

const buildStatusSelect = (
  faq: Faq,
  csrfToken: string,
  options: Faq['status'][],
  statusLabels: Record<Faq['status'], string>,
  ariaLabel: string
): HTMLElement => {
  return addElement(
    'select',
    {
      classList: 'form-select form-select-sm pmf-admin-status-faq',
      'data-pmf-category-id-status': faq.category_id.toString(),
      'data-pmf-faq-id': faq.id.toString(),
      'data-pmf-csrf': csrfToken,
      lang: faq.language,
      id: `status_record_${faq.category_id}_${faq.id.toString()}`,
      'aria-label': ariaLabel,
    },
    options.map((status: Faq['status']) =>
      addElement('option', {
        value: status,
        innerText: statusLabels[status],
        selected: status === faq.status,
      })
    )
  );
};

// A user without the publish right for every category of this FAQ cannot move it into or
// out of "published" — offering that transition would only ever produce a 403. Once the FAQ
// is live, such a user gets a read-only badge instead of a select they cannot act on. The
// badge already renders the status as visible text, so it needs no extra label of its own.
const buildStatusCell = (
  faq: Faq,
  csrfToken: string,
  statusLabels: Record<Faq['status'], string>,
  ariaLabel: string
): HTMLElement => {
  if (faq.isAllowedToPublish) {
    return addElement('td', { classList: 'align-middle' }, [
      buildStatusSelect(faq, csrfToken, ['draft', 'review', 'published'], statusLabels, ariaLabel),
    ]);
  }

  if (faq.status !== 'published') {
    return addElement('td', { classList: 'align-middle' }, [
      buildStatusSelect(faq, csrfToken, ['draft', 'review'], statusLabels, ariaLabel),
    ]);
  }

  return addElement('td', { classList: 'align-middle' }, [
    addElement('span', { classList: 'badge bg-success', innerText: statusLabels[faq.status] }),
  ]);
};

const populateCategoryTable = async (categoryId: string, faqs: Faq[], isAllowedToTranslate: boolean): Promise<void> => {
  const tableBody = document.getElementById(`tbody-category-id-${categoryId}`) as HTMLElement;
  const csrfToken = tableBody.getAttribute('data-pmf-csrf') as string;
  const statusLabels = readStatusLabels(tableBody);
  const statusSelectAriaLabel = readStatusSelectAriaLabel(tableBody);

  faqs.forEach((faq: Faq): void => {
    const row: HTMLTableRowElement = document.createElement('tr');
    row.setAttribute('id', `faq_${faq.id.toString()}_${faq.language}`);

    row.append(
      addElement('td', { classList: 'align-middle text-center' }, [
        addElement('a', {
          classList: 'text-decoration-none',
          href: `./faq/edit/${faq.id.toString()}/${faq.language}`,
          innerText: faq.id.toString(),
        }),
      ])
    );
    row.append(addElement('td', { classList: 'align-middle text-center', innerText: faq.language }));
    row.append(
      addElement('td', { classList: 'align-middle text-center' }, [
        addElement('a', {
          classList: 'text-decoration-none',
          href: `./faq/edit/${faq.id.toString()}/${faq.language}`,
          innerText: faq.solution_id.toString(),
        }),
      ])
    );
    row.append(
      addElement('td', {}, [
        addElement('a', {
          classList: 'text-decoration-none',
          href: `./faq/edit/${faq.id.toString()}/${faq.language}`,
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
          'data-pmf-category-id-sticky': faq.category_id.toString(),
          'data-pmf-faq-id': faq.id.toString(),
          'data-pmf-csrf': csrfToken,
          lang: faq.language,
          id: `sticky_record_${faq.category_id}_${faq.id.toString()}`,
          checked: faq.sticky === 'yes',
        }),
      ])
    );
    row.append(buildStatusCell(faq, csrfToken, statusLabels, statusSelectAriaLabel));
    row.append(
      addElement('td', { classList: 'align-middle text-center' }, [
        addElement('a', { classList: 'btn btn-primary', href: `./faq/edit/${faq.id.toString()}/${faq.language}` }, [
          addElement('i', { classList: 'bi bi-pencil', 'aria-hidden': 'true' }),
        ]),
      ])
    );
    row.append(
      addElement('td', { classList: 'align-middle text-center' }, [
        addElement('a', { classList: 'btn btn-info', href: `./faq/copy/${faq.id.toString()}/${faq.language}` }, [
          addElement('i', { classList: 'bi bi-copy', 'aria-hidden': 'true' }),
        ]),
      ])
    );
    if (isAllowedToTranslate) {
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
                'data-bs-toggle': 'dropdown',
                'aria-haspopup': 'true',
                'aria-expanded': 'false',
                'data-pmf-faq-id': faq.id.toString(),
              },
              [addElement('i', { classList: 'bi bi-globe', 'aria-hidden': 'true' })]
            ),
            addElement('div', { classList: 'dropdown-menu', 'aria-labelledby': 'dropdownAddNewTranslation' }, [
              addElement('a', { classList: 'dropdown-item', id: 'dropdownTranslation', innerText: '' }),
            ]),
          ]),
        ])
      );
    }
    row.append(
      addElement('td', { classList: 'text-center' }, [
        addElement(
          'button',
          {
            classList: 'btn btn-danger pmf-button-delete-faq',
            type: 'button',
            'data-pmf-id': faq.id.toString(),
            'data-pmf-language': faq.language,
            'data-pmf-token': csrfToken,
          },
          [
            addElement('i', {
              classList: 'bi bi-trash',
              'aria-hidden': 'true',
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

const initializeFilterState = (): void => {
  const statusFilter = document.getElementById('pmf-status-filter') as HTMLSelectElement | null;
  const filterForNew = document.getElementById('pmf-checkbox-filter-new') as HTMLInputElement | null;

  const storedStatusState: string | null = localStorage.getItem('pmfStatusFilter');
  const storedNewState: string | null = localStorage.getItem('pmfCheckboxFilterNew');

  if (statusFilter && storedStatusState !== null) {
    statusFilter.value = storedStatusState;
  }

  if (filterForNew && storedNewState !== null) {
    filterForNew.checked = JSON.parse(storedNewState);
  }

  if (statusFilter) {
    statusFilter.addEventListener('change', async (): Promise<void> => {
      localStorage.setItem('pmfStatusFilter', statusFilter.value);
      await refreshExpandedCategoryTables();
    });
  }

  if (filterForNew) {
    filterForNew.addEventListener('change', async (): Promise<void> => {
      localStorage.setItem('pmfCheckboxFilterNew', JSON.stringify(filterForNew.checked));
      await refreshExpandedCategoryTables();
    });
  }
};

// Getter for the status filter state
const getStatusFilterState = (): string => {
  return localStorage.getItem('pmfStatusFilter') ?? '';
};

// Getter for the new checkbox state
const getNewCheckboxState = (): boolean => {
  const storedNewState = localStorage.getItem('pmfCheckboxFilterNew');
  return storedNewState !== null ? JSON.parse(storedNewState) : false;
};
