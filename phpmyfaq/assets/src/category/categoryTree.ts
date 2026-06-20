/**
 * Collapsible category overview tree
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

export const handleCategoryTree = (): void => {
  const tree = document.querySelector<HTMLElement>('.pmf-category-tree--root');
  if (tree === null) {
    return;
  }

  const getToggles = (): HTMLButtonElement[] =>
    Array.from(tree.querySelectorAll<HTMLButtonElement>('.pmf-category-tree__toggle'));

  const setExpanded = (toggleButton: HTMLButtonElement, expanded: boolean): void => {
    toggleButton.setAttribute('aria-expanded', String(expanded));
    const controls = toggleButton.getAttribute('aria-controls');
    const region = controls !== null ? document.getElementById(controls) : null;
    if (region !== null) {
      region.hidden = !expanded;
    }
  };

  // Progressive enhancement: the markup ships expanded; collapse every branch now.
  getToggles().forEach((toggleButton) => setExpanded(toggleButton, false));

  tree.addEventListener('click', (event: Event): void => {
    const toggleButton = (event.target as HTMLElement).closest<HTMLButtonElement>('.pmf-category-tree__toggle');
    if (toggleButton === null || !tree.contains(toggleButton)) {
      return;
    }
    setExpanded(toggleButton, toggleButton.getAttribute('aria-expanded') !== 'true');
  });

  document.querySelector('[data-pmf-expand-all]')?.addEventListener('click', (): void => {
    getToggles().forEach((toggleButton) => setExpanded(toggleButton, true));
  });
  document.querySelector('[data-pmf-collapse-all]')?.addEventListener('click', (): void => {
    getToggles().forEach((toggleButton) => setExpanded(toggleButton, false));
  });
};
