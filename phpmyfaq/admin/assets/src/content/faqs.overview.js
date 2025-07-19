/**
 * Handle data for FAQs overview management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-02-26
 */

import { deleteFaq, fetchAllFaqsByCategory, fetchCategoryTranslations } from '../api';
import { normalizeLanguageCode, pushErrorNotification, pushNotification } from '../utils';
import { addElement } from '../../../../assets/src/utils';

export const handleFaqOverview = async () => {
  const collapsedCategories = document.querySelectorAll('.accordion-collapse');

  if (collapsedCategories) {
    initializeCheckboxState();

    collapsedCategories.forEach((category) => {
      const categoryId = category.getAttribute('data-pmf-categoryId');
      const language = category.getAttribute('data-pmf-language');

      category.addEventListener('hidden.bs.collapse', () => {
        clearCategoryTable(categoryId);
      });

      category.addEventListener('shown.bs.collapse', async () => {
        const onlyInactive = getInactiveCheckboxState();
        const onlyNew = getNewCheckboxState();

        const faqs = await fetchAllFaqsByCategory(categoryId, language, onlyInactive, onlyNew);
        await populateCategoryTable(categoryId, faqs.faqs);
        const deleteFaqButtons = document.querySelectorAll('.pmf-button-delete-faq');
        const toggleStickyFaq = document.querySelectorAll('.pmf-admin-sticky-faq');
        const toggleActiveFaq = document.querySelectorAll('.pmf-admin-active-faq');
        const translationDropdown = document.querySelectorAll('#dropdownAddNewTranslation');

        deleteFaqButtons.forEach((element) => {
          element.addEventListener('click', async (event) => {
            event.preventDefault();

            const faqId = event.target.getAttribute('data-pmf-id');
            const faqLanguage = event.target.getAttribute('data-pmf-language');
            const token = event.target.getAttribute('data-pmf-token');

            if (confirm('Are you sure?')) {
              try {
                const result = await deleteFaq(faqId, faqLanguage, token);
                if (result.success) {
                  const faqTableRow = document.getElementById(`faq_${faqId}_${faqLanguage}`);
                  faqTableRow.remove();
                }
              } catch (error) {
                console.error(error);
              }
            }
          });
        });

        translationDropdown.forEach((element) => {
          element.addEventListener('click', async (event) => {
            event.preventDefault();

            const translations = await fetchCategoryTranslations(categoryId);
            const dropdownMenu = element.parentElement.querySelector('.dropdown-menu');
            const faqId = element.getAttribute('data-pmf-faq-id');
            let options = [];

            dropdownMenu.querySelectorAll('#dropdownTranslation').forEach((link) => {
              options.push(link.innerText);
            });

            for (let [languageCode, categoryName] of Object.entries(translations)) {
              if (languageCode !== language) {
                let displayName;
                try {
                  const normalizedCode = normalizeLanguageCode(languageCode);
                  displayName = new Intl.DisplayNames([language], { type: 'language' }).of(normalizedCode);
                } catch (e) {
                  displayName = null;
                }

                if (displayName && !options.includes(`→ ${displayName}`)) {
                  const newTranslationLink = addElement('a', {
                    classList: 'dropdown-item',
                    id: 'dropdownTranslation',
                    href: `?action=editentry&id=${faqId}&cat=${categoryId}&translateTo=${languageCode}`,
                    innerText: `→ ${displayName}`,
                  });
                  dropdownMenu.appendChild(newTranslationLink);
                }
              }
            }
          });
        });

        toggleStickyFaq.forEach((element) => {
          element.addEventListener('change', async (event) => {
            event.preventDefault();

            const categoryId = event.target.getAttribute('data-pmf-category-id-sticky');
            const faqId = event.target.getAttribute('data-pmf-faq-id');
            const token = event.target.getAttribute('data-pmf-csrf');

            await saveStatus(categoryId, [faqId], token, event.target.checked, 'sticky');
          });
        });

        toggleActiveFaq.forEach((element) => {
          element.addEventListener('change', async (event) => {
            event.preventDefault();

            const categoryId = event.target.getAttribute('data-pmf-category-id-active');
            const faqId = event.target.getAttribute('data-pmf-faq-id');
            const token = event.target.getAttribute('data-pmf-csrf');

            await saveStatus(categoryId, [faqId], token, event.target.checked, 'active');
          });
        });
      });
    });
  }
};

