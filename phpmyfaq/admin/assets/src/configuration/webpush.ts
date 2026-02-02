/**
 * Admin Web Push configuration
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
 * @since     2026-02-04
 */

import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { fetchGenerateVapidKeys } from '../api';

export const handleWebPush = async (): Promise<void> => {
  const publicKeyInput = document.getElementById('edit[push.vapidPublicKey]') as HTMLInputElement | null;
  const privateKeyInput = document.getElementById('edit[push.vapidPrivateKey]') as HTMLInputElement | null;

  if (!publicKeyInput) {
    return;
  }

  // Remove name attributes so VAPID keys are excluded from the config form submission.
  // They are managed separately via the generate-vapid-keys API endpoint.
  publicKeyInput.removeAttribute('name');
  privateKeyInput?.removeAttribute('name');

  // Also remove VAPID key fields from the availableFields hidden input
  // to prevent them from being processed during form save.
  const availableFieldsInput = document.querySelector<HTMLInputElement>('input[name="availableFields"]');
  if (availableFieldsInput) {
    try {
      const fields: string[] = JSON.parse(availableFieldsInput.value);
      const filtered = fields.filter(
        (field: string) => field !== 'push.vapidPublicKey' && field !== 'push.vapidPrivateKey'
      );
      availableFieldsInput.value = JSON.stringify(filtered);
    } catch (_e) {
      // Ignore parse errors
    }
  }

  // Mask the private key for display
  if (privateKeyInput && privateKeyInput.value !== '') {
    privateKeyInput.value = '\u2022'.repeat(20);
  }

  const parentDiv = publicKeyInput.parentElement;
  if (!parentDiv) {
    return;
  }

  // Avoid adding the button multiple times
  if (parentDiv.querySelector('#pmf-generate-vapid-keys')) {
    return;
  }

  const button = document.createElement('button');
  button.type = 'button';
  button.id = 'pmf-generate-vapid-keys';
  button.className = 'btn btn-outline-primary mt-2';
  button.innerHTML = '<i class="bi bi-key" aria-hidden="true"></i> Generate VAPID Keys';
  parentDiv.appendChild(button);

  button.addEventListener('click', async (event: Event): Promise<void> => {
    event.preventDefault();
    button.disabled = true;
    button.innerHTML =
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...';

    try {
      const response = await fetchGenerateVapidKeys();

      if (response.success) {
        publicKeyInput.value = response.publicKey;

        if (privateKeyInput) {
          privateKeyInput.value = '\u2022'.repeat(20);
        }

        pushNotification('VAPID keys have been generated successfully.');
      } else {
        pushErrorNotification(response.error ?? 'Failed to generate VAPID keys.');
      }
    } catch (_error) {
      pushErrorNotification('Failed to generate VAPID keys.');
    } finally {
      button.disabled = false;
      button.innerHTML = '<i class="bi bi-key" aria-hidden="true"></i> Generate VAPID Keys';
    }
  });
};
