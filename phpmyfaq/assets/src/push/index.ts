/**
 * Push notification handler
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

import { getVapidPublicKey, subscribePush, unsubscribePush } from '../api/push';

const PUSH_DISMISSED_KEY = 'pmf-push-banner-dismissed';

const urlBase64ToUint8Array = (base64String: string): Uint8Array => {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }

  return outputArray;
};

const updateButtonState = (button: HTMLButtonElement, subscribed: boolean): void => {
  if (subscribed) {
    button.textContent = button.dataset.labelDisable || 'Disable push notifications';
    button.classList.remove('btn-primary');
    button.classList.add('btn-outline-secondary');
  } else {
    button.textContent = button.dataset.labelEnable || 'Enable push notifications';
    button.classList.remove('btn-outline-secondary');
    button.classList.add('btn-primary');
  }
};

const showToast = (message: string, type: 'success' | 'danger'): void => {
  const container = document.getElementById('pmf-push-toast-container');
  if (!container) {
    return;
  }

  const alert = document.createElement('div');
  alert.className = `alert alert-${type} alert-dismissible fade show`;
  alert.role = 'alert';
  alert.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
  container.appendChild(alert);

  setTimeout(() => {
    alert.classList.remove('show');
    setTimeout(() => alert.remove(), 150);
  }, 4000);
};

const subscribeUser = async (
  registration: ServiceWorkerRegistration,
  vapidPublicKey: string
): Promise<PushSubscription> => {
  const subscription = await registration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
  });

  await subscribePush(subscription);
  return subscription;
};

/**
 * Handles the global push notification banner shown on all pages.
 */
const handlePushBanner = async (
  registration: ServiceWorkerRegistration,
  vapidPublicKey: string,
  isSubscribed: boolean
): Promise<void> => {
  const banner = document.getElementById('pmf-push-banner');
  if (!banner || isSubscribed) {
    return;
  }

  if (!('Notification' in window) || Notification.permission === 'denied') {
    return;
  }

  if (Notification.permission === 'granted') {
    // Already granted but not subscribed — might have been cleared. Don't nag.
    return;
  }

  // Check if the user has dismissed the banner before
  if (localStorage.getItem(PUSH_DISMISSED_KEY)) {
    return;
  }

  // Show the banner
  banner.classList.remove('d-none');

  const enableButton = document.getElementById('pmf-push-banner-enable') as HTMLButtonElement | null;
  const dismissButton = document.getElementById('pmf-push-banner-dismiss') as HTMLButtonElement | null;

  enableButton?.addEventListener('click', async (): Promise<void> => {
    try {
      await subscribeUser(registration, vapidPublicKey);
      banner.classList.add('d-none');
      localStorage.setItem(PUSH_DISMISSED_KEY, 'subscribed');
    } catch (error) {
      console.error('Push subscription error:', error);
      banner.classList.add('d-none');
      localStorage.setItem(PUSH_DISMISSED_KEY, 'denied');
    }
  });

  dismissButton?.addEventListener('click', (): void => {
    banner.classList.add('d-none');
    localStorage.setItem(PUSH_DISMISSED_KEY, 'dismissed');
  });
};

/**
 * Handles the UCP push notification toggle button.
 */
const handleUcpToggle = async (
  button: HTMLButtonElement,
  registration: ServiceWorkerRegistration,
  vapidPublicKey: string,
  initialSubscription: PushSubscription | null
): Promise<void> => {
  let isSubscribed = initialSubscription !== null;
  let currentSubscription = initialSubscription;
  updateButtonState(button, isSubscribed);
  button.disabled = false;

  button.addEventListener('click', async (): Promise<void> => {
    button.disabled = true;

    try {
      if (isSubscribed && currentSubscription) {
        const endpoint = currentSubscription.endpoint;
        await currentSubscription.unsubscribe();
        await unsubscribePush(endpoint);
        isSubscribed = false;
        currentSubscription = null;
        updateButtonState(button, false);
        showToast(button.dataset.msgDisabled || 'Push notifications disabled', 'success');
        localStorage.removeItem(PUSH_DISMISSED_KEY);
      } else {
        if (Notification.permission === 'denied') {
          showToast(button.dataset.msgPermissionDenied || 'Push notification permission was denied', 'danger');
          button.disabled = false;
          return;
        }

        currentSubscription = await subscribeUser(registration, vapidPublicKey);
        isSubscribed = true;
        updateButtonState(button, true);
        showToast(button.dataset.msgEnabled || 'Push notifications enabled', 'success');
        localStorage.setItem(PUSH_DISMISSED_KEY, 'subscribed');
      }
    } catch (error) {
      console.error('Push subscription error:', error);
      // Show more specific error message
      let errorMessage = button.dataset.msgError || 'Failed to enable push notifications';
      if (error instanceof Error) {
        if (error.message.includes('permission') || error.name === 'NotAllowedError') {
          errorMessage = button.dataset.msgPermissionDenied || 'Push notification permission was denied';
        } else {
          errorMessage = error.message;
        }
      }
      showToast(errorMessage, 'danger');
    }

    button.disabled = false;
  });
};

export const handlePushNotifications = async (): Promise<void> => {
  const ucpButton = document.getElementById('pmf-push-toggle') as HTMLButtonElement | null;
  const banner = document.getElementById('pmf-push-banner');

  // Nothing to do if neither the UCP button nor the global banner exist
  if (!ucpButton && !banner) {
    return;
  }

  // For the banner only (no UCP button): skip API call if user already dismissed/subscribed
  // This avoids unnecessary API calls on every page load
  if (!ucpButton && banner) {
    if (localStorage.getItem(PUSH_DISMISSED_KEY)) {
      return;
    }
    // Also skip if notifications are already granted (user is subscribed)
    if ('Notification' in window && Notification.permission === 'granted') {
      return;
    }
    // Skip if permission was denied
    if ('Notification' in window && Notification.permission === 'denied') {
      return;
    }
  }

  // Service workers require a secure context (HTTPS or localhost)
  if (!window.isSecureContext) {
    banner?.classList.add('d-none');
    return;
  }

  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    if (ucpButton) {
      ucpButton.disabled = true;
      ucpButton.textContent =
        ucpButton.dataset.labelNotSupported || 'Push notifications are not supported by your browser';
    }
    banner?.classList.add('d-none');
    return;
  }

  try {
    const vapidResponse = await getVapidPublicKey();

    if (!vapidResponse.enabled || !vapidResponse.vapidPublicKey) {
      if (ucpButton) {
        ucpButton.parentElement?.parentElement?.classList.add('d-none');
      }
      banner?.classList.add('d-none');
      return;
    }

    // Register service worker - use absolute path from site root
    const registration = await navigator.serviceWorker.register('/sw.js');
    const existingSubscription = await registration.pushManager.getSubscription();
    const isSubscribed = existingSubscription !== null;

    // Handle UCP toggle button (on the User Control Panel page)
    if (ucpButton) {
      await handleUcpToggle(ucpButton, registration, vapidResponse.vapidPublicKey, existingSubscription);
    }

    // Handle global push banner (on all pages)
    if (banner) {
      await handlePushBanner(registration, vapidResponse.vapidPublicKey, isSubscribed);
    }
  } catch (error) {
    console.error('Push notification setup failed:', error);
    if (ucpButton) {
      ucpButton.disabled = true;
    }
    banner?.classList.add('d-none');
  }
};
