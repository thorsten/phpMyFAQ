/**
 * Fetch data for stop words
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-02-08
 */

export const fetchByLanguage = async (language: string): Promise<unknown> => {
  const response = await fetch(`./api/stopwords?language=${language}`, {
    method: 'GET',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
  });

  return await response.json();
};

export const postStopWord = async (
  csrf: string,
  stopWord: string,
  stopWordId: number,
  stopWordLanguage: string
): Promise<unknown> => {
  const response = await fetch('./api/stopword/save', {
    method: 'POST',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrf: csrf,
      stopWord: stopWord,
      stopWordId: stopWordId,
      stopWordsLang: stopWordLanguage,
    }),
  });

  return await response.json();
};

export const removeStopWord = async (csrf: string, stopWordId: number, stopWordLanguage: string): Promise<unknown> => {
  const response = await fetch('./api/stopword/delete', {
    method: 'POST',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrf: csrf,
      stopWordId: stopWordId,
      stopWordsLang: stopWordLanguage,
    }),
  });

  return await response.json();
};
