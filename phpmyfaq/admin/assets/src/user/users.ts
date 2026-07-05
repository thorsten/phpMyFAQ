/**
 * Master-detail user administration page
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-05-02
 */

import { Modal } from 'bootstrap';
import {
  deleteUser,
  fetchAllUsers,
  fetchUserData,
  fetchUserRights,
  fetchUsers,
  overwritePassword,
  updateUserData,
  updateUserRights,
} from '../api';
import { capitalize, pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { ApiResponse, UserAutocomplete, UserData, UserOverview } from '../interfaces';
import { wireAddUserModal } from './add-user';

interface UserListEntry {
  id: string;
  name: string;
  login: string;
  status?: string;
}

const INITIAL_LIST_SIZE = 50;
const FILTER_DEBOUNCE_MS = 300;

let selectedUserId = '';
let selectRequestToken = 0;
let filterDebounce: ReturnType<typeof setTimeout> | undefined;

const getGenericErrorMessage = (): string => {
  return (document.getElementById('pmf-user-detail') as HTMLElement | null)?.dataset.msgError || 'An error occurred.';
};

export const handleUsers = async (): Promise<void> => {
  const userList = document.getElementById('pmf-user-list');
  if (!userList) {
    return;
  }

  await refreshUserList('');

  wireUserFilter();
  wirePermissionFilter();
  wirePermissionToggles();
  wireSaveButtons();
  wireDeleteModal();
  wirePasswordOverwrite();
  wireAddUserModal(async (userName: string): Promise<void> => {
    await refreshUserList('');
    const newItem = [...document.querySelectorAll<HTMLButtonElement>('.pmf-user-item')].find(
      (item: HTMLButtonElement): boolean => item.dataset.login === userName
    );
    newItem?.click();
  });

  const currentUserId = (document.getElementById('current_user_id') as HTMLInputElement | null)?.value;
  if (currentUserId) {
    try {
      await selectUser(currentUserId);
    } catch {
      pushErrorNotification(getGenericErrorMessage());
    }
  }
};

const toListEntries = (users: UserOverview[]): UserListEntry[] => {
  return users.map(
    (user: UserOverview): UserListEntry => ({
      id: String(user.id),
      name: user.displayName,
      login: user.userName,
      status: user.status,
    })
  );
};

const searchResultsToEntries = (results: UserAutocomplete[]): UserListEntry[] => {
  return results.map(
    (result: UserAutocomplete): UserListEntry => ({
      id: String(result.value),
      name: result.label,
      login: result.label,
    })
  );
};

const refreshUserList = async (filter: string): Promise<void> => {
  const entries =
    filter === ''
      ? toListEntries(await fetchAllUsers()).slice(0, INITIAL_LIST_SIZE)
      : searchResultsToEntries(await fetchUsers(filter));
  renderUserList(entries);
};

const renderUserList = (entries: UserListEntry[]): void => {
  const userList = document.getElementById('pmf-user-list') as HTMLElement;
  userList.textContent = '';

  entries.forEach((entry: UserListEntry): void => {
    const item = document.createElement('button');
    item.type = 'button';
    item.className = 'list-group-item list-group-item-action pmf-user-item';
    item.dataset.userId = entry.id;
    item.dataset.login = entry.login;
    item.classList.toggle('active', entry.id === selectedUserId);

    const name = document.createElement('span');
    name.className = 'd-block';
    name.textContent = entry.name;
    item.appendChild(name);

    if (entry.status === 'blocked') {
      const badge = document.createElement('span');
      badge.className = 'badge text-bg-danger ms-2';
      badge.textContent = userList.dataset.labelBlocked || 'blocked';
      name.appendChild(badge);
    }

    if (entry.login !== entry.name) {
      const login = document.createElement('small');
      login.className = 'text-body-secondary';
      login.textContent = entry.login;
      item.appendChild(login);
    }

    item.addEventListener('click', async (): Promise<void> => {
      try {
        await selectUser(entry.id);
      } catch {
        pushErrorNotification(getGenericErrorMessage());
      }
    });
    userList.appendChild(item);
  });
};

const wireUserFilter = (): void => {
  const filter = document.getElementById('pmf-user-filter') as HTMLInputElement;
  filter.addEventListener('input', (): void => {
    if (filterDebounce) {
      clearTimeout(filterDebounce);
    }
    filterDebounce = setTimeout(async (): Promise<void> => {
      try {
        await refreshUserList(filter.value.trim());
      } catch {
        pushErrorNotification(getGenericErrorMessage());
      }
    }, FILTER_DEBOUNCE_MS);
  });
};

const selectUser = async (userId: string): Promise<void> => {
  const requestToken = ++selectRequestToken;
  selectedUserId = userId;

  document.querySelectorAll<HTMLButtonElement>('.pmf-user-item').forEach((item: HTMLButtonElement): void => {
    item.classList.toggle('active', item.dataset.userId === userId);
  });

  // Hide the detail card while another user's data streams in, so a save
  // cannot submit the previous user's still-displayed values.
  (document.getElementById('pmf-user-detail') as HTMLElement).classList.add('d-none');

  const userData: UserData = await fetchUserData(userId);
  if (requestToken !== selectRequestToken) {
    return;
  }
  fillProfile(userData);

  await loadUserRights(userId);
  if (requestToken !== selectRequestToken) {
    return;
  }

  (document.getElementById('pmf-user-empty-state') as HTMLElement).classList.add('d-none');
  (document.getElementById('pmf-user-detail') as HTMLElement).classList.remove('d-none');

  window.history.pushState({}, '', `./user/edit/${userId}`);
};

const fillProfile = (userData: UserData): void => {
  (document.getElementById('pmf-selected-user-name') as HTMLElement).textContent = userData.displayName;
  (document.getElementById('pmf-selected-user-login') as HTMLElement).textContent = userData.login;
  (document.getElementById('auth_source') as HTMLInputElement).value = capitalize(userData.authSource);
  (document.getElementById('user_status') as HTMLSelectElement).value = userData.status;
  (document.getElementById('display_name') as HTMLInputElement).value = userData.displayName;
  (document.getElementById('email') as HTMLInputElement).value = userData.email;
  (document.getElementById('last_modified') as HTMLInputElement).value = userData.lastModified;
  (document.getElementById('is_superadmin') as HTMLInputElement).checked = Boolean(userData.isSuperadmin);

  // The reset checkbox is only actionable when the user actually has 2FA enabled.
  const twoFactor = document.getElementById('overwrite_twofactor') as HTMLInputElement;
  twoFactor.checked = false;
  twoFactor.disabled = !userData.twoFactorEnabled;

  // Protected accounts (e.g. the main admin) cannot be deleted.
  const deleteButton = document.getElementById('pmf-delete-user-button') as HTMLButtonElement | null;
  deleteButton?.classList.toggle('d-none', userData.status === 'protected');
};

const loadUserRights = async (userId: string): Promise<void> => {
  document
    .querySelectorAll<HTMLInputElement>('#pmf-user-permission-list input.permission')
    .forEach((checkbox: HTMLInputElement): void => {
      checkbox.checked = false;
    });

  const userRights: string[] = await fetchUserRights(userId);
  userRights.forEach((right: string): void => {
    const checkbox = document.getElementById(`user_right_${right}`) as HTMLInputElement | null;
    if (checkbox) {
      checkbox.checked = true;
    }
  });
};

const wirePermissionFilter = (): void => {
  const filter = document.getElementById('pmf-user-permission-filter') as HTMLInputElement;
  filter.addEventListener('input', (): void => {
    const query = filter.value.toLowerCase().trim();
    document
      .querySelectorAll<HTMLElement>('#pmf-user-permission-list .form-check')
      .forEach((item: HTMLElement): void => {
        const label = item.querySelector('label')?.textContent?.toLowerCase() || '';
        item.classList.toggle('d-none', !label.includes(query));
      });
  });
};

const wirePermissionToggles = (): void => {
  const setAll = (checked: boolean): void => {
    document
      .querySelectorAll<HTMLInputElement>('#pmf-user-permission-list input.permission')
      .forEach((checkbox: HTMLInputElement): void => {
        checkbox.checked = checked;
      });
  };

  (document.getElementById('pmf-user-check-all') as HTMLButtonElement).addEventListener('click', (): void => {
    setAll(true);
  });
  (document.getElementById('pmf-user-uncheck-all') as HTMLButtonElement).addEventListener('click', (): void => {
    setAll(false);
  });
};

const notifyResult = (response: ApiResponse): void => {
  if (response.success) {
    pushNotification(response.success);
  } else {
    pushErrorNotification(response.error ?? getGenericErrorMessage());
  }
};

const wireSaveButtons = (): void => {
  const detail = document.getElementById('pmf-user-detail') as HTMLElement;

  (document.getElementById('pmf-user-save') as HTMLButtonElement).addEventListener('click', async (): Promise<void> => {
    if (selectedUserId === '') {
      return;
    }
    const payload = {
      csrfToken: detail.dataset.csrfUpdate || '',
      userId: selectedUserId,
      display_name: (document.getElementById('display_name') as HTMLInputElement).value.trim(),
      email: (document.getElementById('email') as HTMLInputElement).value.trim(),
      last_modified: (document.getElementById('last_modified') as HTMLInputElement).value,
      user_status: (document.getElementById('user_status') as HTMLSelectElement).value,
      is_superadmin: (document.getElementById('is_superadmin') as HTMLInputElement).checked,
      overwrite_twofactor: (document.getElementById('overwrite_twofactor') as HTMLInputElement).checked,
    };

    try {
      const response = await updateUserData(payload);
      notifyResult(response);
      if (response.success) {
        // Reload so the header, sidebar entry, and last_modified reflect the save.
        await selectUser(selectedUserId);
        await refreshUserList((document.getElementById('pmf-user-filter') as HTMLInputElement).value.trim());
      }
    } catch {
      pushErrorNotification(getGenericErrorMessage());
    }
  });

  (document.getElementById('pmf-user-rights-save') as HTMLButtonElement).addEventListener(
    'click',
    async (): Promise<void> => {
      if (selectedUserId === '') {
        return;
      }
      const rights = [
        ...document.querySelectorAll<HTMLInputElement>('#pmf-user-permission-list input.permission:checked'),
      ].map((checkbox: HTMLInputElement): string => checkbox.value);

      try {
        const response = await updateUserRights(selectedUserId, rights, detail.dataset.csrfRights || '');
        notifyResult(response);
      } catch {
        pushErrorNotification(getGenericErrorMessage());
      }
    }
  );
};

const wireDeleteModal = (): void => {
  const deleteButton = document.getElementById('pmf-delete-user-button') as HTMLButtonElement | null;
  const confirmButton = document.getElementById('pmf-delete-user-yes') as HTMLButtonElement | null;
  if (!deleteButton || !confirmButton) {
    return;
  }
  const detail = document.getElementById('pmf-user-detail') as HTMLElement;

  deleteButton.addEventListener('click', (): void => {
    (document.getElementById('pmf-username-delete') as HTMLElement).textContent = (
      document.getElementById('display_name') as HTMLInputElement
    ).value;
    (document.getElementById('pmf-user-id-delete') as HTMLInputElement).value = selectedUserId;
    (document.getElementById('source_page') as HTMLInputElement).value = 'users';
    Modal.getOrCreateInstance(document.getElementById('pmf-modal-user-confirm-delete') as HTMLElement).show();
  });

  confirmButton.addEventListener('click', async (): Promise<void> => {
    // The same modal serves the user-list page; only act for this page's flow.
    if ((document.getElementById('source_page') as HTMLInputElement).value !== 'users' || selectedUserId === '') {
      return;
    }
    try {
      const response = await deleteUser(selectedUserId, detail.dataset.csrfDelete || '');
      if (response.success) {
        pushNotification(response.success);
        selectedUserId = '';
        detail.classList.add('d-none');
        (document.getElementById('pmf-user-empty-state') as HTMLElement).classList.remove('d-none');
        (document.getElementById('pmf-user-filter') as HTMLInputElement).value = '';
        await refreshUserList('');
      } else {
        pushErrorNotification(response.error ?? getGenericErrorMessage());
      }
    } catch {
      pushErrorNotification(getGenericErrorMessage());
    }
  });
};

const wirePasswordOverwrite = (): void => {
  const actionButton = document.getElementById('pmf-user-password-overwrite-action') as HTMLButtonElement | null;
  if (!actionButton) {
    return;
  }
  const modalElement = document.getElementById('pmf-modal-user-password-overwrite') as HTMLElement;

  actionButton.addEventListener('click', async (event: Event): Promise<void> => {
    event.preventDefault();
    if (selectedUserId === '') {
      return;
    }
    const csrf = (document.getElementById('modal_csrf') as HTMLInputElement).value;
    const newPassword = document.getElementById('npass') as HTMLInputElement;
    const passwordRepeat = document.getElementById('bpass') as HTMLInputElement;

    try {
      const response = await overwritePassword(csrf, selectedUserId, newPassword.value, passwordRepeat.value);
      if (response.success) {
        pushNotification(response.success);
        Modal.getOrCreateInstance(modalElement).hide();
        newPassword.value = '';
        passwordRepeat.value = '';
      } else {
        pushErrorNotification(response.error ?? getGenericErrorMessage());
      }
    } catch {
      pushErrorNotification(getGenericErrorMessage());
    }
  });
};
