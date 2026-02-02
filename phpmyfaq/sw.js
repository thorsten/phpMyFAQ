/**
 * phpMyFAQ Service Worker for Web Push Notifications
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-02
 */

self.addEventListener('install', (_event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
  let data = {
    title: 'phpMyFAQ',
    body: 'New notification',
  };

  if (event.data) {
    try {
      data = event.data.json();
    } catch (_e) {
      data = {
        title: 'phpMyFAQ',
        body: event.data.text() || 'New notification',
      };
    }
  }

  const options = {
    body: data.body || '',
    icon: data.icon || '',
    tag: data.tag || '',
    data: {
      url: data.url || '/',
    },
  };

  event.waitUntil(self.registration.showNotification(data.title || 'phpMyFAQ', options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const url = event.notification.data?.url || '/';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url === url && 'focus' in client) {
          return client.focus();
        }
      }

      if (self.clients.openWindow) {
        return self.clients.openWindow(url);
      }
    })
  );
});