const saveStatus = async (categoryId, faqIds, token, checked, type) => {
  let url;
  const languageElement = document.getElementById(`${type}_record_${categoryId}_${faqIds[0]}`);
  const faqLanguage = languageElement.getAttribute('lang');

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
      throw new Error('Network response was not ok: ', response.text());
    }
  } catch (error) {
    console.error(await error.cause.response.json());
  }
};

const populateCategoryTable = async (catgoryId, faqs) => {
  const tableBody = document.getElementById(`tbody-category-id-${catgoryId}`);
  const csrfToken = tableBody.getAttribute('data-pmf-csrf');

  faqs.forEach((faq) => {
    const row = document.createElement('tr');
    row.setAttribute('id', `faq_${faq.id}_${faq.language}`);

    row.append(
      addElement('td', { classList: 'align-middle text-center' }, [
        addElement('a', {
          classList: 'text-decoration-none',
          href: `?action=editentry&id=${faq.id}&lang=${faq.language}`,
          innerText: faq.id,
        }),
      ])
    );
    row.append(addElement('td', { classList: 'align-middle text-center', innerText: faq.language }));
    row.append(
      addElement('td', { classList: 'align-middle text-center' }, [
        addElement('a', {
          classList: 'text-decoration-none',
          href: `?action=editentry&id=${faq.id}&lang=${faq.language}`,
          innerText: faq.solution_id,
        }),
      ])
    );
    row.append(
      addElement('td', {}, [
        addElement('a', {
          classList: 'text-decoration-none',
          href: `?action=editentry&id=${faq.id}&lang=${faq.language}`,
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
          checked: faq.sticky === 'yes' ? 'checked' : '',
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
          checked: faq.active === 'yes' ? 'checked' : '',
        }),
      ])
    );
    row.append(
      addElement('td', { classList: 'align-middle text-center' }, [
        addElement('a', { classList: 'btn btn-primary', href: `?action=editentry&id=${faq.id}&lang=${faq.language}` }, [
          addElement('i', { classList: 'bi bi-pencil', 'aria-hidden': 'true' }),
        ]),
      ])
    );
    row.append(
      addElement('td', { classList: 'align-middle text-center' }, [
        addElement('a', { classList: 'btn btn-info', href: `?action=copyentry&id=${faq.id}&lang=${faq.language}` }, [
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
            addElement('a', { classList: 'dropdown-item', id: 'dropdownTranslation', innerText: '' }),
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

const clearCategoryTable = (categoryId) => {
  const tableBody = document.getElementById(`tbody-category-id-${categoryId}`);
  tableBody.innerHTML = '';
};

const initializeCheckboxState = () => {
  const filterForInactive = document.getElementById('pmf-checkbox-filter-inactive');
  const filterForNew = document.getElementById('pmf-checkbox-filter-new');

  const storedInactiveState = localStorage.getItem('pmfCheckboxFilterInactive');
  const storedNewState = localStorage.getItem('pmfCheckboxFilterNew');

  if (filterForInactive && storedInactiveState !== null) {
    filterForInactive.checked = JSON.parse(storedInactiveState);
  }

  if (filterForNew && storedNewState !== null) {
    filterForNew.checked = JSON.parse(storedNewState);
  }

  if (filterForInactive) {
    filterForInactive.addEventListener('change', () => {
      localStorage.setItem('pmfCheckboxFilterInactive', filterForInactive.checked);
    });
  }

  if (filterForNew) {
    filterForNew.addEventListener('change', () => {
      localStorage.setItem('pmfCheckboxFilterNew', filterForNew.checked);
    });
  }
};

// Getter for the inactive checkbox state
const getInactiveCheckboxState = () => {
  const storedInactiveState = localStorage.getItem('pmfCheckboxFilterInactive');
  return storedInactiveState !== null ? JSON.parse(storedInactiveState) : null;
};

// Getter for the new checkbox state
const getNewCheckboxState = () => {
  const storedNewState = localStorage.getItem('pmfCheckboxFilterNew');
  return storedNewState !== null ? JSON.parse(storedNewState) : null;
};
