/**
 * Push notification API module
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

export interface VapidPublicKeyResponse {
  enabled: boolean;
  vapidPublicKey: string;
}

export interface PushSubscribeResponse {
  success?: boolean;
  error?: string;
}

export interface PushStatusResponse {
  subscribed: boolean;
}

export const getVapidPublicKey = async (): Promise<VapidPublicKeyResponse> => {
  const response: Response = await fetch('api/push/vapid-public-key', {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return await response.json();
};

export const subscribePush = async (subscription: PushSubscription): Promise<PushSubscribeResponse> => {
  const key = subscription.getKey('p256dh');
  const auth = subscription.getKey('auth');

  if (!key || !auth) {
    throw new Error('Missing subscription keys');
  }

  const publicKey = btoa(String.fromCharCode(...new Uint8Array(key)));
  const authToken = btoa(String.fromCharCode(...new Uint8Array(auth)));

  const response: Response = await fetch('api/push/subscribe', {
    method: 'POST',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      endpoint: subscription.endpoint,
      publicKey: publicKey,
      authToken: authToken,
      contentEncoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
    }),
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return await response.json();
};

export const unsubscribePush = async (endpoint: string): Promise<PushSubscribeResponse> => {
  const response: Response = await fetch('api/push/unsubscribe', {
    method: 'POST',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ endpoint }),
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return await response.json();
};

export const getPushStatus = async (): Promise<PushStatusResponse> => {
  const response: Response = await fetch('api/push/status', {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return await response.json();
};
