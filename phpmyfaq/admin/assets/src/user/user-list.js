/**
 * Functions for handling user management
 *
 * @todo move fetch() functionality to api.js
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-23
 */

import { addElement } from '../../../../assets/src/utils';
import { pushErrorNotification, pushNotification } from '../utils';
import { deleteUser } from '../api';
import { Modal } from 'bootstrap';

const activateUser = async (userId, csrfToken) => {
  try {
    const response = await fetch('./api/user/activate', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrfToken: csrfToken,
        userId: userId,
      }),
    });

    if (response.status === 200) {
      await response.json();
      const icon = document.querySelector(`.icon_user_id_${userId}`);
      icon.classList.remove('bi-ban');
      icon.classList.add('bi-check-circle-o');
      const button = document.getElementById(`btn_activate_user_id_${userId}`);
      button.remove();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    const message = document.getElementById('pmf-user-message');
    message.insertAdjacentElement(
      'afterend',
      addElement('div', { classList: 'alert alert-danger', innerText: error.message })
    );
  }
};

export const handleUserList = () => {
  const activateButtons = document.querySelectorAll('.btn-activate-user');
  const deleteButtons = document.querySelectorAll('.btn-delete-user');

  if (activateButtons) {
    activateButtons.forEach((button) => {
      button.addEventListener('click', async (event) => {
        event.preventDefault();

        const csrfToken = event.target.getAttribute('data-csrf-token');
        const userId = event.target.getAttribute('data-user-id');

        await activateUser(userId, csrfToken);
      });
    });
  }

  if (deleteButtons) {
    deleteButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();

        const deleteModal = new Modal(document.getElementById('pmf-modal-user-confirm-delete'));
        deleteModal.show();
        document.getElementById('pmf-username-delete').innerText = button.getAttribute('data-username');
        document.getElementById('pmf-user-id-delete').value = button.getAttribute('data-user-id');
        document.getElementById('source_page').value = 'user-list';
      });
    });

    const deleteUserConfirm = document.getElementById('pmf-delete-user-yes');
    if (deleteUserConfirm) {
      deleteUserConfirm.addEventListener('click', async (event) => {
        event.preventDefault();
        const source = document.getElementById('source_page');
        if (source.value === 'user-list') {
          const userId = document.getElementById('pmf-user-id-delete').value;
          const csrfToken = document.getElementById('csrf-token-delete-user').value;
          const response = await deleteUser(userId, csrfToken);
          const json = await response.json();
          if (json.success) {
            pushNotification(json.success);
            const row = document.getElementById('row_user_id_' + userId);
            row.remove();
          }
          if (json.error) {
            pushErrorNotification(json.error);
          }
        }
      });
    }
  }
};
