/**
 * Fetch data for glossary management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-27
 */

import { Response } from '../interfaces';

export const createGlossary = async (
  language: string,
  item: string,
  definition: string,
  csrfToken: string
): Promise<Response | undefined> => {
  try {
    const response = await fetch('./api/glossary/create', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrfToken,
        language: language,
        item: item,
        definition: definition,
      }),
    });

    if (response.status === 200) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    throw error;
  }
};

export const deleteGlossary = async (
  glossaryId: string,
  glossaryLang: string,
  csrfToken: string
): Promise<Response | undefined> => {
  try {
    const response = await fetch('./api/glossary/delete', {
      method: 'DELETE',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrfToken,
        id: glossaryId,
        lang: glossaryLang,
      }),
    });

    if (response.status === 200) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    throw error;
  }
};

export const getGlossary = async (glossaryId: string, glossaryLanguage: string): Promise<Response | undefined> => {
  try {
    const response = await fetch(`./api/glossary/${glossaryId}/${glossaryLanguage}`, {
      method: 'GET',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });

    if (response.status === 200) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    throw error;
  }
};

export const updateGlossary = async (
  glossaryId: string,
  glossaryLanguage: string,
  item: string,
  definition: string,
  csrfToken: string
): Promise<Response | undefined> => {
  try {
    const response = await fetch('./api/glossary/update', {
      method: 'PUT',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrfToken,
        id: glossaryId,
        lang: glossaryLanguage,
        item: item,
        definition: definition,
      }),
    });

    if (response.status === 200) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    throw error;
  }
};
