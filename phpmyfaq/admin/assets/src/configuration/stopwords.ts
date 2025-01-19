/**
 * Stop word Handling
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-02-28
 */

import { addElement } from '../../../../assets/src/utils';

const maxCols = 4;

interface StopWord {
  id: number;
  lang: string;
  stopword: string;
}

export const handleStopWords = (): void => {
  const stopWordsLanguageSelector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
  const addStopWordInput = document.getElementById('pmf-stop-words-add-input') as HTMLButtonElement;

  if (stopWordsLanguageSelector) {
    stopWordsLanguageSelector.addEventListener('change', async (event) => {
      const selectedLanguage = (event.target as HTMLSelectElement).value;
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

const fetchStopWordsByLanguage = async (language: string): Promise<void> => {
  try {
    const response = await fetch(`./api/stopwords?language=${language}`, {
      method: 'GET',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });

    if (response.ok) {
      const data: StopWord[] = await response.json();
      setContentAndHandler(data);
    } else {
      throw new Error('Network response was not ok');
    }
  } catch (error) {
    const errorMessage = await (error as any).cause.response.json();
    console.error(errorMessage);
  }
};

const setContentAndHandler = (data: StopWord[]): void => {
  const stopWordsHtml = buildStopWordsHTML(data);
  const stopWordsContainer = document.getElementById('pmf-stopwords-content') as HTMLElement;
  stopWordsContainer.innerHTML = stopWordsHtml;

  const stopWordInputs = document.querySelectorAll('.pmf-stop-word-input') as NodeListOf<HTMLInputElement>;
  if (stopWordInputs) {
    stopWordInputs.forEach((element) => {
      element.addEventListener('keydown', (event) => {
        saveStopWordHandleOnEnter(element.id, event);
      });
      element.addEventListener('blur', () => {
        saveStopWord(element.id);
      });
      element.addEventListener('focus', () => {
        saveOldValue(element.id);
      });
    });
  }
};

const startLoadingIndicator = (): void => {
  const loadingIndicator = document.getElementById('pmf-stop-words-loading-indicator') as HTMLElement;
  const startLoading = addElement('i', { classList: 'bi bi-cog bi-spin bi-fw' }, [
    addElement('span', { classList: 'sr-only', innerText: 'Loading...' }),
  ]);
  loadingIndicator.appendChild(startLoading);
};

const stopLoadingIndicator = (): void => {
  const loadingIndicator = document.getElementById('pmf-stop-words-loading-indicator') as HTMLElement;
  loadingIndicator.innerHTML = '';
};

const buildStopWordsHTML = (stopWordData: StopWord[]): string => {
  if (typeof stopWordData !== 'object') {
    return '';
  }
  const table = addElement('table', { classList: 'table table-hover align-middle' });
  let tr: HTMLElement;
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

const buildStopWordInputElement = (elementId: string, stopWord: string): HTMLElement => {
  const input = addElement('input', {
    id: elementId || buildStopWordInputElementId(),
    classList: 'form-control form-control-sm pmf-stop-word-input',
    type: 'text',
  });
  input.setAttribute('value', stopWord || '');
  return input;
};

const buildStopWordInputElementId = (id?: number, language?: string): string => {
  id = id || -1;
  language = language || (document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement).value;

  return `stopword_${id}_${language}`;
};

const parseStopWordInputElemId = (elementId: string): { id: number; lang: string } => {
  const info = elementId.split('_');
  return { id: parseInt(info[1]), lang: info[2] };
};

const saveStopWordHandleOnEnter = (elementId: string, event: KeyboardEvent): void => {
  const element = document.getElementById(elementId) as HTMLInputElement;
  const key = event.charCode || event.keyCode || 0;
  if (key === 13) {
    if (element.value === '') {
      deleteStopWord(elementId);
    } else {
      element.blur();
    }
  }
};

const saveStopWord = async (elementId: string): Promise<void> => {
  const info = parseStopWordInputElemId(elementId);
  const element = document.getElementById(elementId) as HTMLInputElement;
  const csrfToken = (document.getElementById('pmf-csrf-token') as HTMLInputElement).value;

  if (element.getAttribute('data-old-value') !== element.value) {
    try {
      const response = await fetch('./api/stopword/save', {
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
      });

      if (response.ok) {
        element.style.borderColor = '#198754';
        element.style.backgroundImage =
          "url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e\")";
        element.style.backgroundRepeat = 'no-repeat';
        element.style.backgroundPosition = 'right calc(0.375em + 0.1875rem) center';
        element.style.backgroundSize = 'calc(0.75em + 0.375rem) calc(0.75em + 0.375rem)';
      } else {
        throw new Error('Network response was not ok');
      }
    } catch (error) {
      const errorMessage = await (error as any).cause.response.json();
      const table = document.querySelector('.table') as HTMLElement;
      table.insertAdjacentElement(
        'beforebegin',
        addElement('div', { classList: 'alert alert-danger', innerText: errorMessage })
      );
    }
  } else {
    if (info.id < 0 && element.value === '') {
      element.remove();
    }
  }
};

const saveOldValue = (elementId: string): void => {
  const element = document.getElementById(elementId) as HTMLInputElement;
  element.setAttribute('data-old-value', element.value);
};

const deleteStopWord = async (elementId: string): Promise<void> => {
  const info = parseStopWordInputElemId(elementId);
  const element = document.getElementById(elementId) as HTMLInputElement;
  const csrfToken = (document.getElementById('pmf-csrf-token') as HTMLInputElement).value;

  try {
    const response = await fetch('./api/stopword/delete', {
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
    });

    if (response.ok) {
      element.addEventListener('click', () => (element.style.opacity = '0'));
      element.addEventListener('transitionend', () => element.remove());
    } else {
      throw new Error('Network response was not ok');
    }
  } catch (error) {
    const errorMessage = await (error as any).cause.response.json();
    const table = document.querySelector('.table') as HTMLElement;
    table.insertAdjacentElement(
      'beforebegin',
      addElement('div', { classList: 'alert alert-danger', innerText: errorMessage })
    );
  }
};
