export const handleFaqOverview = async () => {
  const deleteFaqButtons = document.querySelectorAll('.pmf-button-delete-faq');
  const toggleStickyAllFaqs = document.querySelectorAll('.pmf-admin-faqs-all-sticky');
  const toggleStickyFaq = document.querySelectorAll('.pmf-admin-sticky-faq');
  const toggleActiveAllFaqs = document.querySelectorAll('.pmf-admin-faqs-all-active');
  const toggleActiveFaq = document.querySelectorAll('.pmf-admin-active-faq');

  if (deleteFaqButtons) {
    deleteFaqButtons.forEach((element) => {
      element.addEventListener('click', (event) => {
        event.preventDefault();

        const faqId = event.target.getAttribute('data-pmf-id');
        const faqLanguage = event.target.getAttribute('data-pmf-language');
        const token = event.target.getAttribute('data-pmf-token');

        if (confirm('Are you sure?')) {
          fetch('index.php?action=ajax&ajax=records&ajaxaction=delete_record', {
            method: 'DELETE',
            headers: {
              Accept: 'application/json, text/plain, */*',
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              csrf: token,
              record_id: faqId,
              record_lang: faqLanguage,
            }),
          })
            .then(async (response) => {
              if (response.status === 200) {
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

        console.log(event.target);
        console.log('toggle active faqs');

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
  const languageElement = document.getElementById(`${type}_record_${categoryId}_${faqIds[0]}`);
  const faqLanguage = languageElement.getAttribute('lang');

  fetch(`index.php?action=ajax&ajax=records&ajaxaction=save_${type}_records`, {
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
