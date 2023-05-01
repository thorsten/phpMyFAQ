/**
 * Stop word Handling
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-02-28
 */

import { addElement } from '../../../../assets/src/utils';

const maxCols = 4;

export const handleStopWords = () => {
  const stopWordsLanguageSelector = document.getElementById('pmf-stop-words-language-selector');
  const addStopWordInput = document.getElementById('pmf-stop-words-add-input');

  if (stopWordsLanguageSelector) {
    stopWordsLanguageSelector.addEventListener('change', async (event) => {
      const selectedLanguage = event.target.value;
      if ('none' !== selectedLanguage) {
        startLoadingIndicator();
        await fetchStopWordsByLanguage(selectedLanguage);
        addStopWordInput.removeAttribute('disabled');
        stopLoadingIndicator();
      }
    });
    addStopWordInput.addEventListener('click', () => {
      const language = stopWordsLanguageSelector.value;
      setContentAndHandler([{ id: -1, lang: language, stopword: '' }]);
    });
  }
};

/**
 * Load stop words by language, build html and put
 * it into stop words_content container
 * @param language
 */
const fetchStopWordsByLanguage = async (language) => {
  fetch(`index.php?action=ajax&ajax=config&ajaxaction=load_stop_words_by_lang&stopwords_lang=${language}`, {
    method: 'GET',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
  })
    .then(async (response) => {
      if (response.status === 200) {
        return response.json();
      }
      throw new Error('Network response was not ok.');
    })
    .then((response) => {
      setContentAndHandler(response);
    })
    .catch((error) => {
      console.error(error);
    });
};

/**
 * Sets content and handler for stop words
 * @param data
 */
const setContentAndHandler = (data) => {
  const stopWordsHtml = buildStopWordsHTML(data);
  const stopWordsContainer = document.getElementById('pmf-stopwords-content');
  stopWordsContainer.innerHTML = stopWordsHtml;

  const stopWordInputs = document.querySelectorAll('.pmf-stop-word-input');
  if (stopWordInputs) {
    stopWordInputs.forEach((element) => {
      element.addEventListener('keydown', (event) => {
        saveStopWordHandleOnEnter(event.target.id, event);
      });
      element.addEventListener('blur', (event) => {
        saveStopWord(event.target.id);
      });
      element.addEventListener('focus', (event) => {
        saveOldValue(event.target.id);
      });
    });
  }
};

/**
 * Loading indicator starts
 */
const startLoadingIndicator = () => {
  const loadingIndicator = document.getElementById('pmf-stop-words-loading-indicator');
  const startLoading = addElement('i', { classList: 'fa fa-cog fa-spin fa-fw' }, [
    addElement('span', { classList: 'sr-only', innerText: 'Loading...' }),
  ]);
  loadingIndicator.appendChild(startLoading);
};

/**
 * Loading indicator ends
 */
const stopLoadingIndicator = () => {
  const loadingIndicator = document.getElementById('pmf-stop-words-loading-indicator');
  loadingIndicator.innerHTML = '';
};

/**
 * Build complete HTML contents to view and edit stop words
 * @param stopWordData
 * @returns {string|*}
 */
const buildStopWordsHTML = (stopWordData) => {
  if ('object' != typeof stopWordData) {
    return '';
  }
  const table = addElement('table', { classList: 'table table-hover align-middle' });
  let tr;
  for (let i = 0; i < stopWordData.length; i++) {
    if (i % maxCols === 0) {
      tr = addElement('tr', { id: `stopwords_group_${i}` });
      table.appendChild(tr);
    }

    const elementId = buildStopWordInputElementId(stopWordData[i].id, stopWordData[i].lang);

    const td = addElement('td', {
      classList: 'align-middle',
    });
    td.appendChild(buildStopWordInputElement(elementId, stopWordData[i].stopword));
    tr.appendChild(td);
  }

  return table.outerHTML;
};

/**
 * Build stop word input element
 * @param elementId
 * @param stopWord
 * @returns {*}
 */
