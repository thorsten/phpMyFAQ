/**
 * TypeScript types for Translation API
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

/**
 * Content types that can be translated
 */
export type ContentType = 'faq' | 'customPage' | 'category' | 'news';

/**
 * Field mapping: source field selector -> target field selector
 */
export interface FieldMapping {
  [sourceFieldId: string]: string;
}

/**
 * Translation request payload
 */
export interface TranslationRequest {
  contentType: ContentType;
  sourceLang: string;
  targetLang: string;
  fields: Record<string, string>;
  'pmf-csrf-token': string;
}

/**
 * Translation response (success)
 */
export interface TranslationSuccessResponse {
  success: true;
  translatedFields: Record<string, string>;
}

/**
 * Translation response (error)
 */
export interface TranslationErrorResponse {
  success: false;
  error: string;
}

/**
 * Combined translation response type
 */
export type TranslationResponse = TranslationSuccessResponse | TranslationErrorResponse;

/**
 * Translator options
 */
export interface TranslatorOptions {
  buttonSelector: string;
  contentType: ContentType;
  sourceLang: string;
  targetLang: string;
  fieldMapping: FieldMapping;
  onTranslationStart?: () => void;
  onTranslationSuccess?: (translatedFields: Record<string, string>) => void;
  onTranslationError?: (error: string) => void;
}
