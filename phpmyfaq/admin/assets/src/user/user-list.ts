/**
 * Functions for handling user management
 *
 * @todo move fetch() functionality to api functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-23
 */

import { addElement, pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { deleteUser } from '../api';
import { Modal } from 'bootstrap';

const activateUser = async (userId: string, csrfToken: string): Promise<void> => {
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
      const icon = document.querySelector(`.icon_user_id_${userId}`) as HTMLElement;
      icon.classList.remove('bi-ban');
      icon.classList.add('bi-check-circle-o');
      const button = document.getElementById(`btn_activate_user_id_${userId}`) as HTMLElement;
      button.remove();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    const message = document.getElementById('pmf-user-message') as HTMLElement;
    message.insertAdjacentElement(
      'afterend',
      addElement('div', { classList: 'alert alert-danger', innerText: (error as Error).message })
    );
  }
};

export const handleUserList = (): void => {
  const activateButtons = document.querySelectorAll('.btn-activate-user');
  const deleteButtons = document.querySelectorAll('.btn-delete-user');

  if (activateButtons) {
    activateButtons.forEach((button) => {
      button.addEventListener('click', async (event) => {
        event.preventDefault();

        const target = event.target as HTMLElement;
        const csrfToken = target.getAttribute('data-csrf-token')!;
        const userId = target.getAttribute('data-user-id')!;

        await activateUser(userId, csrfToken);
      });
    });
  }

  if (deleteButtons) {
    deleteButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();

        const deleteModal = new Modal(document.getElementById('pmf-modal-user-confirm-delete') as HTMLElement);
        deleteModal.show();
        const usernameDelete = document.getElementById('pmf-username-delete') as HTMLElement;
        usernameDelete.innerText = button.getAttribute('data-username')!;
        const userIdDelete = document.getElementById('pmf-user-id-delete') as HTMLInputElement;
        userIdDelete.value = button.getAttribute('data-user-id')!;
        const sourcePage = document.getElementById('source_page') as HTMLInputElement;
        sourcePage.value = 'user-list';
      });
    });

    const deleteUserConfirm = document.getElementById('pmf-delete-user-yes');
    if (deleteUserConfirm) {
      deleteUserConfirm.addEventListener('click', async (event) => {
        event.preventDefault();
        const source = document.getElementById('source_page') as HTMLInputElement;
        if (source.value === 'user-list') {
          const userId = (document.getElementById('pmf-user-id-delete') as HTMLInputElement).value;
          const csrfToken = (document.getElementById('csrf-token-delete-user') as HTMLInputElement).value;
          const response = await deleteUser(userId, csrfToken);
          const json = await response.json();
          if (json.success) {
            pushNotification(json.success);
            const row = document.getElementById('row_user_id_' + userId) as HTMLElement;
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
