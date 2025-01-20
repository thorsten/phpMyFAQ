/**
 * JavaScript functions for user frontend
 *
 * @todo move fetch() functionality to api functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-05-02
 */

import { Modal } from 'bootstrap';
import { fetchUserData, fetchUserRights, deleteUser, postUserData } from '../api';
import { capitalize, pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

/**
 * Updates the current loaded user
 * @param userId
 */
export const updateUser = async (userId: string): Promise<void> => {
  await setUserData(userId);
  await setUserRights(userId);
};

/**
 * Sets the user data
 * @param {string} userId
 */
const setUserData = async (userId: string): Promise<void> => {
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
    const superAdmin = document.getElementById('is_superadmin') as HTMLInputElement;
    superAdmin.setAttribute('checked', 'checked');
    document.querySelectorAll('.permission').forEach((checkbox) => {
      (checkbox as HTMLInputElement).removeAttribute('disabled');
    });
    document.querySelectorAll('#pmf-user-rights-save').forEach((element) => {
      (element as HTMLButtonElement).removeAttribute('disabled');
    });
    (document.getElementById('checkAll') as HTMLInputElement).removeAttribute('disabled');
    (document.getElementById('uncheckAll') as HTMLInputElement).setAttribute('disabled', '');
  } else {
    const superAdmin = document.getElementById('is_superadmin') as HTMLInputElement;
    superAdmin.removeAttribute('checked');
  }

  if (userData.twofactor_enabled === '1') {
    const twoFactorEnabled = document.getElementById('overwrite_twofactor') as HTMLInputElement;
    twoFactorEnabled.setAttribute('checked', 'checked');
    twoFactorEnabled.removeAttribute('disabled');
  }

  if (userData.status !== 'protected') {
    const deleteUser = document.getElementById('pmf-delete-user') as HTMLButtonElement;
    deleteUser.classList.remove('disabled');
  }
  const saveUser = document.getElementById('pmf-user-save') as HTMLButtonElement;
  saveUser.classList.remove('disabled');

  window.history.pushState({}, '', `./user/edit/${userId}`);
};

const setUserRights = async (userId: string): Promise<void> => {
  clearUserRights();
  const userRights = await fetchUserRights(userId);
  userRights.forEach((right) => {
    const checkbox = document.getElementById(`user_right_${right}`) as HTMLInputElement;
    checkbox.setAttribute('checked', 'checked');
  });

  (document.getElementById('rights_user_id') as HTMLInputElement).value = userId;
};

const clearUserForm = async (): Promise<void> => {
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

  clearUserRights();

  (document.getElementById('pmf-user-save') as HTMLButtonElement).classList.add('disabled');
  (document.getElementById('pmf-delete-user') as HTMLButtonElement).classList.add('disabled');
};

const clearUserRights = (): void => {
  document.querySelectorAll('.permission').forEach((item) => {
    if ((item as HTMLInputElement).checked) {
      (item as HTMLInputElement).removeAttribute('checked');
    }
  });
};

const updateInput = (id: string, value: string): void => {
  const input = document.getElementById(id) as HTMLInputElement;
  if (input) {
    input.value = value;
    input.removeAttribute('disabled');
  }
};

