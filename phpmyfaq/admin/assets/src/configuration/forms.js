/**
 * Handle Form edit
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <modelrailroader@gmx-topmail.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-03-09
 */
import { pushNotification } from '../utils';
import { Modal } from 'bootstrap';

export const handleFormEdit = () => {
  const forms =  document.getElementById('forms');
  if (forms) {
    // Handle activate checkboxes
    document.querySelectorAll('#active').forEach(function(element) {
      element.addEventListener('change', async (event) => {
        const checked = element.checked ? 1 : 0;
        const response = await fetch('api/forms/activate', {
          method: 'POST',
          headers: {
            Accept: 'application/json, text/plain, */*',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            csrf: element.getAttribute('data-pmf-csrf-token'),
            formid: element.getAttribute('data-pmf-formid'),
            inputid: element.getAttribute('data-pmf-inputid'),
            checked: checked,
          }),
        });

        if (response.ok) {
          const result = await response.json();
          if (result.success) {
            pushNotification(result.success);
          } else {
            console.error(result.error);
          }
        } else {
          throw new Error('Network response was not ok: ', response.text());
        }
      });
    });
    // Handle required checkboxes
    document.querySelectorAll('#required').forEach(function(element) {
      element.addEventListener('change', async (event) => {
        const checked = element.checked ? 1 : 0;
        const response = await fetch('api/forms/required', {
          method: 'POST',
          headers: {
            Accept: 'application/json, text/plain, */*',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            csrf: element.getAttribute('data-pmf-csrf-token'),
            formid: element.getAttribute('data-pmf-formid'),
            inputid: element.getAttribute('data-pmf-inputid'),
            checked: checked,
          }),
        });

        if (response.ok) {
          const result = await response.json();
          if (result.success) {
            pushNotification(result.success);
          } else {
            console.error(result.error);
          }
        } else {
          throw new Error('Network response was not ok: ', response.text());
        }
      });
    });

    // Handle tabs
    const tabAskQuestion = document.getElementById('ask-question-tab');
    const tabAddContent = document.getElementById('add-content-tab');
    const tabContentAskQuestion = document.getElementById('ask-question');
    const tabContentAddContent = document.getElementById('add-content');

    tabAskQuestion.addEventListener('click', function (event) {
      event.preventDefault();
      tabAskQuestion.classList.add('active');
      tabAddContent.classList.remove('active');
      tabContentAskQuestion.classList.add('active');
      tabContentAddContent.classList.remove('active');
    });
    tabAddContent.addEventListener('click', function(event ) {
      event.preventDefault();
      tabAddContent.classList.add('active');
      tabAskQuestion.classList.remove('active');
      tabContentAddContent.classList.add('active');
      tabContentAskQuestion.classList.remove('active');
    });
    document.getElementById('button').addEventListener('click', function () {
      const modal = new Modal(document.getElementById('translations'));
      modal.show();
    });
  }
}

export const handleFormTranslations = () => {
  const table = document.getElementById('formTranslations');
  if (table) {
    // Edit translation
    const editButtons = document.querySelectorAll('#editTranslation');
    editButtons.forEach(function(element) {
      element.addEventListener('click', async () => {
        const lang = element.getAttribute('data-pmf-lang');
        const input = document.getElementById('labelInput_' + lang);
        if (input.disabled) {
          input.disabled = false;
          element.classList.remove('bg-primary');
          element.classList.add('bg-success');
          element.children[0].classList.remove('bi-pencil');
          element.children[0].classList.add('bi-check');
        }
        else {
          input.disabled = true;
          element.classList.add('bg-primary');
          element.classList.remove('bg-success');
          element.children[0].classList.add('bi-pencil');
          element.children[0].classList.remove('bi-check');
          const response = await fetch('api/forms/translation-edit', {
            method: 'POST',
            headers: {
              Accept: 'application/json, text/plain, */*',
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              csrf: element.getAttribute('data-pmf-csrf'),
              formId: element.getAttribute('data-pmf-formId'),
              inputId: element.getAttribute('data-pmf-inputId'),
              lang: element.getAttribute('data-pmf-lang'),
              label: input.value
            }),
          });

          if (response.ok) {
            const result = await response.json();
            if (result.success) {
              pushNotification(result.success);
            } else {
              console.error(result.error);
            }
          } else {
            throw new Error('Network response was not ok: ', response.text());
          }
        }
      });
    });
    // Delete translation
    const deleteButtons = document.querySelectorAll('#deleteTranslation');
    deleteButtons.forEach(function (element) {
      element.addEventListener('click', async () => {
        const response = await fetch('api/forms/translation-delete', {
          method: 'POST',
          headers: {
            Accept: 'application/json, text/plain, */*',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            csrf: element.getAttribute('data-pmf-csrf'),
            formId: element.getAttribute('data-pmf-formId'),
            inputId: element.getAttribute('data-pmf-inputId'),
            lang: element.getAttribute('data-pmf-lang')
          }),
        });

        if (response.ok) {
          const result = await response.json();
          if (result.success) {
            pushNotification(result.success);
            document.getElementById('item_' + element.getAttribute('data-pmf-lang')).remove();
          } else {
            console.error(result.error);
          }
        } else {
          throw new Error('Network response was not ok: ', response.text());
        }
      });
    });
    // Add Translation
    const addTranslationButton = document.getElementById('addTranslation');
    const languageSelect = document.getElementById('languageSelect');
    const translationInput = document.getElementById('translationText');
    addTranslationButton.addEventListener('click', async (event) => {
      event.preventDefault();
      const response = await fetch('api/forms/translation-add', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          csrf: addTranslationButton.getAttribute('data-pmf-csrf'),
          formId: addTranslationButton.getAttribute('data-pmf-formId'),
          inputId: addTranslationButton.getAttribute('data-pmf-inputId'),
          lang: languageSelect.value,
          translation: translationInput.value
        }),
      });

      if (response.ok) {
        const result = await response.json();
        if (result.success) {
          pushNotification(result.success);
          setTimeout(function(){
            window.location.reload();
          }, 3000);
        } else {
          console.error(result.error);
        }
      } else {
        throw new Error('Network response was not ok: ', response.text());
      }
    })
  }
}
