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
 * @copyright 2010-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-05-02
 */

import { Modal } from 'bootstrap';
import { fetchAllUsers, fetchUserData, fetchUserRights, deleteUser, postUserData } from '../api';
import { addElement, capitalize } from '../../../../assets/src/utils';
import { pushErrorNotification, pushNotification } from '../utils';

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
  updateInput('modal_user_id', userData.user_id);
  updateInput('auth_source', capitalize(userData.auth_source));
  updateInput('user_status', userData.status);
  updateInput('display_name', userData.display_name);
  updateInput('email', userData.email);
  updateInput('overwrite_twofactor', userData.twofactor_enabled);

  if (userData.is_superadmin) {
    const superAdmin = document.getElementById('is_superadmin');
    superAdmin.setAttribute('checked', 'checked');
  }

  if (userData.twofactor_enabled === '1') {
    const twoFactorEnabled = document.getElementById('overwrite_twofactor');
    twoFactorEnabled.setAttribute('checked', 'checked');
    twoFactorEnabled.removeAttribute('disabled');
  }

  if (userData.status !== 'protected') {
    const deleteUser = document.getElementById('pmf-delete-user');
    deleteUser.classList.remove('disabled');
  }
  const saveUser = document.getElementById('pmf-user-save');
  saveUser.classList.remove('disabled');
};

const setUserRights = async (userId) => {
  const userRights = await fetchUserRights(userId);
  userRights.forEach((right) => {
    const checkbox = document.getElementById(`user_right_${right}`);
    checkbox.setAttribute('checked', 'checked');
  });

  document.getElementById('rights_user_id').value = userId;
};

const clearUserForm = async () => {
  updateInput('current_user_id', '');
  updateInput('pmf-user-list-autocomplete', '');
  updateInput('last_modified', '');
  updateInput('update_user_id', '');
  updateInput('modal_user_id', '');
  updateInput('auth_source', '');
  updateInput('user_status', '');
  updateInput('display_name', '');
  updateInput('email', '');
  updateInput('overwrite_twofactor', '');

  document.querySelectorAll('.permission').forEach((item) => {
    if (item.checked) {
      item.removeAttribute('checked');
    }
  });

  document.getElementById('pmf-user-save').classList.add('disabled');
  document.getElementById('pmf-delete-user').classList.add('disabled');
};

