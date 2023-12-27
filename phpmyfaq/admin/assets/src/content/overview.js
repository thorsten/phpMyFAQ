/**
 * Handle data for FAQs overview management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-02-26
 */

import { fetchAllFaqsByCategory } from '../api';
import { addElement } from '../../../../assets/src/utils';

export const handleFaqOverview = async () => {
  const deleteFaqButtons = document.querySelectorAll('.pmf-button-delete-faq');
  const toggleStickyAllFaqs = document.querySelectorAll('.pmf-admin-faqs-all-sticky');
  const toggleStickyFaq = document.querySelectorAll('.pmf-admin-sticky-faq');
  const toggleActiveAllFaqs = document.querySelectorAll('.pmf-admin-faqs-all-active');
  const toggleActiveFaq = document.querySelectorAll('.pmf-admin-active-faq');

  const collapsedCategories = document.querySelectorAll('.accordion-collapse');

  if (collapsedCategories) {
    collapsedCategories.forEach((category) => {
      const categoryId = category.getAttribute('data-pmf-categoryId');
      category.addEventListener('hidden.bs.collapse', () => {
        clearCategoryTable(categoryId);
      });
      category.addEventListener('shown.bs.collapse', async () => {
        const faqs = await fetchAllFaqsByCategory(categoryId);
        populateCategoryTable(categoryId, faqs.faqs);
      });
    });
  }

  if (deleteFaqButtons) {
    deleteFaqButtons.forEach((element) => {
      element.addEventListener('click', (event) => {
        event.preventDefault();

        const faqId = event.target.getAttribute('data-pmf-id');
        const faqLanguage = event.target.getAttribute('data-pmf-language');
        const token = event.target.getAttribute('data-pmf-token');

        if (confirm('Are you sure?')) {
          fetch('./api/faq/delete', {
            method: 'DELETE',
            headers: {
              Accept: 'application/json, text/plain, */*',
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              csrf: token,
              faqId: faqId,
              faqLanguage: faqLanguage,
            }),
          })
            .then(async (response) => {
              if (response.ok) {
                return response.json();
              }
              throw new Error('Network response was not ok: ', { cause: { response } });
            })
            .then((response) => {
              const result = response;
              if (result.success) {
                const faqTableRow = document.getElementById(`record_${faqId}_${faqLanguage}`);
                faqTableRow.remove();
              } else {
                console.error(result.error);
              }
            })
            .catch(async (error) => {
              const errorMessage = await error.cause.response.json();
              console.log(errorMessage);
            });
        }
      });
    });
  }

  if (toggleStickyAllFaqs) {
    toggleStickyAllFaqs.forEach((element) => {
      element.addEventListener('change', (event) => {
        event.preventDefault();

        const categoryId = event.target.getAttribute('data-pmf-category-id');
        const faqIds = [];
        const token = event.target.getAttribute('data-pmf-csrf');

        const checkboxes = document.querySelectorAll('input[type=checkbox]');
        if (checkboxes) {
          checkboxes.forEach((checkbox) => {
            if (checkbox.getAttribute('data-pmf-category-id-sticky') === categoryId) {
              checkbox.checked = element.checked;
              if (checkbox.checked === true) {
                faqIds.push(checkbox.getAttribute('data-pmf-faq-id'));
              }
            }
          });
          saveStatus(categoryId, faqIds, token, event.target.checked, 'sticky');
        }
      });
    });
  }

  if (toggleStickyFaq) {
    toggleStickyFaq.forEach((element) => {
      element.addEventListener('change', (event) => {
        event.preventDefault();

        const categoryId = event.target.getAttribute('data-pmf-category-id-sticky');
        const faqId = event.target.getAttribute('data-pmf-faq-id');
        const token = event.target.getAttribute('data-pmf-csrf');

        saveStatus(categoryId, [faqId], token, event.target.checked, 'sticky');
      });
    });
  }

  if (toggleActiveAllFaqs) {
    toggleActiveAllFaqs.forEach((element) => {
      element.addEventListener('change', (event) => {
        event.preventDefault();

        const categoryId = event.target.getAttribute('data-pmf-category-id');
        const faqIds = [];
        const token = event.target.getAttribute('data-pmf-csrf');

        const checkboxes = document.querySelectorAll('input[type=checkbox]');
        if (checkboxes) {
          checkboxes.forEach((checkbox) => {
            if (checkbox.getAttribute('data-pmf-category-id-active') === categoryId) {
              checkbox.checked = element.checked;
              if (checkbox.checked === true) {
                faqIds.push(checkbox.getAttribute('data-pmf-faq-id'));
              }
            }
          });
          saveStatus(categoryId, faqIds, token, event.target.checked, 'active');
        }
      });
    });
  }

  if (toggleActiveFaq) {
    toggleActiveFaq.forEach((element) => {
      element.addEventListener('change', (event) => {
        event.preventDefault();

        const categoryId = event.target.getAttribute('data-pmf-category-id-active');
        const faqId = event.target.getAttribute('data-pmf-faq-id');
        const token = event.target.getAttribute('data-pmf-csrf');

        saveStatus(categoryId, [faqId], token, event.target.checked, 'active');
      });
    });
  }
};

const saveStatus = (categoryId, faqIds, token, checked, type) => {
  let url;
  const languageElement = document.getElementById(`${type}_record_${categoryId}_${faqIds[0]}`);
  const faqLanguage = languageElement.getAttribute('lang');

  if ('active' === type) {
    url = './api/faq/activate';
  } else {
    url = './api/faq/sticky';
  }

  fetch(url, {
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
  })
    .then(async (response) => {
      if (response.ok) {
        return response.json();
      }
      throw new Error('Network response was not ok: ', { cause: { response } });
    })
    .then((response) => {
      const result = response;
      if (result.success) {
        console.error(result.success);
      } else {
        console.error(result.error);
      }
    })
    .catch(async (error) => {
      console.error(await error.cause.response.json());
    });
};

const populateCategoryTable = (catgoryId, faqs) => {
  const tableBody = document.getElementById(`tbody-category-id-${catgoryId}`);

  faqs.forEach((faq) => {
    const row = document.createElement('tr');

    row.append(
      addElement('td', { classList: 'align-middle text-center' }, [
        addElement('a', { href: `?action=editentry&id=${faq.id}&lang=${faq.language}`, innerText: faq.id }),
      ])
    );
    row.append(addElement('td', { classList: 'align-middle text-center', innerText: faq.language }));
    row.append(
      addElement('td', { classList: 'align-middle text-center' }, [
        addElement('a', { href: `?action=editentry&id=${faq.id}&lang=${faq.language}`, innerText: faq.solution_id }),
      ])
    );
    row.append(
      addElement('td', {}, [
        addElement('a', { href: `?action=editentry&id=${faq.id}&lang=${faq.language}`, innerText: faq.question }),
      ])
    );
    row.append(addElement('td', { classList: 'small', innerText: faq.created }));
    row.append(addElement('td', { innerText: faq.active }));
    row.append(addElement('td', { innerText: faq.sticky }));
    row.append(addElement('td', { innerText: 'actions' }));

    tableBody.appendChild(row);
  });
};

const clearCategoryTable = (catgoryId) => {
  const tableBody = document.getElementById(`tbody-category-id-${catgoryId}`);
  tableBody.innerHTML = '';
};
