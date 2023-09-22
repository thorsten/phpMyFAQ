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
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-23
 */

import { addElement } from '../../../../assets/src/utils';

const activateUser = (userId, csrfToken) => {
  fetch('index.php?action=ajax&ajax=user&ajaxaction=activate_user', {
    method: 'POST',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrfToken: csrfToken,
      userId: userId,
    }),
  })
    .then(async (response) => {
      if (response.status === 200) {
        return response.json();
      }
      throw new Error('Network response was not ok.');
    })
    .then(() => {
      const icon = document.querySelector(`.icon_user_id_${userId}`);
      icon.classList.remove('fa-ban');
      icon.classList.add('fa-check-circle-o');
      const button = document.getElementById(`btn_activate_user_id_${userId}`);
      button.remove();
    })
    .catch((error) => {
      const message = document.getElementById('pmf-user-message');
      message.insertAdjacentElement(
        'afterend',
        addElement('div', { classList: 'alert alert-danger', innerText: error })
      );
    });
};

const deleteUser = (userId, csrfToken) => {
  const message = document.getElementById('pmf-user-message');
  fetch('index.php?action=ajax&ajax=user&ajaxaction=delete_user', {
    method: 'DELETE',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrfToken: csrfToken,
      userId: userId,
    }),
  })
    .then(async (response) => {
      if (response.ok) {
        return response.json();
      }
      throw new Error('Network response was not ok: ', { cause: { response } });
    })
    .then((response) => {
      message.insertAdjacentElement('afterend', addElement('div', { innerHTML: response }));
      const row = document.querySelector(`.row_user_id_${userId}`);
      row.addEventListener('click', () => (row.style.opacity = '0'));
      row.addEventListener('transitionend', () => row.remove());
    })
    .catch(async (error) => {
      const errorMessage = await error.cause.response.json();
      message.insertAdjacentElement(
        'afterend',
        addElement('div', { classList: 'alert alert-danger', innerText: errorMessage })
      );
    });
};

export const handleUserList = () => {
  const activateButtons = document.querySelectorAll('.btn-activate-user');
  const deleteButtons = document.querySelectorAll('.btn-delete-user');

  if (activateButtons) {
    activateButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();

        const csrfToken = event.target.getAttribute('data-csrf-token');
        const userId = event.target.getAttribute('data-user-id');

        activateUser(userId, csrfToken);
      });
    });
  }

  if (deleteButtons) {
    deleteButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();

        const csrfToken = event.target.getAttribute('data-csrf-token');
        const userId = event.target.getAttribute('data-user-id');

        deleteUser(userId, csrfToken);
      });
    });
  }
};
