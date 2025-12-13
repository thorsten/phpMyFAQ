import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import Choices from 'choices.js';
import { TranslationService } from '../utils';
import { handleCategorySelection } from './category';

// Mock Choices.js
vi.mock('choices.js', () => ({
  default: vi.fn(),
}));

// Mock TranslationService
vi.mock('../utils', () => ({
  TranslationService: vi.fn(function () {
    this.translations = new Map();
    this.loadTranslations = vi.fn().mockResolvedValue(undefined);
    this.translate = vi.fn().mockImplementation((key: string) => `translated_${key}`);
  }),
}));

describe('handleCategorySelection', () => {
  const mockChoices = vi.mocked(Choices);
  const mockTranslationService = vi.mocked(TranslationService);

  beforeEach(() => {
    vi.clearAllMocks();

    // Setup DOM
    document.body.innerHTML = '<select id="pmf-search-category"></select>';
    document.documentElement.lang = 'de';
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('should initialize Choices when element exists', async (): Promise<void> => {
    await handleCategorySelection();

    expect(mockTranslationService).toHaveBeenCalled();
    expect(mockChoices).toHaveBeenCalledWith(
      document.getElementById('pmf-search-category'),
      expect.objectContaining({
        searchPlaceholderValue: 'translated_msgTypeSearchCategories',
        loadingText: 'translated_msgLoadingText',
        noResultsText: 'translated_msgNoResultsText',
        noChoicesText: 'translated_msgNoChoicesText',
        itemSelectText: 'translated_msgItemSelectText',
        uniqueItemText: 'translated_msgUniqueItemText',
        customAddItemText: 'translated_msgCustomAddItemText',
      })
    );
  });

  it('should not initialize Choices when element does not exist', async (): Promise<void> => {
    document.body.innerHTML = '';

    await handleCategorySelection();

    expect(mockTranslationService).toHaveBeenCalled();
    expect(mockChoices).not.toHaveBeenCalled();
  });

  it('should load translations with correct language', async (): Promise<void> => {
    const mockInstance = {
      translations: new Map(),
      loadTranslations: vi.fn().mockResolvedValue(undefined),
      translate: vi.fn().mockReturnValue('translated'),
    };
    mockTranslationService.mockImplementation(function () {
      this.translations = mockInstance.translations;
      this.loadTranslations = mockInstance.loadTranslations;
      this.translate = mockInstance.translate;
    });

    await handleCategorySelection();

    expect(mockInstance.loadTranslations).toHaveBeenCalledWith('de');
  });
});