const buildStopWordInputElement = (elementId, stopWord) => {
  const input = addElement('input', {
    id: elementId || buildStopWordInputElementId(),
    classList: 'form-control form-control-sm pmf-stop-word-input',
    type: 'text',
  });
  input.setAttribute('value', stopWord || '');
  return input;
};

/**
 * Builds an id for the stop word input element
 * @param id
 * @param language
 * @returns {`stopword_${number}_${string}`}
 */
const buildStopWordInputElementId = (id, language) => {
  id = id || -1;
  language = language || document.getElementById('pmf-stop-words-language-selector').value;

  return `stopword_${id}_${language}`;
};

/**
 * Returns the unique ID and the language from the element ID
 * @param elementId
 * @returns {{id, lang}}
 */
const parseStopWordInputElemId = (elementId) => {
  const info = elementId.split('_');
  return { id: info[1], lang: info[2] };
};

/**
 * Handle enter press on a stop word input element
 * @param elementId
 * @param event
 */
const saveStopWordHandleOnEnter = (elementId, event) => {
  const element = document.getElementById(elementId);
  event = event || undefined;

  if (undefined !== event) {
    const key = event.charCode || event.keyCode || 0;
    if (13 === key) {
      if ('' === element.value) {
        deleteStopWord(elementId);
      } else {
        // this blur action will cause saveStopWord() call
        element.blur();
      }
    }
  }
};

const saveStopWord = (elementId) => {
  const info = parseStopWordInputElemId(elementId);
  const element = document.getElementById(elementId);
  const csrfToken = document.getElementById('pmf-csrf-token').value;

  if (element.getAttribute('data-old-value') !== element.value) {
    fetch('index.php?action=ajax&ajax=config&ajaxaction=save_stop_word', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrfToken,
        stopWord: element.value,
        stopWordId: info.id,
        stopWordsLang: info.lang,
      }),
    })
      .then(async (response) => {
        if (response.status === 200) {
          return response.json();
        }
        throw new Error('Network response was not ok.');
      })
      .then((response) => {
        // @todo needs to be improved
        element.style.borderColor = '#198754';
        element.style.backgroundImage =
          "url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e\")";
        element.style.backgroundRepeat = 'no-repeat';
        element.style.backgroundPosition = 'right calc(0.375em + 0.1875rem) center';
        element.style.backgroundSize = 'calc(0.75em + 0.375rem) calc(0.75em + 0.375rem)';
      })
      .catch((error) => {
        const table = document.querySelector('.table');
        table.insertAdjacentElement(
          'beforebegin',
          addElement('div', { classList: 'alert alert-danger', innerText: error })
        );
      });
  } else {
    if (0 > info.id && '' === element.val()) {
      element.remove();
    }
  }
};

/**
 * Save the value of the stop word input element. This is bound on onfocus.
 * @param elementId
 */
const saveOldValue = (elementId) => {
  const element = document.getElementById(elementId);
  element.setAttribute('data-old-value', element.value);
};

/**
 * Handle stop word delete
 * @param elementId
 */
const deleteStopWord = (elementId) => {
  const info = parseStopWordInputElemId(elementId);
  const element = document.getElementById(elementId);
  const csrfToken = document.getElementById('pmf-csrf-token').value;

  fetch('index.php?action=ajax&ajax=config&ajaxaction=delete_stop_word', {
    method: 'DELETE',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrf: csrfToken,
      stopWordId: info.id,
      stopWordsLang: info.lang,
    }),
  })
    .then(async (response) => {
      if (response.status === 200) {
        return response.json();
      }
      throw new Error('Network response was not ok.');
    })
    .then(() => {
      element.addEventListener('click', () => (element.style.opacity = '0'));
      element.addEventListener('transitionend', () => element.remove());
    })
    .catch((error) => {
      const table = document.querySelector('.table');
      table.insertAdjacentElement(
        'beforebegin',
        addElement('div', { classList: 'alert alert-danger', innerText: error })
      );
    });
};
