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
import { pushErrorNotification, pushNotification } from '../../../admin/assets/src/utils';

export const handleUserControlPanel = () => {
  const userControlPanelSubmit = document.getElementById('pmf-submit-user-control-panel');

  if (userControlPanelSubmit) {
    userControlPanelSubmit.addEventListener('click', async (event) => {
      event.preventDefault();

      const form = document.querySelector('#pmf-user-control-panel-form');
      const loader = document.getElementById('loader');
      const formData = new FormData(form);

      const response = await updateUserControlPanelData(formData);

      if (response.success) {
        loader.classList.add('d-none');
        const message = document.getElementById('pmf-user-control-panel-response');
        message.insertAdjacentElement(
          'afterend',
          addElement('div', { classList: 'alert alert-success', innerText: response.success })
        );
      }

      if (response.error) {
        loader.classList.add('d-none');
        const message = document.getElementById('pmf-user-control-panel-response');
        message.insertAdjacentElement(
          'afterend',
          addElement('div', { classList: 'alert alert-danger', innerText: response.error })
        );
      }
    });

    const confirmRemoveTwofactor = document.getElementById('pmf-remove-twofactor-confirm');
    if (confirmRemoveTwofactor) {
      confirmRemoveTwofactor.addEventListener('click', async (event) => {
        event.preventDefault();
        const csrfToken = document.getElementById('pmf-csrf-token-remove-twofactor');
        const response = await removeTwofactorConfig(csrfToken.value);
        if (response.success) {
          pushNotification(response.success);
          document.getElementById('twofactor_enabled').checked = false;
          document.getElementById('removeCurrentConfig').style.display = 'none';
        }
        if (response.error) {
          pushErrorNotification(response.error);
        }
      });
    }
  }
};
