/**
 * FAQ editor category checkbox tree
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-07-13
 */

import { getCategoryPermissions } from './faqs';

export const handleFaqCategoryTree = (): void => {
  const tree = document.getElementById('pmf-faq-category-tree') as HTMLElement | null;
  const filter = document.getElementById('pmf-faq-category-filter') as HTMLInputElement | null;

  if (!tree) {
    return;
  }

  // The filter input is form-associated with #faqEditor, so Enter would
  // implicitly submit the form and save the FAQ — swallow it.
  filter?.addEventListener('keydown', (event: KeyboardEvent): void => {
    if (event.key === 'Enter') {
      event.preventDefault();
    }
  });

  filter?.addEventListener('input', (): void => {
    const query = filter.value.toLowerCase().trim();
    tree.querySelectorAll<HTMLElement>('.pmf-category-tree-item').forEach((item: HTMLElement): void => {
      item.classList.toggle('d-none', query !== '' && !(item.dataset.pmfCategoryName ?? '').includes(query));
    });
  });

  // Override FAQ permissions with the category permissions to avoid confused users
  tree.addEventListener('change', (event: Event): void => {
    const target = event.target as HTMLInputElement;
    if (target.name !== 'categories[]') {
      return;
    }

    const categories = Array.from(tree.querySelectorAll<HTMLInputElement>('input[name="categories[]"]:checked')).map(
      ({ value }) => value
    );
    if (categories.length > 0) {
      getCategoryPermissions(categories);
    }
  });
};
