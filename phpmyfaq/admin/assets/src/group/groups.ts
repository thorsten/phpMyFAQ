/**
 * Functions for handling group management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-01-04
 */

import { Modal } from 'bootstrap';
import {
  deleteGroup,
  fetchAllGroups,
  fetchAllMembers,
  fetchAllUsersForGroups,
  fetchCategoriesForRestrictions,
  fetchGroup,
  fetchGroupCategoryRestrictions,
  fetchGroupRights,
  saveGroupCategoryRestrictions,
  updateGroup,
  updateGroupMembers,
  updateGroupPermissions,
} from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { ApiResponse, CategoryItem, CategoryRestrictions, Group, User } from '../interfaces';

let allUsers: User[] = [];
let selectedGroupId: string = '';
let selectRequestToken = 0;

const getGenericErrorMessage = (): string => {
  return (document.getElementById('pmf-group-detail') as HTMLElement | null)?.dataset.msgError || 'An error occurred.';
};

export const handleGroups = async (): Promise<void> => {
  const groupList = document.getElementById('pmf-group-list');
  if (!groupList) {
    return;
  }

  allUsers = await fetchAllUsersForGroups();
  await refreshGroupList();

  wireGroupFilter();
  wireMemberSearch();
  wirePermissionFilter();
  wirePermissionToggles();
  wireSaveButtons();
  wireDeleteModal();
};

const applyGroupFilter = (query: string): void => {
  document.querySelectorAll<HTMLButtonElement>('.pmf-group-item').forEach((item: HTMLButtonElement): void => {
    item.classList.toggle('d-none', !(item.textContent || '').toLowerCase().includes(query));
  });
};

const refreshGroupList = async (): Promise<void> => {
  const groupList = document.getElementById('pmf-group-list') as HTMLElement;
  const groups: Group[] = await fetchAllGroups();

  groupList.textContent = '';
  groups.forEach((group: Group): void => {
    const item = document.createElement('button');
    item.type = 'button';
    item.className = 'list-group-item list-group-item-action pmf-group-item';
    item.dataset.groupId = group.group_id;
    item.textContent = group.name;
    item.addEventListener('click', async (): Promise<void> => {
      try {
        await selectGroup(group.group_id);
      } catch {
        pushErrorNotification(getGenericErrorMessage());
      }
    });
    groupList.appendChild(item);
  });

  const filter = document.getElementById('pmf-group-filter') as HTMLInputElement | null;
  if (filter) {
    applyGroupFilter(filter.value.toLowerCase().trim());
  }
};

const selectGroup = async (groupId: string): Promise<void> => {
  const requestToken = ++selectRequestToken;
  selectedGroupId = groupId;

  document.querySelectorAll<HTMLButtonElement>('.pmf-group-item').forEach((item: HTMLButtonElement): void => {
    item.classList.toggle('active', item.dataset.groupId === groupId);
  });

  const group: Group = await fetchGroup(groupId);
  if (requestToken !== selectRequestToken) {
    return;
  }
  (document.getElementById('update_group_name') as HTMLInputElement).value = group.name;
  (document.getElementById('update_group_description') as HTMLTextAreaElement).value = group.description || '';
  (document.getElementById('update_group_auto_join') as HTMLInputElement).checked =
    1 === parseInt(group.auto_join || '0');
  (document.getElementById('pmf-selected-group-name') as HTMLElement).textContent = group.name;

  await loadGroupRights(groupId);
  if (requestToken !== selectRequestToken) {
    return;
  }
  await loadMembers(groupId);
  if (requestToken !== selectRequestToken) {
    return;
  }
  try {
    await loadCategoryRestrictions(groupId);
  } catch (error) {
    console.error('Failed to load category restrictions:', error);
    currentRestrictions = {};
    const container = document.getElementById('categoryRestrictionsBody');
    if (container) {
      container.innerHTML = '';
      const errorParagraph = document.createElement('p');
      errorParagraph.className = 'text-body-secondary';
      errorParagraph.textContent = container.dataset.msgEmpty || 'No permissions assigned to this group.';
      container.appendChild(errorParagraph);
    }
  }
  if (requestToken !== selectRequestToken) {
    return;
  }

  (document.getElementById('pmf-group-empty-state') as HTMLElement).classList.add('d-none');
  (document.getElementById('pmf-group-detail') as HTMLElement).classList.remove('d-none');
};

