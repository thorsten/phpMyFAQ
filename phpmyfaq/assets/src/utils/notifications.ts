/**
 * phpMyFAQ push notifications based on Bootstrap Toasts.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-04
 */

import { Toast } from 'bootstrap';

export const pushNotification = (message: string): void => {
  const toast = document.getElementById('pmf-notification') as HTMLElement | null;
  const notificationMessage = document.getElementById('pmf-notification-message') as HTMLElement | null;

  if (toast && notificationMessage) {
    const notification = Toast.getOrCreateInstance(toast);
    if (message) {
      notificationMessage.innerText = message;
    }
    notification.show();
  }
};

export const pushErrorNotification = (message: string): void => {
  const toast = document.getElementById('pmf-notification-error') as HTMLElement | null;
  const notificationMessage = document.getElementById('pmf-notification-error-message') as HTMLElement | null;

  if (toast && notificationMessage) {
    const notification = Toast.getOrCreateInstance(toast);
    if (message) {
      notificationMessage.innerText = message;
    }
    notification.show();
  }
};
