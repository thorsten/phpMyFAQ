/**
 * Translator Tests
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

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { Translator } from './translator';
import * as TranslationApiModule from './translation-api';

// Mock the TranslationApi module
vi.mock('./translation-api', () => ({
  TranslationApi: vi.fn(),
}));

// Type for translation result
type TranslationResult = {
  success: boolean;
  translatedFields?: Record<string, string>;
  error?: string;
};

// Helper function to create a mocked TranslationApi
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const createMockTranslationApi = (mockTranslate: any) => {
  const MockedTranslationApi = vi.mocked(TranslationApiModule.TranslationApi);
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  MockedTranslationApi.mockImplementation(function (this: any) {
    this.translate = mockTranslate;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
  } as any);
};

describe('Translator', () => {
  let button: HTMLButtonElement;
  let sourceInput: HTMLInputElement;
  let targetInput: HTMLInputElement;
  let csrfTokenInput: HTMLInputElement;

  beforeEach(() => {
    // Set up DOM
    document.body.innerHTML = `
      <button id="btn-translate">Translate with AI</button>
      <input type="text" id="pmf-faq-question" value="What is phpMyFAQ?" />
      <input type="text" id="pmf-faq-question-translated" value="" />
      <input type="hidden" name="pmf-csrf-token" value="test-token" />
    `;

    button = document.querySelector('#btn-translate') as HTMLButtonElement;
    sourceInput = document.querySelector('#pmf-faq-question') as HTMLInputElement;
    targetInput = document.querySelector('#pmf-faq-question-translated') as HTMLInputElement;
    csrfTokenInput = document.querySelector('input[name="pmf-csrf-token"]') as HTMLInputElement;

    vi.clearAllMocks();
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('should throw error if button not found', () => {
    expect(() => {
      new Translator({
        buttonSelector: '#nonexistent-button',
        contentType: 'faq',
        sourceLang: 'en',
        targetLang: 'de',
        fieldMapping: { question: '#pmf-faq-question-translated' },
      });
    }).toThrow('Button not found: #nonexistent-button');
  });

  it('should initialize with correct options', () => {
    const translator = new Translator({
      buttonSelector: '#btn-translate',
      contentType: 'faq',
      sourceLang: 'en',
      targetLang: 'de',
      fieldMapping: { question: '#pmf-faq-question-translated' },
    });

    expect(translator).toBeDefined();
    expect(button.textContent).toBe('Translate with AI');
  });

  it('should collect source fields correctly', async () => {
    const mockTranslate = vi.fn().mockResolvedValue({
      success: true,
      translatedFields: { question: 'Was ist phpMyFAQ?' },
    });

    createMockTranslationApi(mockTranslate);

    new Translator({
      buttonSelector: '#btn-translate',
      contentType: 'faq',
      sourceLang: 'en',
      targetLang: 'de',
      fieldMapping: { question: '#pmf-faq-question-translated' },
    });

    button.click();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(mockTranslate).toHaveBeenCalledWith({
      contentType: 'faq',
      sourceLang: 'en',
      targetLang: 'de',
      fields: { question: 'What is phpMyFAQ?' },
      'pmf-csrf-token': 'test-token',
    });
  });

  it('should populate translated fields on success', async () => {
    const mockTranslate = vi.fn().mockResolvedValue({
      success: true,
      translatedFields: { question: 'Was ist phpMyFAQ?' },
    });

    createMockTranslationApi(mockTranslate);

    new Translator({
      buttonSelector: '#btn-translate',
      contentType: 'faq',
      sourceLang: 'en',
      targetLang: 'de',
      fieldMapping: { question: '#pmf-faq-question-translated' },
    });

    button.click();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(targetInput.value).toBe('Was ist phpMyFAQ?');
  });

  it('should set button to loading state during translation', async () => {
    let resolveTranslation: ((value: TranslationResult) => void) | undefined;
    const translationPromise = new Promise<TranslationResult>((resolve) => {
      resolveTranslation = resolve;
    });

    const mockTranslate = vi.fn().mockReturnValue(translationPromise);

    createMockTranslationApi(mockTranslate);

    new Translator({
      buttonSelector: '#btn-translate',
      contentType: 'faq',
      sourceLang: 'en',
      targetLang: 'de',
      fieldMapping: { question: '#pmf-faq-question-translated' },
    });

    button.click();
    await new Promise((resolve) => setTimeout(resolve, 0));

    // Button should be disabled and text changed
    expect(button.disabled).toBe(true);
    expect(button.textContent).toBe('Translating...');

    // Resolve the translation
    if (resolveTranslation) {
      resolveTranslation({
        success: true,
        translatedFields: { question: 'Was ist phpMyFAQ?' },
      });
    }

    await new Promise((resolve) => setTimeout(resolve, 0));

    // Button should be restored
    expect(button.disabled).toBe(false);
    expect(button.textContent).toBe('Translate with AI');
  });

  it('should call onTranslationSuccess callback on success', async () => {
    const onTranslationSuccess = vi.fn();
    const mockTranslate = vi.fn().mockResolvedValue({
      success: true,
      translatedFields: { question: 'Was ist phpMyFAQ?' },
    });

    createMockTranslationApi(mockTranslate);

    new Translator({
      buttonSelector: '#btn-translate',
      contentType: 'faq',
      sourceLang: 'en',
      targetLang: 'de',
      fieldMapping: { question: '#pmf-faq-question-translated' },
      onTranslationSuccess,
    });

    button.click();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(onTranslationSuccess).toHaveBeenCalledWith({ question: 'Was ist phpMyFAQ?' });
  });

  it('should call onTranslationError callback on error', async () => {
    const onTranslationError = vi.fn();
    const mockTranslate = vi.fn().mockResolvedValue({
      success: false,
      error: 'Translation provider not configured',
    });

    createMockTranslationApi(mockTranslate);

    // Mock alert to prevent it from blocking tests
    global.alert = vi.fn();

    new Translator({
      buttonSelector: '#btn-translate',
      contentType: 'faq',
      sourceLang: 'en',
      targetLang: 'de',
      fieldMapping: { question: '#pmf-faq-question-translated' },
      onTranslationError,
    });

    button.click();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(onTranslationError).toHaveBeenCalledWith('Translation provider not configured');
  });

  it('should handle missing CSRF token', async () => {
    csrfTokenInput.remove();

    const mockTranslate = vi.fn();
    createMockTranslationApi(mockTranslate);

    // Mock alert to prevent it from blocking tests
    global.alert = vi.fn();

    new Translator({
      buttonSelector: '#btn-translate',
      contentType: 'faq',
      sourceLang: 'en',
      targetLang: 'de',
      fieldMapping: { question: '#pmf-faq-question-translated' },
    });

    button.click();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(mockTranslate).not.toHaveBeenCalled();
    expect(global.alert).toHaveBeenCalledWith('Translation failed: CSRF token not found');
  });

  it('should handle empty fields', async () => {
    sourceInput.value = '';

    const mockTranslate = vi.fn();
    createMockTranslationApi(mockTranslate);

    // Mock alert to prevent it from blocking tests
    global.alert = vi.fn();

    new Translator({
      buttonSelector: '#btn-translate',
      contentType: 'faq',
      sourceLang: 'en',
      targetLang: 'de',
      fieldMapping: { question: '#pmf-faq-question-translated' },
    });

    button.click();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(mockTranslate).not.toHaveBeenCalled();
    expect(global.alert).toHaveBeenCalledWith('Translation failed: No fields to translate');
  });

  it('should prevent multiple simultaneous translations', async () => {
    let resolveTranslation: ((value: TranslationResult) => void) | undefined;
    const translationPromise = new Promise<TranslationResult>((resolve) => {
      resolveTranslation = resolve;
    });

    const mockTranslate = vi.fn().mockReturnValue(translationPromise);

    createMockTranslationApi(mockTranslate);

    new Translator({
      buttonSelector: '#btn-translate',
      contentType: 'faq',
      sourceLang: 'en',
      targetLang: 'de',
      fieldMapping: { question: '#pmf-faq-question-translated' },
    });

    // Click button twice
    button.click();
    button.click();

    await new Promise((resolve) => setTimeout(resolve, 0));

    // Should only call translate once
    expect(mockTranslate).toHaveBeenCalledTimes(1);

    // Resolve the translation
    if (resolveTranslation) {
      resolveTranslation({
        success: true,
        translatedFields: { question: 'Was ist phpMyFAQ?' },
      });
    }

    await new Promise((resolve) => setTimeout(resolve, 0));
  });

  it('should handle textarea fields', async () => {
    document.body.innerHTML = `
      <button id="btn-translate">Translate with AI</button>
      <textarea id="pmf-faq-answer">This is the answer.</textarea>
      <textarea id="pmf-faq-answer-translated"></textarea>
      <input type="hidden" name="pmf-csrf-token" value="test-token" />
    `;

    const mockTranslate = vi.fn().mockResolvedValue({
      success: true,
      translatedFields: { answer: 'Das ist die Antwort.' },
    });

    createMockTranslationApi(mockTranslate);

    const translateButton = document.querySelector('#btn-translate') as HTMLButtonElement;

    new Translator({
      buttonSelector: '#btn-translate',
      contentType: 'faq',
      sourceLang: 'en',
      targetLang: 'de',
      fieldMapping: { answer: '#pmf-faq-answer-translated' },
    });

    translateButton.click();
    await new Promise((resolve) => setTimeout(resolve, 0));

    const targetTextarea = document.querySelector('#pmf-faq-answer-translated') as HTMLTextAreaElement;
    expect(targetTextarea.value).toBe('Das ist die Antwort.');
  });
});
