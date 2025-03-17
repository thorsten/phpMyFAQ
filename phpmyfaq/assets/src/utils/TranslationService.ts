import { fetchTranslations } from '../api/translations';

export class TranslationService {
  private translations: Record<string, string> = {};

  // Load translations from JSON
  async loadTranslations(locale: string): Promise<void> {
    try {
      this.translations = await fetchTranslations(locale);
    } catch (error) {
      console.error('Failed to load translations:', error);
    }
  }

  // Get translated string by key
  translate(key: string): string {
    return this.translations[key] || key;
  }
}
