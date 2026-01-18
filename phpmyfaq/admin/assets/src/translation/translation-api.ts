/**
 * Translation API Client
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
 * @since     2026-01-17
 */

import { TranslationRequest, TranslationResponse } from './types';

/**
 * Translation API client
 */
export class TranslationApi {
  private readonly apiUrl: string;

  constructor() {
    this.apiUrl = '/admin/api/translation/translate';
  }

  /**
   * Translate content fields using the configured AI translation provider
   *
   * @param request - Translation request payload
   * @returns Translation response with translated fields or error
   */
  async translate(request: TranslationRequest): Promise<TranslationResponse> {
    try {
      const response = await fetch(this.apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(request),
      });

      if (!response.ok) {
        const errorData = await response.json();
        return {
          success: false,
          error: errorData.error || `HTTP error! status: ${response.status}`,
        };
      }

      const data = await response.json();
      return data as TranslationResponse;
    } catch (error) {
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Network error occurred',
      };
    }
  }
}
