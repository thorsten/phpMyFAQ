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

import { fetchJson } from './fetch-wrapper';

export const fetchByLanguage = async (language: string): Promise<unknown> => {
  return await fetchJson(`./api/stopwords?language=${language}`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
  });
};

export const postStopWord = async (
  csrf: string,
  stopWord: string,
  stopWordId: number,
  stopWordLanguage: string
): Promise<unknown> => {
  return await fetchJson('./api/stopword/save', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrf: csrf,
      stopWord: stopWord,
      stopWordId: stopWordId,
      stopWordsLang: stopWordLanguage,
    }),
  });
};

export const removeStopWord = async (csrf: string, stopWordId: number, stopWordLanguage: string): Promise<unknown> => {
  return await fetchJson('./api/stopword/delete', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrf: csrf,
      stopWordId: stopWordId,
      stopWordsLang: stopWordLanguage,
    }),
  });
};