const loadGroupRights = async (groupId: string): Promise<void> => {
  document
    .querySelectorAll<HTMLInputElement>('#pmf-permission-list input.permission')
    .forEach((checkbox: HTMLInputElement): void => {
      checkbox.checked = false;
    });

  const groupRights: string[] = await fetchGroupRights(groupId);
  groupRights.forEach((right: string): void => {
    const checkbox = document.getElementById(`group_right_${right}`) as HTMLInputElement | null;
    if (checkbox) {
      checkbox.checked = true;
    }
  });
};

const loadMembers = async (groupId: string): Promise<void> => {
  const memberList = document.getElementById('pmf-member-list') as HTMLElement;
  memberList.textContent = '';

  const members = await fetchAllMembers(groupId);
  members.forEach((member: User): void => {
    memberList.appendChild(createMemberRow(member));
  });
  updateMemberCount();

  const search = document.getElementById('pmf-user-search') as HTMLInputElement | null;
  if (search) {
    search.value = '';
  }
  const results = document.getElementById('pmf-user-search-results') as HTMLElement | null;
  if (results) {
    results.textContent = '';
    results.classList.add('d-none');
  }
};

const createMemberRow = (user: User): HTMLLIElement => {
  const memberList = document.getElementById('pmf-member-list') as HTMLElement;
  const row = document.createElement('li');
  row.className = 'list-group-item d-flex justify-content-between align-items-center';
  row.dataset.userId = user.user_id;

  const login = document.createElement('span');
  login.textContent = user.login;
  row.appendChild(login);

  const removeButton = document.createElement('button');
  removeButton.type = 'button';
  removeButton.className = 'btn btn-outline-danger btn-sm';
  removeButton.setAttribute('aria-label', memberList.dataset.labelRemove || 'Remove member');
  removeButton.innerHTML = '<i aria-hidden="true" class="bi bi-person-dash"></i>';
  removeButton.addEventListener('click', (): void => {
    row.remove();
    updateMemberCount();
  });
  row.appendChild(removeButton);

  return row;
};

const updateMemberCount = (): void => {
  const count = document.querySelectorAll('#pmf-member-list li').length;
  (document.getElementById('pmf-member-count') as HTMLElement).textContent = count.toString();
};

const wireGroupFilter = (): void => {
  const filter = document.getElementById('pmf-group-filter') as HTMLInputElement;
  filter.addEventListener('input', (): void => {
    applyGroupFilter(filter.value.toLowerCase().trim());
  });
};

const wireMemberSearch = (): void => {
  const search = document.getElementById('pmf-user-search') as HTMLInputElement;
  const results = document.getElementById('pmf-user-search-results') as HTMLElement;

  search.addEventListener('input', (): void => {
    const query = search.value.toLowerCase().trim();
    results.textContent = '';

    if (query === '') {
      results.classList.add('d-none');
      return;
    }

    const memberIds = new Set(
      [...document.querySelectorAll<HTMLElement>('#pmf-member-list li')].map((row: HTMLElement) => row.dataset.userId)
    );
    const candidates = allUsers
      .filter((user: User): boolean => user.login.toLowerCase().includes(query) && !memberIds.has(user.user_id))
      .slice(0, 10);

    if (candidates.length === 0) {
      results.classList.add('d-none');
      return;
    }

    candidates.forEach((user: User): void => {
      const suggestion = document.createElement('button');
      suggestion.type = 'button';
      suggestion.className = 'list-group-item list-group-item-action';
      suggestion.textContent = user.login;
      suggestion.addEventListener('click', (): void => {
        (document.getElementById('pmf-member-list') as HTMLElement).appendChild(createMemberRow(user));
        updateMemberCount();
        search.value = '';
        results.textContent = '';
        results.classList.add('d-none');
      });
      results.appendChild(suggestion);
    });
    results.classList.remove('d-none');
  });
};

const wirePermissionFilter = (): void => {
  const filter = document.getElementById('pmf-permission-filter') as HTMLInputElement;
  filter.addEventListener('input', (): void => {
    const query = filter.value.toLowerCase().trim();
    document.querySelectorAll<HTMLElement>('#pmf-permission-list .form-check').forEach((item: HTMLElement): void => {
      const label = item.querySelector('label')?.textContent?.toLowerCase() || '';
      item.classList.toggle('d-none', !label.includes(query));
    });
  });
};

const wirePermissionToggles = (): void => {
  const setAll = (checked: boolean): void => {
    document
      .querySelectorAll<HTMLInputElement>('#pmf-permission-list input.permission')
      .forEach((checkbox: HTMLInputElement): void => {
        checkbox.checked = checked;
      });
    refreshRestrictionsPanel();
  };

  (document.getElementById('pmf-group-check-all') as HTMLButtonElement).addEventListener('click', (): void => {
    setAll(true);
  });
  (document.getElementById('pmf-group-uncheck-all') as HTMLButtonElement).addEventListener('click', (): void => {
    setAll(false);
  });

  document.getElementById('pmf-permission-list')?.addEventListener('change', (event: Event): void => {
    const target = event.target as HTMLInputElement;
    if (target.type === 'checkbox' && target.classList.contains('permission')) {
      refreshRestrictionsPanel();
    }
  });
};

