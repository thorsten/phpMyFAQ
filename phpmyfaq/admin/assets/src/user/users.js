/**
 * JavaScript functions for user frontend
 *
 * @todo move fetch() functionality to api.js
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-05-02
 */

import { Modal } from 'bootstrap';
import { userAutoComplete } from './autocomplete';
import { fetchAllUsers, fetchUserData, fetchUserRights, postUserData } from './api';
import { addElement } from '../../../../assets/src/utils';

/**
 * Updates the current loaded user
 * @param userId
 */
export const updateUser = async (userId) => {
  await setUserData(userId);
  await setUserRights(userId);
};

/**
 * Sets the user data
 * @param {string} userId
 */
const setUserData = async (userId) => {
  const userData = await fetchUserData(userId);

  updateInput('current_user_id', userData.user_id);
  updateInput('pmf-user-list-autocomplete', userData.login);
  updateInput('last_modified', userData.last_modified);
  updateInput('update_user_id', userData.user_id);
  updateInput('user_status', userData.status);
  updateInput('display_name', userData.display_name);
  updateInput('email', userData.email);

  if (userData.is_superadmin) {
    const superAdmin = document.getElementById('is_superadmin');
    superAdmin.setAttribute('checked', 'checked');
    superAdmin.removeAttribute('disabled');
  }
};

const setUserRights = async (userId) => {
  const userRights = await fetchUserRights(userId);
  userRights.forEach((right) => {
    const checkbox = document.getElementById(`user_right_${right}`);
    checkbox.setAttribute('checked', 'checked');
  });

  document.getElementById('rights_user_id').value = userId;
};

const updateInput = (id, value) => {
  const input = document.getElementById(id);
  input.value = value;
  input.removeAttribute('disabled');
};

export const handleUsers = async () => {
  const currentUserId = document.getElementById('current_user_id');

  if (currentUserId?.value) {
    await updateUser(currentUserId.value);
  }

  const toggleUserRights = document.getElementById('checkAll');
  const modal = document.getElementById('addUserModal');
  const modalBackdrop = document.getElementsByClassName('modal-backdrop fade show');
  const addUser = document.getElementById('pmf-add-user-action');
  const addUserForm = document.getElementById('pmf-add-user-form');
  const addUserError = document.getElementById('pmf-add-user-error-message');
  const addUserMessage = document.getElementById('pmf-user-message');
  const passwordToggle = document.getElementById('add_user_automatic_password');
  const passwordInputs = document.getElementById('add_user_show_password_inputs');

  if (passwordToggle) {
    passwordToggle.addEventListener('click', () => {
      passwordInputs.classList.toggle('d-none');
    });
  }

  if (toggleUserRights) {
    toggleUserRights.addEventListener('click', (event) => {
      event.preventDefault();

      const checkboxes = document.querySelectorAll('.permission');
      checkboxes.forEach((checkbox) => {
        checkbox.checked = !checkbox.checked;
      });
    });
  }

  if (addUser) {
    addUser.addEventListener('click', (event) => {
      event.preventDefault();
      const csrf = document.getElementById('add_user_csrf').value;
      const userName = document.getElementById('add_user_name').value;
      const realName = document.getElementById('add_user_realname').value;
      const email = document.getElementById('add_user_email').value;
      const password = document.getElementById('add_user_password').value;
      const passwordConfirm = document.getElementById('add_user_password_confirm').value;
      const isSuperAdmin = document.querySelector('#add_user_is_superadmin').checked;

      addUserForm.classList.add('was-validated');

      const userData = {
        userName,
        realName,
        email,
        password,
        passwordConfirm,
        isSuperAdmin,
      };

      postUserData('index.php?action=ajax&ajax=user&ajaxaction=add_user&csrf=' + csrf, userData)
        .then(async (response) => {
          if (response.status !== 201) {
            const errors = await response.json();
            let errorMessage = '';

            errors.forEach((error) => {
              errorMessage += `${error}<br>`;
            });

            addUserError.classList.remove('d-none');
            addUserError.innerHTML = errorMessage;
          } else {
            const result = await response.json();

            addUserMessage.innerHTML = `<p class="alert alert-success">${result.data}</p>`;

            modal.style.display = 'none';
            modal.classList.remove('show');
            modalBackdrop[0].parentNode.removeChild(modalBackdrop[0]);
          }
        })
        .catch((error) => {
          console.log('Final Request failure: ', error);
        });
    });
  }

  const buttonExportAllUsers = document.getElementById('pmf-button-export-users');

  if (buttonExportAllUsers) {
    buttonExportAllUsers.addEventListener('click', async (event) => {
      event.preventDefault();

      await fetchAllUsers()
        .then((userData) => {
          const replacer = (key, value) => (value === null ? '' : value);
          const header = Object.keys(userData[0]);
          let csv = userData.map((row) =>
            header.map((fieldName) => JSON.stringify(row[fieldName], replacer)).join(',')
          );
          csv.unshift(header.join(','));
          csv = csv.join('\r\n');

          let hiddenElement = document.createElement('a');
          hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURI(csv);
          hiddenElement.target = '_blank';
          hiddenElement.download = 'phpmyfaq-users-' + new Date().toISOString().substring(0, 10) + '.csv';
          hiddenElement.click();
        })
        .catch((error) => {
          console.log('Final Request failure: ', error);
        });
    });
  }

  const buttonOverwritePassword = document.getElementById('pmf-user-password-overwrite-action');
  const container = document.getElementById('pmf-modal-user-password-overwrite');

  if (buttonOverwritePassword) {
    const modal = new Modal(container);
    const message = document.getElementById('pmf-user-message');

    buttonOverwritePassword.addEventListener('click', (event) => {
      event.preventDefault();

      const csrf = document.getElementById('modal_csrf').value;
      const userId = document.getElementById('modal_user_id').value;
      const newPassword = document.getElementById('npass').value;
      const passwordRepeat = document.getElementById('bpass').value;

      fetch('index.php?action=ajax&ajax=user&ajaxaction=overwrite_password', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          csrf: csrf,
          userId: userId,
          newPassword: newPassword,
          passwordRepeat: passwordRepeat,
        }),
      })
        .then(async (response) => {
          if (response.status === 200) {
            return response.json();
          }
          throw new Error('Network response was not ok.');
        })
        .then((response) => {
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-success', innerText: response.success })
          );
          modal.hide();
        })
        .catch((error) => {
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-danger', innerText: error })
          );
        });
    });
  }
};