export const handleUsers = async (): Promise<void> => {
  const currentUserId = document.getElementById('current_user_id') as HTMLInputElement;

  if (currentUserId?.value) {
    await updateUser(currentUserId.value);
  }

  const toggleCheckAll = document.getElementById('checkAll') as HTMLInputElement;
  const toggleUncheckAll = document.getElementById('uncheckAll') as HTMLInputElement;
  const modal = document.getElementById('addUserModal') as HTMLElement;
  const modalBackdrop = document.getElementsByClassName('modal-backdrop fade show') as HTMLCollectionOf<HTMLElement>;
  const addUser = document.getElementById('pmf-add-user-action') as HTMLButtonElement;
  const addUserForm = document.getElementById('pmf-add-user-form') as HTMLFormElement;
  const addUserError = document.getElementById('pmf-add-user-error-message') as HTMLElement;
  const passwordToggle = document.getElementById('add_user_automatic_password') as HTMLInputElement;
  const passwordInputs = document.getElementById('add_user_show_password_inputs') as HTMLElement;
  const isSuperAdmin = document.getElementById('is_superadmin') as HTMLInputElement;

  if (isSuperAdmin) {
    isSuperAdmin.addEventListener('click', () => {
      if (isSuperAdmin.checked) {
        document.querySelectorAll('.permission').forEach((checkbox) => {
          (checkbox as HTMLInputElement).removeAttribute('disabled');
        });
        document.querySelectorAll('#pmf-user-rights-save').forEach((element) => {
          (element as HTMLButtonElement).removeAttribute('disabled');
        });
        (document.getElementById('checkAll') as HTMLInputElement).setAttribute('disabled', '');
        (document.getElementById('uncheckAll') as HTMLInputElement).setAttribute('disabled', '');
      } else {
        document.querySelectorAll('.permission').forEach((checkbox) => {
          (checkbox as HTMLInputElement).removeAttribute('disabled');
        });
        document.querySelectorAll('#pmf-user-rights-save').forEach((element) => {
          (element as HTMLButtonElement).removeAttribute('disabled');
        });
        (document.getElementById('checkAll') as HTMLInputElement).removeAttribute('disabled');
        (document.getElementById('uncheckAll') as HTMLInputElement).removeAttribute('disabled');
      }
    });
  }

  if (passwordToggle) {
    passwordToggle.addEventListener('click', () => {
      passwordInputs.classList.toggle('d-none');
    });
  }

  if (toggleCheckAll && toggleUncheckAll) {
    toggleCheckAll.addEventListener('click', () => {
      document.querySelectorAll('.permission').forEach((checkbox) => {
        (checkbox as HTMLInputElement).checked = true;
      });
    });
    toggleUncheckAll.addEventListener('click', () => {
      document.querySelectorAll('.permission').forEach((checkbox) => {
        (checkbox as HTMLInputElement).checked = false;
      });
    });
  }

  if (addUser) {
    addUser.addEventListener('click', (event) => {
      event.preventDefault();
      const csrf = (document.getElementById('add_user_csrf') as HTMLInputElement).value;
      const userName = (document.getElementById('add_user_name') as HTMLInputElement).value;
      const realName = (document.getElementById('add_user_realname') as HTMLInputElement).value;
      const automaticPassword = (document.getElementById('add_user_automatic_password') as HTMLInputElement)?.checked;
      const email = (document.getElementById('add_user_email') as HTMLInputElement).value;
      const password = (document.getElementById('add_user_password') as HTMLInputElement).value;
      const passwordConfirm = (document.getElementById('add_user_password_confirm') as HTMLInputElement).value;
      let isSuperAdmin = document.querySelector('#add_user_is_superadmin') as HTMLInputElement;

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
            json.forEach((item: string) => {
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

  const buttonExportAllUsers = document.getElementById('pmf-button-export-users') as HTMLButtonElement;

  if (buttonExportAllUsers) {
    buttonExportAllUsers.addEventListener('click', (event) => {
      event.preventDefault();
      window.location.href = './api/user/users/csv';
    });
  }

  const buttonOverwritePassword = document.getElementById('pmf-user-password-overwrite-action') as HTMLButtonElement;
  const container = document.getElementById('pmf-modal-user-password-overwrite') as HTMLElement;

  if (buttonOverwritePassword) {
    const modal = new Modal(container);

    buttonOverwritePassword.addEventListener('click', async (event) => {
      event.preventDefault();

      const csrf = (document.getElementById('modal_csrf') as HTMLInputElement).value;
      const userId = (document.getElementById('modal_user_id') as HTMLInputElement).value;
      const newPassword = (document.getElementById('npass') as HTMLInputElement).value;
      const passwordRepeat = (document.getElementById('bpass') as HTMLInputElement).value;

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
  const deleteUserButton = document.getElementById('pmf-delete-user') as HTMLButtonElement;
  const deleteUserConfirmed = document.getElementById('pmf-delete-user-yes') as HTMLButtonElement;

  if (deleteUserButton) {
    deleteUserButton.addEventListener('click', (event) => {
      event.preventDefault();
      const modalDeleteConfirmation = new Modal(
        document.getElementById('pmf-modal-user-confirm-delete') as HTMLElement
      );
      modalDeleteConfirmation.show();
      const username = document.getElementById('pmf-username-delete') as HTMLElement;
      const userid = document.getElementById('pmf-user-id-delete') as HTMLInputElement;
      username.innerText = (document.getElementById('display_name') as HTMLInputElement).value;
      userid.value = (document.getElementById('current_user_id') as HTMLInputElement).value;
      (document.getElementById('source_page') as HTMLInputElement).value = 'users';
    });
    deleteUserConfirmed.addEventListener('click', async (event) => {
      event.preventDefault();
      const source = document.getElementById('source_page') as HTMLInputElement;
      if (source.value === 'users') {
        const userId = (document.getElementById('pmf-user-id-delete') as HTMLInputElement).value;
        const csrfToken = (document.getElementById('csrf-token-delete-user') as HTMLInputElement).value;
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
  const editUserButton = document.getElementById('pmf-user-save') as HTMLButtonElement;
  if (editUserButton) {
    editUserButton.addEventListener('click', async (event) => {
      event.preventDefault();
      const userId = (document.getElementById('update_user_id') as HTMLInputElement).value;
      let userData = {
        csrfToken: (document.getElementById('pmf-csrf-token') as HTMLInputElement).value,
        display_name: (document.getElementById('display_name') as HTMLInputElement).value,
        email: (document.getElementById('email') as HTMLInputElement).value,
        last_modified: (document.getElementById('last_modified') as HTMLInputElement).value,
        user_status: (document.getElementById('user_status') as HTMLInputElement).value,
        is_superadmin: (document.getElementById('is_superadmin') as HTMLInputElement).checked,
        overwrite_twofactor: (document.getElementById('overwrite_twofactor') as HTMLInputElement).checked,
        userId: userId,
      };

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
      let rightData: string[] = [];
      document.querySelectorAll('.permission').forEach(async (checkbox) => {
        if ((checkbox as HTMLInputElement).checked) {
          rightData.push((checkbox as HTMLInputElement).value);
        }
      });
      const userId = (document.getElementById('rights_user_id') as HTMLInputElement).value;
      let data = {
        csrfToken: (document.getElementById('pmf-csrf-token-rights') as HTMLInputElement).value,
        userId: userId,
        userRights: rightData,
      };
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
