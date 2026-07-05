/**
 * Category overview enhancements: filter, collapse/expand, translation popovers
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
 * @since     2026-07-05
 */

import { Popover } from 'bootstrap';
import { categorySortables } from './category';

const STORAGE_KEY = 'pmf-admin-category-collapsed';

export const handleCategoryOverview = (): void => {
  const tree = document.getElementById('pmf-category-tree');
  const filter = document.getElementById('pmf-category-filter') as HTMLInputElement | null;
  if (!tree || !filter) {
    return;
  }

  wirePopovers(tree);
  applyPersistedCollapsedState(tree);
  wireCollapseToggles(tree);
  wireExpandCollapseAll(tree, filter);
  wireFilter(filter, tree);
};

const wirePopovers = (tree: HTMLElement): void => {
  tree.querySelectorAll<HTMLElement>('[data-bs-toggle="popover"]').forEach((badge: HTMLElement): void => {
    Popover.getOrCreateInstance(badge);
  });
};

const readCollapsedIds = (): string[] => {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]') as string[];
  } catch {
    return [];
  }
};

const storeCollapsedIds = (ids: string[]): void => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
};

const setCollapsed = (tree: HTMLElement, categoryId: string, collapsed: boolean): void => {
  const container = tree.querySelector<HTMLElement>(`[data-pmf-children-of="${categoryId}"]`);
  const toggle = tree.querySelector<HTMLElement>(`[data-pmf-collapse-id="${categoryId}"]`);
  if (!container) {
    return;
  }
  container.classList.toggle('d-none', collapsed);
  if (toggle) {
    toggle.setAttribute('aria-expanded', String(!collapsed));
    const icon = toggle.querySelector('i');
    icon?.classList.toggle('bi-chevron-down', !collapsed);
    icon?.classList.toggle('bi-chevron-right', collapsed);
  }

  const ids = new Set(readCollapsedIds());
  if (collapsed) {
    ids.add(categoryId);
  } else {
    ids.delete(categoryId);
  }
  storeCollapsedIds([...ids]);
};

const applyPersistedCollapsedState = (tree: HTMLElement): void => {
  const activeIds = readCollapsedIds().filter(
    (categoryId: string): boolean => tree.querySelector(`[data-pmf-children-of="${categoryId}"]`) !== null
  );
  storeCollapsedIds(activeIds);
  activeIds.forEach((categoryId: string): void => {
    setCollapsed(tree, categoryId, true);
  });
};

const wireCollapseToggles = (tree: HTMLElement): void => {
  tree.addEventListener('click', (event: Event): void => {
    const toggle = (event.target as HTMLElement).closest<HTMLElement>('.pmf-category-toggle');
    if (!toggle) {
      return;
    }
    const categoryId = toggle.dataset.pmfCollapseId || '';
    const container = tree.querySelector<HTMLElement>(`[data-pmf-children-of="${categoryId}"]`);
    setCollapsed(tree, categoryId, !container?.classList.contains('d-none'));
  });
};

const wireExpandCollapseAll = (tree: HTMLElement, filter: HTMLInputElement): void => {
  const allToggleIds = (): string[] =>
    [...tree.querySelectorAll<HTMLElement>('[data-pmf-collapse-id]')].map(
      (toggle: HTMLElement): string => toggle.dataset.pmfCollapseId || ''
    );

  document.getElementById('pmf-category-expand-all')?.addEventListener('click', (): void => {
    if (filter.value.trim() !== '') {
      return;
    }
    allToggleIds().forEach((categoryId: string): void => {
      setCollapsed(tree, categoryId, false);
    });
  });
  document.getElementById('pmf-category-collapse-all')?.addEventListener('click', (): void => {
    if (filter.value.trim() !== '') {
      return;
    }
    allToggleIds().forEach((categoryId: string): void => {
      setCollapsed(tree, categoryId, true);
    });
  });
};

const setSortingDisabled = (disabled: boolean): void => {
  categorySortables.forEach((sortable): void => {
    sortable.option('disabled', disabled);
  });
};

const wireFilter = (filter: HTMLInputElement, tree: HTMLElement): void => {
  filter.addEventListener('input', (): void => {
    const query = filter.value.toLowerCase().trim();
    // Reordering a partially hidden tree would corrupt sibling order.
    setSortingDisabled(query !== '');

    const rows = [...tree.querySelectorAll<HTMLElement>('.list-group-item')];
    const containers = [...tree.querySelectorAll<HTMLElement>('[data-pmf-children-of]')];

    if (query === '') {
      rows.forEach((row: HTMLElement): void => {
        row.classList.remove('d-none');
      });
      containers.forEach((container: HTMLElement): void => {
        container.classList.remove('d-none');
      });
      applyPersistedCollapsedState(tree);
      return;
    }

    // Hide everything, force-expand containers, then reveal matches with
    // their descendants and ancestor chains.
    rows.forEach((row: HTMLElement): void => {
      row.classList.toggle('d-none', !(row.dataset.pmfCategoryName || '').includes(query));
    });
    containers.forEach((container: HTMLElement): void => {
      container.classList.remove('d-none');
    });
    rows.forEach((row: HTMLElement): void => {
      if (!(row.dataset.pmfCategoryName || '').includes(query)) {
        return;
      }
      row.querySelectorAll<HTMLElement>('.list-group-item').forEach((descendant: HTMLElement): void => {
        descendant.classList.remove('d-none');
      });
      let ancestor = row.parentElement?.closest<HTMLElement>('.list-group-item');
      while (ancestor) {
        ancestor.classList.remove('d-none');
        ancestor = ancestor.parentElement?.closest<HTMLElement>('.list-group-item');
      }
    });
  });
};
