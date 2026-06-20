/**
 * Matched-term highlighting for search suggestions
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

const escapeRegExp = (value: string): string => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

export const highlightMatch = (text: string, query: string): DocumentFragment => {
  const fragment = document.createDocumentFragment();
  const trimmedQuery = query.trim();

  if (trimmedQuery === '') {
    fragment.appendChild(document.createTextNode(text));
    return fragment;
  }

  const regex = new RegExp(`(${escapeRegExp(trimmedQuery)})`, 'gi');
  const parts = text.split(regex);

  parts.forEach((part, index) => {
    if (part === '') {
      return;
    }
    // split() with a capturing group yields matches at odd indices.
    if (index % 2 === 1) {
      const strong = document.createElement('strong');
      strong.textContent = part;
      fragment.appendChild(strong);
    } else {
      fragment.appendChild(document.createTextNode(part));
    }
  });

  return fragment;
};
