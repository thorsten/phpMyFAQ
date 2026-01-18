/**
 * API functions for sticky FAQs management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-18
 */

import { fetchJson } from './fetch-wrapper';

export interface StickyOrderResponse {
  success?: string;
  error?: string;
}

export interface RemoveStickyResponse {
  success?: string;
  error?: string;
}

/**
 * Update the order of sticky FAQs
 * @param faqIds Array of FAQ IDs in the new order
 * @param csrf CSRF token
 * @returns Promise with the API response
 */
export const updateStickyFaqsOrder = async (faqIds: string[], csrf: string): Promise<StickyOrderResponse> => {
  return (await fetchJson('./api/faqs/sticky/order', {
    method: 'POST',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      faqIds,
      csrf,
    }),
  })) as StickyOrderResponse;
};

/**
 * Remove a sticky FAQ
 * @param faqId FAQ ID to remove from sticky
 * @param categoryId Category ID
 * @param csrfToken CSRF token
 * @param lang Language code
 * @returns Promise with the API response
 */
export const removeStickyFaq = async (
  faqId: string,
  categoryId: string,
  csrfToken: string,
  lang: string
): Promise<RemoveStickyResponse> => {
  return (await fetchJson('./api/faq/sticky', {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-PMF-CSRF-Token': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({
      csrf: csrfToken,
      categoryId: categoryId,
      faqIds: [faqId],
      faqLanguage: lang,
      checked: false,
    }),
  })) as RemoveStickyResponse;
};
