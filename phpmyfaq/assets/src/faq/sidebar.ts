/**
 * FAQ Sidebar toggle functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-24
 */

const STORAGE_KEY = 'pmf-sidebar-collapsed';

export const handleSidebarToggle = (): void => {
  const toggleButton = document.getElementById('pmf-sidebar-toggle');
  const sidebar = document.getElementById('pmf-sidebar');
  const contentColumn = document.getElementById('pmf-content-column');

  if (!toggleButton || !sidebar || !contentColumn) {
    return;
  }

  const isCollapsed = localStorage.getItem(STORAGE_KEY) === 'true';
  if (isCollapsed) {
    sidebar.classList.add('pmf-sidebar-collapsed');
    contentColumn.classList.add('pmf-content-expanded');
    toggleButton.classList.add('collapsed');
    updateToggleIcon(toggleButton, true);
  }

  toggleButton.addEventListener('click', (event: Event) => {
    event.preventDefault();

    const nowCollapsed = sidebar.classList.toggle('pmf-sidebar-collapsed');
    contentColumn.classList.toggle('pmf-content-expanded');
    toggleButton.classList.toggle('collapsed');

    localStorage.setItem(STORAGE_KEY, String(nowCollapsed));
    updateToggleIcon(toggleButton, nowCollapsed);
  });
};

const updateToggleIcon = (button: HTMLElement, isCollapsed: boolean): void => {
  const icon = button.querySelector('i');
  if (icon) {
    if (isCollapsed) {
      icon.classList.remove('bi-chevron-right');
      icon.classList.add('bi-chevron-left');
    } else {
      icon.classList.remove('bi-chevron-left');
      icon.classList.add('bi-chevron-right');
    }
  }
};
