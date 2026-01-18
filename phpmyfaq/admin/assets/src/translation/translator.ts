/**
 * Main Translation Module
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

import { TranslationApi } from './translation-api';
import { ContentType, FieldMapping, TranslatorOptions } from './types';

/**
 * Translator class for AI-assisted content translation
 *
 * Usage example:
 * ```typescript
 * const translator = new Translator({
 *   buttonSelector: '#btn-translate-ai',
 *   contentType: 'faq',
 *   sourceLang: 'en',
 *   targetLang: 'de',
 *   fieldMapping: {
 *     'question': '#pmf-faq-question-translated',
 *     'answer': '#pmf-faq-answer-translated',
 *     'keywords': '#pmf-faq-keywords-translated'
 *   }
 * });
 * ```
 */
export class Translator {
  private readonly api: TranslationApi;
  private readonly button: HTMLButtonElement;
  private readonly contentType: ContentType;
  private readonly sourceLang: string;
  private readonly targetLang: string;
  private readonly fieldMapping: FieldMapping;
  private readonly onTranslationStart?: () => void;
  private readonly onTranslationSuccess?: (translatedFields: Record<string, string>) => void;
  private readonly onTranslationError?: (error: string) => void;

  private originalButtonText: string = '';
  private isTranslating: boolean = false;

  constructor(options: TranslatorOptions) {
    this.api = new TranslationApi();
    this.contentType = options.contentType;
    this.sourceLang = options.sourceLang;
    this.targetLang = options.targetLang;
    this.fieldMapping = options.fieldMapping;
    this.onTranslationStart = options.onTranslationStart;
    this.onTranslationSuccess = options.onTranslationSuccess;
    this.onTranslationError = options.onTranslationError;

    // Find and configure the button
    const buttonElement = document.querySelector(options.buttonSelector);
    if (!buttonElement || !(buttonElement instanceof HTMLButtonElement)) {
      throw new Error(`Button not found: ${options.buttonSelector}`);
    }
    this.button = buttonElement;
    this.originalButtonText = this.button.textContent || '';

    // Attach click handler
    this.button.addEventListener('click', () => this.handleTranslateClick());
  }

  /**
   * Handle translate button click
   */
  private async handleTranslateClick(): Promise<void> {
    if (this.isTranslating) {
      return;
    }

    try {
      this.isTranslating = true;
      this.setButtonLoading(true);
      this.onTranslationStart?.();

      // Collect source field values
      const fields = this.collectSourceFields();

      if (Object.keys(fields).length === 0) {
        this.showError('No fields to translate');
        return;
      }

      // Get CSRF token
      const csrfToken = this.getCsrfToken();
      if (!csrfToken) {
        this.showError('CSRF token not found');
        return;
      }

      // Call translation API
      const response = await this.api.translate({
        contentType: this.contentType,
        sourceLang: this.sourceLang,
        targetLang: this.targetLang,
        fields,
        'pmf-csrf-token': csrfToken,
      });

      if (response.success) {
        this.populateTranslatedFields(response.translatedFields);
        this.showSuccess();
        this.onTranslationSuccess?.();
      } else {
        this.showError(response.error);
        this.onTranslationError?.(response.error);
      }
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
      this.showError(errorMessage);
      this.onTranslationError?.(errorMessage);
    } finally {
      this.isTranslating = false;
      this.setButtonLoading(false);
    }
  }

  /**
   * Collect values from source fields
   */
  private collectSourceFields(): Record<string, string> {
    const fields: Record<string, string> = {};

    for (const fieldName in this.fieldMapping) {
      const sourceSelector = `#pmf-${this.contentType}-${fieldName}`;
      const sourceElement = document.querySelector(sourceSelector);

      if (sourceElement) {
        let value = '';

        if (sourceElement instanceof HTMLInputElement || sourceElement instanceof HTMLTextAreaElement) {
          value = sourceElement.value;
        } else if (sourceElement instanceof HTMLElement) {
          value = sourceElement.textContent || '';
        }

        if (value.trim()) {
          fields[fieldName] = value;
        }
      }
    }

    return fields;
  }

  /**
   * Populate translated fields into target form elements
   */
  private populateTranslatedFields(translatedFields: Record<string, string>): void {
    for (const fieldName in translatedFields) {
      const targetSelector = this.fieldMapping[fieldName];
      if (!targetSelector) {
        continue;
      }

      const targetElement = document.querySelector(targetSelector);
      if (!targetElement) {
        console.warn(`Target element not found: ${targetSelector}`);
        continue;
      }

      const translatedValue = translatedFields[fieldName];

      if (targetElement instanceof HTMLInputElement || targetElement instanceof HTMLTextAreaElement) {
        targetElement.value = translatedValue;

        // Trigger input event for any listeners
        targetElement.dispatchEvent(new Event('input', { bubbles: true }));
      } else if (targetElement instanceof HTMLElement) {
        targetElement.textContent = translatedValue;
      }
    }
  }

  /**
   * Get CSRF token from the page
   */
  private getCsrfToken(): string | null {
    const tokenInput = document.querySelector<HTMLInputElement>('input[name="pmf-csrf-token-translate"]');
    return tokenInput?.value || null;
  }

  /**
   * Set the button loading state
   */
  private setButtonLoading(loading: boolean): void {
    if (loading) {
      this.button.disabled = true;
      this.button.textContent = 'Translating...';
      this.button.classList.add('disabled');
    } else {
      this.button.disabled = false;
      this.button.textContent = this.originalButtonText;
      this.button.classList.remove('disabled');
    }
  }

  /**
   * Show a success message
   */
  private showSuccess(): void {
    // You can customize this to use your app's notification system
    console.log('Translation completed successfully');
  }

  /**
   * Show error message
   */
  private showError(message: string): void {
    // You can customize this to use your app's notification system
    console.error('Translation failed:', message);
    alert(`Translation failed: ${message}`);
  }
}
