/**
 * Fetch data for FAQs overview management
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
 * @since     2023-12-27
 */

import { Response } from '../interfaces';
import { FaqResponse } from '../interfaces';

export const fetchAllFaqsByCategory = async (
  categoryId: string,
  language: string,
  onlyInactive?: boolean,
  onlyNew?: boolean
): Promise<FaqResponse> => {
  try {
    let currentUrl: string = window.location.protocol + '//' + window.location.host;
    let pathname: string = window.location.pathname;

    if (pathname.endsWith('/faqs')) {
      pathname = pathname.slice(0, -5);
    }

    currentUrl += pathname;
    const url = new URL(`${currentUrl}/api/faqs/${categoryId}/${language}`);
    if (onlyInactive) {
      url.searchParams.set('only-inactive', onlyInactive as unknown as string);
    }
    if (onlyNew) {
      url.searchParams.set('only-new', onlyNew as unknown as string);
    }
    const response = await fetch(url.toString(), {
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

export const fetchFaqsByAutocomplete = async (searchTerm: string, csrfToken: string): Promise<Response | undefined> => {
  try {
    const response = await fetch(`./api/faq/search`, {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        search: searchTerm,
        csrf: csrfToken,
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

export const deleteFaq = async (faqId: string, faqLanguage: string, token: string): Promise<Response | undefined> => {
  try {
    const response = await fetch('./api/faq/delete', {
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

export const create = async (formData: any): Promise<Response | undefined> => {
  try {
    const response = await fetch('./api/faq/create', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        data: formData,
      }),
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const update = async (formData: any): Promise<Response | undefined> => {
  try {
    const response = await fetch('./api/faq/update', {
      method: 'PUT',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        data: formData,
      }),
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};