const updateInput = (id, value) => {
  const input = document.getElementById(id);
  if (input) {
    input.value = value;
    input.removeAttribute('disabled');
  }
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
      const automaticPassword = document.getElementById('add_user_automatic_password')?.checked;
      const email = document.getElementById('add_user_email').value;
      const password = document.getElementById('add_user_password').value;
      const passwordConfirm = document.getElementById('add_user_password_confirm').value;
      let isSuperAdmin = document.querySelector('#add_user_is_superadmin');

      if (isSuperAdmin) {
        isSuperAdmin = isSuperAdmin.checked;
      } else {
        isSuperAdmin = false;
      }

      addUserForm.classList.add('was-validated');

      const userData = {
        csrf,
        userName,
        realName,
        email,
        automaticPassword,
        password,
        passwordConfirm,
        isSuperAdmin,
      };

      postUserData('./api/user/add', userData)
        .then(async (response) => {
          if (response.ok) {
            return response.json();
          }
          if (response.status === 400) {
            const json = await response.json();
            json.forEach((item) => {
              pushErrorNotification(item);
            });
          }
          throw new Error('Network response was not ok: ', { cause: { response } });
        })
        .then((response) => {
          modal.style.display = 'none';
          modal.classList.remove('show');
          modalBackdrop[0].parentNode.removeChild(modalBackdrop[0]);
          pushNotification(response.success);
          setTimeout(() => {
            location.reload();
          }, 1500);
        })
        .catch(async (error) => {
          console.error('Error adding user: ' + error);
          throw error;
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
            header.map((fieldName) => JSON.stringify(row[fieldName], replacer)).join(','),
          );
          csv.unshift(header.join(','));
          csv = csv.join('\r\n');

          window.open(encodeURI(csv));

          let hiddenElement = document.createElement('a');
          hiddenElement.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURI(csv));
          hiddenElement.setAttribute('target', '_blank');
          hiddenElement.setAttribute(
            'download',
            'phpmyfaq-users-' + new Date().toISOString().substring(0, 10) + '.csv',
          );
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

    buttonOverwritePassword.addEventListener('click', async (event) => {
      event.preventDefault();

      const csrf = document.getElementById('modal_csrf').value;
      const userId = document.getElementById('modal_user_id').value;
      const newPassword = document.getElementById('npass').value;
      const passwordRepeat = document.getElementById('bpass').value;

      const response = await overwritePassword(csrf, userId, newPassword, passwordRepeat);
      if (response.success) {
        pushNotification(response.success);
        modal.hide();
      }
      if (response.error) {
        pushErrorNotification(response.error);
      }
    });
  }

  // Delete user
  const deleteUserButton = document.getElementById('pmf-delete-user');
  const deleteUser_yes = document.getElementById('pmf-delete-user-yes');

  if (deleteUserButton) {
    deleteUserButton.addEventListener('click', (event) => {
      event.preventDefault();
      const modalDeleteConfirmation = new Modal(document.getElementById('pmf-modal-user-confirm-delete'));
      modalDeleteConfirmation.show();
      const username = document.getElementById('pmf-username-delete');
      const userid = document.getElementById('pmf-user-id-delete');
      username.innerText = document.getElementById('display_name').value;
      userid.value = document.getElementById('current_user_id').value;
      document.getElementById('source_page').value = 'users';
    });
    deleteUser_yes.addEventListener('click', async (event) => {
      event.preventDefault();
      const source = document.getElementById('source_page');
      if (source.value === 'users') {
        const userId = document.getElementById('pmf-user-id-delete').value;
        const csrfToken = document.getElementById('csrf-token-delete-user').value;
        const response = await deleteUser(userId, csrfToken);
        const json = await response.json();
        if (json.success) {
          pushNotification(json.success);
          await clearUserForm();
        }
        if (json.error) {
          pushErrorNotification(json.error);
        }
      }
    });
  }

  // Edit user
  const editUserButton = document.getElementById('pmf-user-save');
  if (editUserButton) {
    editUserButton.addEventListener('click', async (event) => {
      event.preventDefault();
      const userId = document.getElementById('update_user_id').value;
      let userData = {
        csrfToken: document.getElementById('pmf-csrf-token').value,
        display_name: document.getElementById('display_name').value,
        email: document.getElementById('email').value,
        last_modified: document.getElementById('last_modified').value,
        user_status: document.getElementById('user_status').value,
        is_superadmin: document.getElementById('is_superadmin').checked,
        overwrite_twofactor: document.getElementById('overwrite_twofactor').checked,
        userId: userId
      };

      console.log(userData);

      const response = await postUserData('./api/user/edit', userData);
      const json = await response.json();
      if (json.success) {
        pushNotification(json.success);
      }
      if (json.error) {
        pushErrorNotification(json.error);
      }
      await updateUser(userId);
    });
  }

  // Update user rights
  document.querySelectorAll('#pmf-user-rights-save').forEach((item) => {
    item.addEventListener('click', async (event) => {
      event.preventDefault();
      let rightData = [];
      document.querySelectorAll('.permission').forEach(async (checkbox) => {
        if (checkbox.checked) {
          rightData.push(checkbox.value);
        }
      });
      const userId = document.getElementById('rights_user_id').value
      let data = {
        csrfToken: document.getElementById('pmf-csrf-token-rights').value,
        userId: userId,
        userRights: rightData
      }
      const response = await postUserData('./api/user/update-rights', data);
      const json = await response.json();
      if (json.success) {
        pushNotification(json.success);
      }
      if (json.error) {
        pushErrorNotification(json.error);
      }
      await updateUser(userId);
    });
  });
};
