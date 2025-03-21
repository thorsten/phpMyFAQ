/**
 * Loads the correct sidebar on window load,
 * collapses the sidebar on window resize.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-03-22
 */

export const sidebarToggle = (): void => {
  const sidebarToggle = document.body.querySelector('#sidebarToggle') as HTMLElement | null;

  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', (event: Event) => {
      event.preventDefault();
      document.body.classList.toggle('pmf-admin-sidenav-toggled');
      localStorage.setItem(
        'sb|sidebar-toggle',
        document.body.classList.contains('pmf-admin-sidenav-toggled').toString()
      );
    });
  }
};
