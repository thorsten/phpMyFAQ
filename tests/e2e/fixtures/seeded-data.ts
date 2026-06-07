/**
 * Expected content produced by the `phpmyfaq:seed-testdata` console command.
 *
 * Keep these values in sync with the JSON fixtures in
 * phpmyfaq/src/phpMyFAQ/Command/Fixtures/testdata/. The e2e specs assert against
 * these strings to confirm the bilingual (DE and EN) seed data is rendered.
 */

export type Locale = 'en' | 'de';

export const SEEDED = {
  categories: {
    gettingStarted: { en: 'Getting Started', de: 'Erste Schritte' },
    troubleshooting: { en: 'Troubleshooting', de: 'Fehlerbehebung' },
  },
  faqs: {
    // The "databases" FAQ uses plain, language-distinct search terms that the
    // phpMyFAQ search accepts (numeric/hyphenated terms like "8.4" are ignored).
    databases: {
      en: 'Which databases does phpMyFAQ support?',
      de: 'Welche Datenbanken unterstützt phpMyFAQ?',
      searchTerm: { en: 'databases', de: 'Datenbanken' },
      answerFragment: 'MySQL',
    },
  },
} as const;

/**
 * Admin credentials created by the headless installer (`phpmyfaq:install`).
 * The e2e harness installs with these defaults; override via env in CI.
 */
export const ADMIN = {
  user: process.env.E2E_ADMIN_USER ?? 'admin',
  password: process.env.E2E_ADMIN_PASSWORD ?? 'password1234',
} as const;

/** Force a UI language via the GET `lang` parameter (LanguageDetector priority 2). */
export function withLang(path: string, locale: Locale): string {
  const separator = path.includes('?') ? '&' : '?';
  return `${path}${separator}lang=${locale}`;
}
