/**
 * Functions for handling group management
 *
 * @todo move fetch() functionality to api functions
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

import {
  fetchAllGroups,
  fetchAllMembers,
  fetchAllUsersForGroups,
  fetchCategoriesForRestrictions,
  fetchGroup,
  fetchGroupCategoryRestrictions,
  fetchGroupRights,
  saveGroupCategoryRestrictions,
} from '../api';
import { selectAll, unSelectAll } from '../utils';
import { CategoryItem, CategoryRestrictions, Group, Member, User } from '../interfaces';

export const handleGroups = async (): Promise<void> => {
  clearGroupList();

  const groupListSelect = document.querySelector<HTMLSelectElement>('#group_list_select') as HTMLSelectElement;
  if (!groupListSelect) {
    return;
  }

  const addMember = document.querySelector<HTMLButtonElement>('.pmf-add-member') as HTMLButtonElement;
  const removeMember = document.querySelector<HTMLButtonElement>('.pmf-remove-member') as HTMLButtonElement;
  const selectAllUsers = document.getElementById('select_all_group_user_list') as HTMLButtonElement;
  const unSelectAllUsers = document.getElementById('unselect_all_group_user_list') as HTMLButtonElement;
  const selectAllMembers = document.getElementById('select_all_members') as HTMLButtonElement;
  const unSelectAllMembers = document.getElementById('unselect_all_members') as HTMLButtonElement;
  const groups: Group[] = await fetchAllGroups();

  groups.forEach((group: Group): void => {
    const option: HTMLOptionElement = document.createElement('option');
    option.value = group.group_id;
    option.textContent = group.name;
    groupListSelect.appendChild(option);
  });

  await processGroupList();

  // Events
  groupListSelect.addEventListener('change', (event: Event): void => {
    handleGroupSelect(event);
  });

  selectAllUsers.addEventListener('click', (): void => {
    selectAll('group_user_list');
  });

  unSelectAllUsers.addEventListener('click', (): void => {
    unSelectAll('group_user_list');
  });

  addMember.addEventListener('click', (): void => {
    addGroupMembers();
    selectAll('group_member_list');
  });

  removeMember.addEventListener('click', (): void => {
    removeGroupMembers();
    selectAll('group_member_list');
  });

  selectAllMembers.addEventListener('click', (): void => {
    selectAll('group_member_list');
  });

  unSelectAllMembers.addEventListener('click', (): void => {
    unSelectAll('group_member_list');
  });

  // Category restrictions save button
  const saveCategoryRestrictions = document.getElementById('saveCategoryRestrictions') as HTMLButtonElement;
  if (saveCategoryRestrictions) {
    saveCategoryRestrictions.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      await handleCategoryRestrictionsSave();
    });
  }

  // Update category restrictions panel when rights checkboxes change
  document.getElementById('groupRights')?.addEventListener('change', (event: Event): void => {
    const target = event.target as HTMLInputElement;
    if (target.type === 'checkbox' && target.classList.contains('permission')) {
      const container = document.getElementById('categoryRestrictionsBody');
      if (container) {
        renderCategoryRestrictions(container);
      }
    }
  });
};

const handleGroupSelect = async (event: Event): Promise<void> => {
  const groupId: string = (event.target as HTMLSelectElement)?.value;

  if (groupId) {
    clearGroupData();
    await getGroupData(groupId);
    clearGroupRights();
    await getGroupRights(groupId);
    clearUserList();
    await getUserList();
    clearMemberList();
    await getMemberList(groupId);
    await loadCategoryRestrictions(groupId);

    // Activate user inputs
    const saveGroupDetails = document.getElementById('saveGroupDetails') as HTMLButtonElement;
    const saveMembersList = document.getElementById('saveMembersList') as HTMLButtonElement;
    const saveGroupRights = document.getElementById('saveGroupRights') as HTMLButtonElement;
    const deleteGroup = document.getElementById('deleteGroup') as HTMLButtonElement;
    const groupAddMember = document.getElementById('groupAddMember') as HTMLButtonElement;
    const groupRemoveMember = document.getElementById('groupRemoveMember') as HTMLButtonElement;
    const saveCategoryRestrictions = document.getElementById('saveCategoryRestrictions') as HTMLButtonElement;

    saveGroupDetails.disabled = false;
    saveMembersList.disabled = false;
    saveGroupRights.disabled = false;
    deleteGroup.disabled = false;
    groupAddMember.disabled = false;
    groupRemoveMember.disabled = false;
    if (saveCategoryRestrictions) {
      saveCategoryRestrictions.disabled = false;
    }

    document.querySelectorAll<HTMLInputElement>('.permission').forEach((item: HTMLInputElement): void => {
      item.disabled = false;
    });
  }
};

const getGroupData = async (groupId: string): Promise<void> => {
  const groupData: Group = await fetchGroup(groupId);

  (document.getElementById('update_group_id') as HTMLInputElement).value = groupData.group_id;
  (document.getElementById('update_group_name') as HTMLInputElement).value = groupData.name;
  (document.getElementById('update_group_description') as HTMLInputElement).value = groupData.description || '';

  const autoJoinCheckbox = document.getElementById('update_group_auto_join') as HTMLInputElement;
  autoJoinCheckbox.checked = 1 === parseInt(groupData.auto_join || '0');
};

const clearGroupList = (): void => {
  const groupList = document.getElementById('group_list_select') as HTMLSelectElement;
  if (groupList) {
    groupList.textContent = '';
  }
};

const processGroupList = async (): Promise<void> => {
  clearGroupData();
  clearGroupRights();
  clearUserList();
  await getUserList();
  clearMemberList();
};

const clearGroupData = (): void => {
  const updateGroupAutoJoin = document.getElementById('update_group_auto_join') as HTMLInputElement;
  const updateGroupId = document.getElementById('update_group_id') as HTMLInputElement;
  if (updateGroupId) {
    updateGroupId.value = '';
  }
  const updateGroupName = document.getElementById('update_group_name') as HTMLInputElement;
  if (updateGroupName) {
    updateGroupName.value = '';
  }
  const updateGroupDescription = document.getElementById('update_group_description') as HTMLInputElement;
  if (updateGroupDescription) {
    updateGroupDescription.value = '';
  }
  if (updateGroupAutoJoin.checked) {
    updateGroupAutoJoin.checked = false;
  }
};

const clearGroupRights = (): void => {
  const groupRightsCheckboxes: NodeListOf<HTMLInputElement> = document.querySelectorAll<HTMLInputElement>(
    '#groupRights input[type=checkbox]'
  );
  if (groupRightsCheckboxes) {
    groupRightsCheckboxes.forEach((checkbox: HTMLInputElement): void => {
      checkbox.checked = false;
    });
  }
};

const getGroupRights = async (groupId: string): Promise<void> => {
  const groupRights: string[] = await fetchGroupRights(groupId);

  if (groupRights) {
    (document.getElementById('rights_group_id') as HTMLInputElement).value = groupId;
    groupRights.forEach((right) => {
      (document.getElementById(`group_right_${right}`) as HTMLInputElement).checked = true;
    });
  }
};

const clearUserList = (): void => {
  const groupUserListOptions: NodeListOf<HTMLSelectElement> =
    document.querySelectorAll<HTMLSelectElement>('#group_user_list option');
  if (groupUserListOptions) {
    groupUserListOptions.forEach((option: HTMLSelectElement): void => {
      option.value = '';
    });
  }
};

const getUserList = async (): Promise<void> => {
  const groupUserList = document.querySelector<HTMLSelectElement>('#group_user_list') as HTMLSelectElement;
  const allUsers: User[] = await fetchAllUsersForGroups();

  groupUserList.textContent = '';
  allUsers.forEach((user: User): void => {
    const option: HTMLOptionElement = document.createElement('option');
    option.value = user.user_id;
    option.textContent = user.login;
    groupUserList.appendChild(option);
  });
};

const clearMemberList = (): void => {
  const groupMemberList = document.querySelector<HTMLSelectElement>('#group_member_list') as HTMLSelectElement;
  groupMemberList.textContent = '';
};

const getMemberList = async (groupId: string): Promise<void> => {
  const groupMemberList = document.querySelector<HTMLSelectElement>('#group_member_list') as HTMLSelectElement;
  const members: Member[] = await fetchAllMembers(groupId);

  groupMemberList.textContent = '';
  members.forEach((member: Member) => {
    const option: HTMLOptionElement = document.createElement('option');
    option.value = member.user_id;
    option.textContent = member.login;
    option.selected = true;
    groupMemberList.appendChild(option);
  });
  (document.getElementById('update_member_group_id') as HTMLInputElement).value = groupId;
};

const addGroupMembers = (): void => {
  const selectedGroup = document.querySelector<HTMLSelectElement>(
    '#group_list_select option:checked'
  ) as HTMLSelectElement;
  if (selectedGroup === null) {
    // @todo refactor alert() to something more beautiful.
    alert('Please choose a group.');
    return;
  }

  const allUsers = document.getElementById('group_user_list') as HTMLSelectElement;
  const selectedUsers = [...allUsers.options]
    .filter((option: HTMLOptionElement): boolean => option.selected)
    .map((option: HTMLOptionElement) => {
      return { value: option.value, login: option.innerText };
    });
  const allMembers = document.getElementById('group_member_list') as HTMLSelectElement;
  const members = [...allMembers.options].map((option: HTMLOptionElement) => {
    return { value: option.value, login: option.innerText };
  });

  if (selectedUsers) {
    selectedUsers.forEach((user) => {
      let isMember: boolean = false;

      members.forEach((member) => {
        isMember = user.value === member.value;
      });

      if (isMember === false) {
        const groupMemberList = document.getElementById('group_member_list') as HTMLSelectElement;
        const option: HTMLOptionElement = document.createElement('option');
        option.value = user.value;
        option.textContent = user.login;
        option.selected = true;
        groupMemberList.appendChild(option);
      }
    });
  }
};

const removeGroupMembers = (): void => {
  const memberList = document.getElementById('group_member_list') as HTMLSelectElement;
  const selectedMembers: string[] = [...memberList.options]
    .filter((option: HTMLOptionElement): boolean => option.selected)
    .map((option: HTMLOptionElement): string => option.value);

  if (selectedMembers.length === 0) {
    // @todo refactor alert() to something more beautiful.
    alert('Please choose a member.');
    return;
  }

  for (const member of [...document.querySelectorAll<HTMLSelectElement>('#group_member_list option')]) {
    if (selectedMembers.includes(member.value)) {
      member.remove();
    }
  }
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

const renderCategoryRestrictions = (container: HTMLElement): void => {
  const checkedRights = document.querySelectorAll<HTMLInputElement>('#groupRights input[type=checkbox]:checked');

  container.innerHTML = '';

  if (checkedRights.length === 0) {
    container.innerHTML = '<p class="text-muted">No permissions assigned to this group.</p>';
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
    helpText.textContent = 'Select categories to restrict this permission. Leave empty for unrestricted access.';
    wrapper.appendChild(helpText);

    container.appendChild(wrapper);
  });
};

export const handleCategoryRestrictionsSave = async (): Promise<void> => {
  const groupListSelect = document.getElementById('group_list_select') as HTMLSelectElement;
  if (!groupListSelect) {
    return;
  }

  const groupId = groupListSelect.value;
  if (!groupId) {
    return;
  }

  const container = document.getElementById('categoryRestrictionsBody');
  if (!container) {
    return;
  }

  const selects = container.querySelectorAll<HTMLSelectElement>('select[data-right-id]');

  for (const select of selects) {
    const rightId = select.dataset.rightId;
    if (!rightId) {
      continue;
    }

    const selectedCategoryIds = [...select.options]
      .filter((option: HTMLOptionElement): boolean => option.selected)
      .map((option: HTMLOptionElement): number => parseInt(option.value));

    await saveGroupCategoryRestrictions(groupId, rightId, selectedCategoryIds);
  }
};
