/**
 * Fetch data for category management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-12-28
 */
import { Response } from '../interfaces';

export const fetchCategoryTranslations = async (categoryId: string): Promise<Response | undefined> => {
  try {
    const response = await fetch(`./api/category/translations/${categoryId}`, {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const deleteCategory = async (
  categoryId: string,
  language: string,
  csrfToken: string
): Promise<Response | undefined> => {
  try {
    const response = await fetch(`./api/category/delete`, {
      method: 'DELETE',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        categoryId: categoryId,
        language: language,
        csrfToken: csrfToken,
      }),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const setCategoryTree = async (
  categoryTree: any,
  categoryId: string,
  csrfToken: string
): Promise<Response | undefined> => {
  try {
    const response = await fetch('./api/category/update-order', {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        categoryTree: categoryTree,
        categoryId: categoryId,
        csrfToken: csrfToken,
      }),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};
