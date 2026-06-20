/**
 * Recent searches storage (localStorage)
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
 * @since     2026-06-20
 */

const STORAGE_KEY = 'pmf-recent-searches';
const MAX_ENTRIES = 5;

export const getRecentSearches = (): string[] => {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) {
      return [];
    }
    const parsed: unknown = JSON.parse(raw);
    if (!Array.isArray(parsed)) {
      return [];
    }
    // Normalize defensively: externally modified localStorage could contain
    // non-strings, blank entries, or an arbitrarily large array.
    return parsed
      .filter((item): item is string => typeof item === 'string')
      .map((item) => item.trim())
      .filter((item) => item !== '')
      .slice(0, MAX_ENTRIES);
  } catch {
    return [];
  }
};

export const addRecentSearch = (term: string): void => {
  const trimmed = term.trim();
  if (trimmed === '') {
    return;
  }

  try {
    const existing = getRecentSearches().filter((item) => item.toLowerCase() !== trimmed.toLowerCase());
    const updated = [trimmed, ...existing].slice(0, MAX_ENTRIES);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(updated));
  } catch {
    // Storage unavailable (e.g. private mode) — silently ignore.
  }
};

export const clearRecentSearches = (): void => {
  try {
    localStorage.removeItem(STORAGE_KEY);
  } catch {
    // Storage unavailable — silently ignore.
  }
};
