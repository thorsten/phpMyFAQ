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
import { StopWord } from '../interfaces';
import { fetchByLanguage, postStopWord, removeStopWord } from '../api/stop-words';

const maxCols = 4;

export const handleStopWords = (): void => {
  const stopWordsLanguageSelector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
  const addStopWordInput = document.getElementById('pmf-stop-words-add-input') as HTMLButtonElement;

  if (stopWordsLanguageSelector) {
    stopWordsLanguageSelector.addEventListener('change', async (event) => {
      const selectedLanguage = (event.target as HTMLSelectElement).value as string;
      if ('none' !== selectedLanguage) {
        startLoadingIndicator();
        await fetchStopWordsByLanguage(selectedLanguage);
        addStopWordInput.removeAttribute('disabled');
        stopLoadingIndicator();
      }
    });
    addStopWordInput.addEventListener('click', () => {
      const language = stopWordsLanguageSelector.value as string;
      setContentAndHandler([{ id: -1, lang: language, stopword: '' }]);
    });
  }
};

const fetchStopWordsByLanguage = async (language: string): Promise<void> => {
  try {
    const stopWordsList = (await fetchByLanguage(language)) as unknown as StopWord[];
    setContentAndHandler(stopWordsList);
  } catch (error) {
    const errorMessage = await (error as any).cause.response.json();
    console.error(errorMessage);
  }
};

const setContentAndHandler = (stopWordsList: StopWord[]): void => {
  const stopWordsHtml = buildStopWordsHTML(stopWordsList) as string;
  const stopWordsContainer = document.getElementById('pmf-stopwords-content') as HTMLElement;
  stopWordsContainer.innerHTML = stopWordsHtml;

  const stopWordInputs = document.querySelectorAll('.pmf-stop-word-input') as NodeListOf<HTMLInputElement>;
  if (stopWordInputs) {
    stopWordInputs.forEach((element: HTMLInputElement) => {
      element.addEventListener('keydown', async (event: KeyboardEvent): Promise<void> => {
        await saveStopWordHandleOnEnter(element.id, event);
      });
      element.addEventListener('blur', async (): Promise<void> => {
        await saveStopWord(element.id);
      });
      element.addEventListener('focus', (): void => {
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
  const table: HTMLElement = addElement('table', { classList: 'table table-hover align-middle' });
  let tr: HTMLElement | null = null;
  for (let i: number = 0; i < stopWordData.length; i++) {
    if (i % maxCols === 0) {
      tr = addElement('tr', { id: `stopwords_group_${i}` });
      table.appendChild(tr);
    }

    const elementId: string = buildStopWordInputElementId(stopWordData[i].id, stopWordData[i].lang);

    const td: HTMLElement = addElement('td', { classList: 'align-middle' });
    td.appendChild(buildStopWordInputElement(elementId, stopWordData[i].stopword));
    if (tr) {
      tr.appendChild(td);
    }
  }

  return table.outerHTML;
};

const buildStopWordInputElement = (elementId: string, stopWord: string): HTMLElement => {
  const input: HTMLElement = addElement('input', {
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

const saveStopWordHandleOnEnter = async (elementId: string, event: KeyboardEvent): Promise<void> => {
  const element = document.getElementById(elementId) as HTMLInputElement;
  const key: number = event.charCode || event.keyCode || 0;
  if (key === 13) {
    if (element.value === '') {
      await deleteStopWord(elementId);
    } else {
      element.blur();
    }
  }
};

const saveStopWord = async (elementId: string): Promise<void> => {
  const info = parseStopWordInputElemId(elementId);
  const element = document.getElementById(elementId) as HTMLInputElement;
  const csrfToken: string = (document.getElementById('pmf-csrf-token') as HTMLInputElement).value;

  if (element.getAttribute('data-old-value') !== element.value) {
    try {
      await postStopWord(csrfToken, element.value, info.id, info.lang);
      element.style.borderColor = '#198754';
      element.style.backgroundImage =
        "url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e\")";
      element.style.backgroundRepeat = 'no-repeat';
      element.style.backgroundPosition = 'right calc(0.375em + 0.1875rem) center';
      element.style.backgroundSize = 'calc(0.75em + 0.375rem) calc(0.75em + 0.375rem)';
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
  const csrfToken: string = (document.getElementById('pmf-csrf-token') as HTMLInputElement).value;

  try {
    await removeStopWord(csrfToken, info.id, info.lang);
    element.addEventListener('click', () => (element.style.opacity = '0'));
    element.addEventListener('transitionend', () => element.remove());
  } catch (error) {
    const errorMessage = (await (error as any).cause.response.json()) as string;
    const table = document.querySelector('.table') as HTMLElement;
    table.insertAdjacentElement(
      'beforebegin',
      addElement('div', { classList: 'alert alert-danger', innerText: errorMessage })
    );
  }
};