const refreshRestrictionsPanel = (): void => {
  const container = document.getElementById('categoryRestrictionsBody');
  if (container) {
    captureCurrentRestrictions(container);
    renderCategoryRestrictions(container);
  }
};

const notifyResult = (response: ApiResponse): void => {
  if (response.success) {
    pushNotification(response.success);
  } else {
    pushErrorNotification(response.error ?? getGenericErrorMessage());
  }
};

const wireSaveButtons = (): void => {
  const detail = document.getElementById('pmf-group-detail') as HTMLElement;

  (document.getElementById('saveGroupDetails') as HTMLButtonElement).addEventListener(
    'click',
    async (): Promise<void> => {
      if (selectedGroupId === '') {
        return;
      }
      const name = (document.getElementById('update_group_name') as HTMLInputElement).value.trim();
      const description = (document.getElementById('update_group_description') as HTMLTextAreaElement).value;
      const autoJoin = (document.getElementById('update_group_auto_join') as HTMLInputElement).checked;

      try {
        const response = await updateGroup(
          selectedGroupId,
          name,
          description,
          autoJoin,
          detail.dataset.csrfUpdate || ''
        );
        if (response.success) {
          (document.getElementById('pmf-selected-group-name') as HTMLElement).textContent = name;
          const activeItem = document.querySelector<HTMLButtonElement>(
            `.pmf-group-item[data-group-id="${selectedGroupId}"]`
          );
          if (activeItem) {
            activeItem.textContent = name;
          }
        }
        notifyResult(response);
      } catch {
        pushErrorNotification(getGenericErrorMessage());
      }
    }
  );

  (document.getElementById('saveMembersList') as HTMLButtonElement).addEventListener(
    'click',
    async (): Promise<void> => {
      if (selectedGroupId === '') {
        return;
      }
      const memberIds = [...document.querySelectorAll<HTMLElement>('#pmf-member-list li')].map((row: HTMLElement) =>
        parseInt(row.dataset.userId || '0')
      );
      try {
        const response = await updateGroupMembers(selectedGroupId, memberIds, detail.dataset.csrfMembers || '');
        notifyResult(response);
      } catch {
        pushErrorNotification(getGenericErrorMessage());
      }
    }
  );

  (document.getElementById('saveGroupRights') as HTMLButtonElement).addEventListener(
    'click',
    async (): Promise<void> => {
      if (selectedGroupId === '') {
        return;
      }
      const rightIds = [
        ...document.querySelectorAll<HTMLInputElement>('#pmf-permission-list input.permission:checked'),
      ].map((checkbox: HTMLInputElement) => parseInt(checkbox.value));
      try {
        const response = await updateGroupPermissions(selectedGroupId, rightIds, detail.dataset.csrfPermissions || '');
        notifyResult(response);
      } catch {
        pushErrorNotification(getGenericErrorMessage());
      }
    }
  );

  (document.getElementById('saveCategoryRestrictions') as HTMLButtonElement).addEventListener(
    'click',
    async (event: Event): Promise<void> => {
      event.preventDefault();
      try {
        await handleCategoryRestrictionsSave();
      } catch {
        pushErrorNotification(getGenericErrorMessage());
      }
    }
  );
};

const wireDeleteModal = (): void => {
  const modalElement = document.getElementById('pmf-group-delete-modal') as HTMLElement;
  const detail = document.getElementById('pmf-group-detail') as HTMLElement;

  (document.getElementById('pmf-delete-group-button') as HTMLButtonElement).addEventListener('click', (): void => {
    (document.getElementById('pmf-group-delete-name') as HTMLElement).textContent = (
      document.getElementById('pmf-selected-group-name') as HTMLElement
    ).textContent;
    Modal.getOrCreateInstance(modalElement).show();
  });

  (document.getElementById('pmf-confirm-group-delete') as HTMLButtonElement).addEventListener(
    'click',
    async (): Promise<void> => {
      if (selectedGroupId === '') {
        return;
      }

      try {
        const response = await deleteGroup(selectedGroupId, detail.dataset.csrfDelete || '');
        Modal.getInstance(modalElement)?.hide();

        if (response.success) {
          pushNotification(response.success);
          selectedGroupId = '';
          detail.classList.add('d-none');
          (document.getElementById('pmf-group-empty-state') as HTMLElement).classList.remove('d-none');
          await refreshGroupList();
        } else {
          pushErrorNotification(response.error ?? getGenericErrorMessage());
        }
      } catch {
        pushErrorNotification(getGenericErrorMessage());
      }
    }
  );
};

