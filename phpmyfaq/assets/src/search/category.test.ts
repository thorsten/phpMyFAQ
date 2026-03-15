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
    return {
      loadTranslations: vi.fn().mockResolvedValue(undefined),
      translate: vi.fn().mockImplementation((key: string) => `translated_${key}`),
    };
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
      loadTranslations: vi.fn().mockResolvedValue(undefined),
      translate: vi.fn().mockReturnValue('translated'),
    };
    mockTranslationService.mockImplementation(function () {
      return mockInstance as unknown as TranslationService;
    });

    await handleCategorySelection();

    expect(mockInstance.loadTranslations).toHaveBeenCalledWith('de');
  });

  it('should load translations with English language', async (): Promise<void> => {
    document.documentElement.lang = 'en';

    const mockInstance = {
      loadTranslations: vi.fn().mockResolvedValue(undefined),
      translate: vi.fn().mockReturnValue('translated'),
    };
    mockTranslationService.mockImplementation(function () {
      return mockInstance as unknown as TranslationService;
    });

    await handleCategorySelection();

    expect(mockInstance.loadTranslations).toHaveBeenCalledWith('en');
  });

  it('should configure search options correctly', async (): Promise<void> => {
    await handleCategorySelection();

    expect(mockChoices).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({
        searchEnabled: true,
        searchChoices: true,
        searchFloor: 1,
        searchResultLimit: 4,
        searchFields: ['label', 'value'],
      })
    );
  });

  it('should configure item handling options', async (): Promise<void> => {
    await handleCategorySelection();

    expect(mockChoices).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({
        addItems: true,
        removeItems: true,
        removeItemButton: false,
        editItems: false,
        duplicateItemsAllowed: true,
        delimiter: ',',
        paste: true,
      })
    );
  });

  it('should disable HTML for security', async (): Promise<void> => {
    await handleCategorySelection();

    expect(mockChoices).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({
        allowHTML: false,
        allowHtmlUserInput: false,
      })
    );
  });

  it('should configure custom class names with Bootstrap classes', async (): Promise<void> => {
    await handleCategorySelection();

    const config = mockChoices.mock.calls[0][1] as Record<string, unknown>;
    const classNames = config.classNames as Record<string, string[]>;

    expect(classNames.containerOuter).toContain('choices');
    expect(classNames.containerOuter).toContain('rounded');
    expect(classNames.containerOuter).toContain('border');
    expect(classNames.containerOuter).toContain('bg-white');
    expect(classNames.containerInner).toContain('border-0');
    expect(classNames.containerInner).toContain('bg-white');
  });

  it('should configure sorting and positioning options', async (): Promise<void> => {
    await handleCategorySelection();

    expect(mockChoices).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({
        position: 'auto',
        resetScrollPosition: true,
        shouldSort: true,
        shouldSortItems: false,
      })
    );
  });

  it('should enable placeholder', async (): Promise<void> => {
    await handleCategorySelection();

    expect(mockChoices).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({
        placeholder: true,
        placeholderValue: null,
      })
    );
  });

  it('should configure fuse options for search scoring', async (): Promise<void> => {
    await handleCategorySelection();

    const config = mockChoices.mock.calls[0][1] as Record<string, unknown>;
    const fuseOptions = config.fuseOptions as Record<string, unknown>;

    expect(fuseOptions.includeScore).toBe(true);
  });

  it('should provide a valueComparer that compares strings by equality', async (): Promise<void> => {
    await handleCategorySelection();

    const config = mockChoices.mock.calls[0][1] as Record<string, unknown>;
    const valueComparer = config.valueComparer as (v1: string, v2: string) => boolean;

    expect(valueComparer('foo', 'foo')).toBe(true);
    expect(valueComparer('foo', 'bar')).toBe(false);
    expect(valueComparer('', '')).toBe(true);
  });

  it('should provide an addItemFilter that rejects empty strings', async (): Promise<void> => {
    await handleCategorySelection();

    const config = mockChoices.mock.calls[0][1] as Record<string, unknown>;
    const addItemFilter = config.addItemFilter as (value: string) => boolean;

    expect(addItemFilter('valid')).toBe(true);
    expect(addItemFilter('')).toBe(false);
  });

  it('should translate all seven Choices.js text keys', async (): Promise<void> => {
    const translateSpy = vi.fn().mockImplementation((key: string) => `t_${key}`);
    mockTranslationService.mockImplementation(function () {
      return {
        loadTranslations: vi.fn().mockResolvedValue(undefined),
        translate: translateSpy,
      } as unknown as TranslationService;
    });

    await handleCategorySelection();

    expect(translateSpy).toHaveBeenCalledWith('msgTypeSearchCategories');
    expect(translateSpy).toHaveBeenCalledWith('msgLoadingText');
    expect(translateSpy).toHaveBeenCalledWith('msgNoResultsText');
    expect(translateSpy).toHaveBeenCalledWith('msgNoChoicesText');
    expect(translateSpy).toHaveBeenCalledWith('msgItemSelectText');
    expect(translateSpy).toHaveBeenCalledWith('msgUniqueItemText');
    expect(translateSpy).toHaveBeenCalledWith('msgCustomAddItemText');
  });

  it('should always load translations even when element is missing', async (): Promise<void> => {
    document.body.innerHTML = '';

    const mockInstance = {
      loadTranslations: vi.fn().mockResolvedValue(undefined),
      translate: vi.fn().mockReturnValue('translated'),
    };
    mockTranslationService.mockImplementation(function () {
      return mockInstance as unknown as TranslationService;
    });

    await handleCategorySelection();

    expect(mockInstance.loadTranslations).toHaveBeenCalledWith('de');
  });

  it('should set renderChoiceLimit to unlimited', async (): Promise<void> => {
    await handleCategorySelection();

    expect(mockChoices).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({
        renderChoiceLimit: -1,
        maxItemCount: -1,
      })
    );
  });
});
