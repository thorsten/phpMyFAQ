/**
 * Translation API Tests
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

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { TranslationApi } from './translation-api';
import { TranslationRequest } from './types';

describe('TranslationApi', () => {
  let api: TranslationApi;

  beforeEach(() => {
    api = new TranslationApi();
    vi.clearAllMocks();
  });

  describe('translate', () => {
    it('should translate content and return success response', async () => {
      const mockResponse = {
        success: true,
        translatedFields: {
          question: 'Was ist phpMyFAQ?',
          answer: 'phpMyFAQ ist ein Open-Source FAQ-System.',
        },
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const request: TranslationRequest = {
        contentType: 'faq',
        sourceLang: 'en',
        targetLang: 'de',
        fields: {
          question: 'What is phpMyFAQ?',
          answer: 'phpMyFAQ is an open-source FAQ system.',
        },
        'pmf-csrf-token': 'test-token',
      };

      const result = await api.translate(request);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('/admin/api/translation/translate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(request),
      });
    });

    it('should return error response when API returns error', async () => {
      const mockErrorResponse = {
        error: 'Translation provider not configured',
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: false,
          json: () => Promise.resolve(mockErrorResponse),
        } as Response)
      );

      const request: TranslationRequest = {
        contentType: 'faq',
        sourceLang: 'en',
        targetLang: 'de',
        fields: { question: 'What is phpMyFAQ?' },
        'pmf-csrf-token': 'test-token',
      };

      const result = await api.translate(request);

      expect(result).toEqual({
        success: false,
        error: 'Translation provider not configured',
      });
    });

    it('should return error response when HTTP error occurs', async () => {
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: false,
          status: 500,
          json: () => Promise.resolve({}),
        } as Response)
      );

      const request: TranslationRequest = {
        contentType: 'faq',
        sourceLang: 'en',
        targetLang: 'de',
        fields: { question: 'What is phpMyFAQ?' },
        'pmf-csrf-token': 'test-token',
      };

      const result = await api.translate(request);

      expect(result).toEqual({
        success: false,
        error: 'HTTP error! status: 500',
      });
    });

    it('should return error response when network error occurs', async () => {
      const mockError = new Error('Network error');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const request: TranslationRequest = {
        contentType: 'faq',
        sourceLang: 'en',
        targetLang: 'de',
        fields: { question: 'What is phpMyFAQ?' },
        'pmf-csrf-token': 'test-token',
      };

      const result = await api.translate(request);

      expect(result).toEqual({
        success: false,
        error: 'Network error',
      });
    });

    it('should handle different content types', async () => {
      const mockResponse = {
        success: true,
        translatedFields: {
          pageTitle: 'Über uns',
          content: 'Dies ist unsere Über-uns-Seite.',
        },
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const request: TranslationRequest = {
        contentType: 'customPage',
        sourceLang: 'en',
        targetLang: 'de',
        fields: {
          pageTitle: 'About Us',
          content: 'This is our about page.',
        },
        'pmf-csrf-token': 'test-token',
      };

      const result = await api.translate(request);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('/admin/api/translation/translate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(request),
      });
    });
  });
});
