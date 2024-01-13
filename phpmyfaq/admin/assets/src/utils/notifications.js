/**
 * phpMyFAQ push notifications based on Bootstrap Toasts.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-04
 */

import { Toast } from 'bootstrap';

export const pushNotification = (message) => {
  const toast = document.getElementById('pmf-notification');
  const notificationMessage = document.getElementById('pmf-notification-message');

  const notification = Toast.getOrCreateInstance(toast);
  if (message) {
    notificationMessage.innerText = message;
  }
  notification.show();
};

export const pushErrorNotification = (message) => {
  const toast = document.getElementById('pmf-notification-error');
  const notificationMessage = document.getElementById('pmf-notification-error-message');

  const notification = Toast.getOrCreateInstance(toast);
  if (message) {
    notificationMessage.innerText = message;
  }
  notification.show();
};