let cachedCategories: CategoryItem[] = [];
let currentRestrictions: CategoryRestrictions = {};

const loadCategoryRestrictions = async (groupId: string): Promise<void> => {
  const container = document.getElementById('categoryRestrictionsBody');
  if (!container) {
    return;
  }

  if (cachedCategories.length === 0) {
    cachedCategories = await fetchCategoriesForRestrictions();
  }

  currentRestrictions = await fetchGroupCategoryRestrictions(groupId);

  renderCategoryRestrictions(container);
};

const captureCurrentRestrictions = (container: HTMLElement): void => {
  const selects = container.querySelectorAll<HTMLSelectElement>('select[data-right-id]');
  selects.forEach((select: HTMLSelectElement): void => {
    const rightId = select.dataset.rightId;
    if (!rightId) {
      return;
    }
    currentRestrictions[rightId] = [...select.options]
      .filter((option: HTMLOptionElement): boolean => option.selected)
      .map((option: HTMLOptionElement): number => parseInt(option.value));
  });
};

const renderCategoryRestrictions = (container: HTMLElement): void => {
  const checkedRights = document.querySelectorAll<HTMLInputElement>(
    '#pmf-permission-list input[type=checkbox]:checked'
  );

  container.innerHTML = '';

  if (checkedRights.length === 0) {
    const emptyMsg = container.dataset.msgEmpty || 'No permissions assigned to this group.';
    const emptyParagraph = document.createElement('p');
    emptyParagraph.className = 'text-body-secondary';
    emptyParagraph.textContent = emptyMsg;
    container.appendChild(emptyParagraph);
    return;
  }

  checkedRights.forEach((checkbox: HTMLInputElement): void => {
    const rightId = checkbox.value;
    const label = checkbox.closest('.form-check')?.querySelector('label')?.textContent?.trim() || `Right ${rightId}`;
    const restrictedCategoryIds = currentRestrictions[rightId] || [];

    const wrapper = document.createElement('div');
    wrapper.className = 'mb-3';

    const labelElement = document.createElement('label');
    labelElement.className = 'form-label fw-semibold';
    labelElement.textContent = label;
    wrapper.appendChild(labelElement);

    const select = document.createElement('select');
    select.className = 'form-select form-select-sm';
    select.multiple = true;
    select.size = 4;
    select.dataset.rightId = rightId;

    cachedCategories.forEach((cat: CategoryItem): void => {
      const option = document.createElement('option');
      option.value = String(cat.id);
      option.textContent = cat.name;
      option.selected = restrictedCategoryIds.includes(cat.id);
      select.appendChild(option);
    });

    wrapper.appendChild(select);

    const helpText = document.createElement('div');
    helpText.className = 'form-text';
    helpText.textContent =
      container.dataset.msgHelp ||
      'Select categories to restrict this permission. Leave empty for unrestricted access.';
    wrapper.appendChild(helpText);

    container.appendChild(wrapper);
  });
};

export const handleCategoryRestrictionsSave = async (): Promise<void> => {
  if (selectedGroupId === '') {
    return;
  }

  const container = document.getElementById('categoryRestrictionsBody');
  if (!container) {
    return;
  }

  const csrfToken = container.dataset.csrfToken || '';

  // Collect every right ID from the permission checkboxes so unticked
  // permissions also get their stored restrictions cleared.
  const rightIds = new Set<string>();
  document
    .querySelectorAll<HTMLInputElement>('#pmf-permission-list input[type=checkbox].permission')
    .forEach((checkbox: HTMLInputElement): void => {
      if (checkbox.value) {
        rightIds.add(checkbox.value);
      }
    });

  let failed = false;
  for (const rightId of rightIds) {
    const select = container.querySelector<HTMLSelectElement>(`select[data-right-id="${rightId}"]`);
    const selectedCategoryIds = select
      ? [...select.options]
          .filter((option: HTMLOptionElement): boolean => option.selected)
          .map((option: HTMLOptionElement): number => parseInt(option.value))
      : [];

    const response = await saveGroupCategoryRestrictions(selectedGroupId, rightId, selectedCategoryIds, csrfToken);
    if (!response.ok) {
      failed = true;
    }
  }

  if (failed) {
    pushErrorNotification(container.dataset.msgSaveFailed || 'Failed to save category restrictions.');
  } else {
    pushNotification(container.dataset.msgSaved || 'Category restrictions saved.');
  }
};
