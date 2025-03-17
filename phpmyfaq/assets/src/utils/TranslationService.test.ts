import { describe, expect, test, vi, beforeEach, Mock } from 'vitest';
import { TranslationService } from './TranslationService';
import { fetchTranslations } from '../api/translations';

vi.mock('../api/translations', () => ({
  fetchTranslations: vi.fn(),
}));

describe('TranslationService', (): void => {
  let service: TranslationService;

  beforeEach((): void => {
    service = new TranslationService();
  });

  test('should load translations successfully', async (): Promise<void> => {
    const mockTranslations = { hello: 'Hello', goodbye: 'Goodbye' };
    (fetchTranslations as Mock).mockResolvedValue(mockTranslations);

    await service.loadTranslations('en');

    expect(fetchTranslations).toHaveBeenCalledWith('en');
    expect(service.translate('hello')).toBe('Hello');
    expect(service.translate('goodbye')).toBe('Goodbye');
  });

  test('should handle error when loading translations', async (): Promise<void> => {
    const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation((): void => {});
    (fetchTranslations as Mock).mockRejectedValue(new Error('Failed to fetch'));

    await service.loadTranslations('en');

    expect(fetchTranslations).toHaveBeenCalledWith('en');
    expect(consoleErrorSpy).toHaveBeenCalledWith('Failed to load translations:', expect.any(Error));
    expect(service.translate('hello')).toBe('hello');

    consoleErrorSpy.mockRestore();
  });

  test('should return key if translation is not found', (): void => {
    expect(service.translate('nonexistent')).toBe('nonexistent');
  });
});
