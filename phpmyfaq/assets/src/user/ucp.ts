/**
 * User Control Panel functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-03
 */

import { removeTwofactorConfig, updateUserControlPanelData } from '../api';
import { addElement } from '../utils';
import { ApiResponse } from '../interfaces';
import { pushErrorNotification, pushNotification } from '../utils';

export const handleUserControlPanel = (): void => {
  const userControlPanelSubmit = document.getElementById('pmf-submit-user-control-panel') as HTMLButtonElement | null;

  if (userControlPanelSubmit) {
    userControlPanelSubmit.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();

      const form = document.querySelector('#pmf-user-control-panel-form') as HTMLFormElement;
      const loader = document.getElementById('loader') as HTMLElement;
      const formData = new FormData(form);

      const response = (await updateUserControlPanelData(formData)) as ApiResponse;

      if (response.success) {
        loader.classList.add('d-none');
        const message = document.getElementById('pmf-user-control-panel-response') as HTMLElement;
        message.insertAdjacentElement(
          'afterend',
          addElement('div', { classList: 'alert alert-success', innerText: response.success })
        );
      }

      if (response.error) {
        loader.classList.add('d-none');
        const message = document.getElementById('pmf-user-control-panel-response') as HTMLElement;
        message.insertAdjacentElement(
          'afterend',
          addElement('div', { classList: 'alert alert-danger', innerText: response.error })
        );
      }
    });

    const confirmRemoveTwoFactor = document.getElementById('pmf-remove-twofactor-confirm');
    if (confirmRemoveTwoFactor) {
      confirmRemoveTwoFactor.addEventListener('click', async (event: Event): Promise<void> => {
        event.preventDefault();
        const csrfToken = document.getElementById('pmf-csrf-token-remove-twofactor') as HTMLInputElement;
        const response = (await removeTwofactorConfig(csrfToken.value)) as ApiResponse;
        if (response.success) {
          pushNotification(response.success);
          const twoFactorEnabled = document.getElementById('twofactor_enabled') as HTMLInputElement | null;
          if (twoFactorEnabled) {
            twoFactorEnabled.checked = false;
          }
          const removeCurrentConfig = document.getElementById('removeCurrentConfig') as HTMLElement | null;
          if (removeCurrentConfig) {
            removeCurrentConfig.style.display = 'none';
          }
        }
        if (response.error) {
          pushErrorNotification(response.error);
        }
      });
    }
  }
};
