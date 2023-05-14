import { fetchAllGroups, fetchAllMembers, fetchAllUsersForGroups, fetchGroup, fetchGroupRights } from '../api';
import { selectAllCheckboxes, unSelectAllCheckboxes } from '../utils';

export const handleGroups = async () => {
  clearGroupList();

  const groupListSelect = document.querySelector('#group_list_select');
  if (!groupListSelect) {
    return;
  }

  const addMember = document.querySelector('.pmf-add-member');
  const removeMember = document.querySelector('.pmf-remove-member');
  const selectAllUsers = document.getElementById('select_all_group_user_list');
  const unSelectAllUsers = document.getElementById('unselect_all_group_user_list');
  const selectAllMembers = document.getElementById('select_all_members');
  const unSelectAllMembers = document.getElementById('unselect_all_members');
  const groups = await fetchAllGroups();

  groups.forEach((group) => {
    const option = document.createElement('option');
    option.value = group.group_id;
    option.textContent = group.name;
    groupListSelect.appendChild(option);
  });

  await processGroupList();

  // Events
  groupListSelect.addEventListener('change', (event) => {
    handleGroupSelect(event);
  });

  selectAllUsers.addEventListener('click', () => {
    selectAllCheckboxes('group_user_list');
  });

  unSelectAllUsers.addEventListener('click', () => {
    unSelectAllCheckboxes('group_user_list');
  });

  addMember.addEventListener('click', () => {
    addGroupMembers();
  });

  removeMember.addEventListener('click', () => {
    removeGroupMembers();
  });

  selectAllMembers.addEventListener('click', () => {
    selectAllCheckboxes('group_member_list');
  });

  unSelectAllMembers.addEventListener('click', () => {
    unSelectAllCheckboxes('group_member_list');
  });
};

const handleGroupSelect = async (event) => {
  const groupId = event.target?.value;

  if (groupId) {
    clearGroupData();
    await getGroupData(groupId);
    clearGroupRights();
    await getGroupRights(groupId);
    clearUserList();
    await getUserList();
    clearMemberList();
    await getMemberList(groupId);
  }
};

const getGroupData = async (groupId) => {
  const groupData = await fetchGroup(groupId);

  document.getElementById('update_group_id').value = groupData.group_id;
  document.getElementById('update_group_name').value = groupData.name;
  document.getElementById('update_group_description').value = groupData.description;

  const autoJoinCheckbox = document.getElementById('update_group_auto_join');
  autoJoinCheckbox.checked = 1 === parseInt(groupData.auto_join);
};

const clearGroupList = () => {
  const groupList = document.getElementById('group_list_select');
  if (groupList) {
    groupList.textContent = '';
  }
};

const processGroupList = async () => {
  clearGroupData();
  clearGroupRights();
  clearUserList();
  await getUserList();
  clearMemberList();
};

const clearGroupData = () => {
  const updateGroupAutoJoin = document.getElementById('update_group_auto_join');
  const updateGroupId = document.getElementById('#update_group_id');
  if (updateGroupId) {
    updateGroupId.value = '';
  }
  const updateGroupName = document.getElementById('#update_group_name');
  if (updateGroupName) {
    updateGroupName.value = '';
  }
  const updateGroupDescription = document.getElementById('#update_group_description');
  if (updateGroupDescription) {
    updateGroupDescription.value = '';
  }
  if ('checked' === updateGroupAutoJoin.getAttribute('checked')) {
    updateGroupAutoJoin.setAttribute('checked', '');
  }
};

const clearGroupRights = () => {
  const groupRightsCheckboxes = document.querySelectorAll('#groupRights input[type=checkbox]');
  if (groupRightsCheckboxes) {
    groupRightsCheckboxes.forEach((checkbox) => {
      checkbox.checked = false;
    });
  }
};

const getGroupRights = async (groupId) => {
  const groupRights = await fetchGroupRights(groupId);

  if (groupRights) {
    document.getElementById('rights_group_id').value = groupId;
    groupRights.forEach((right) => {
      document.getElementById(`group_right_${right}`).checked = true;
    });
  }
};

const clearUserList = () => {
  const groupUserListOptions = document.querySelectorAll('#group_user_list option');
  if (groupUserListOptions) {
    groupUserListOptions.forEach((option) => {
      option.value = '';
    });
  }
};

const getUserList = async () => {
  const groupUserList = document.querySelector('#group_user_list');
  const allUsers = await fetchAllUsersForGroups();

  groupUserList.textContent = '';
  allUsers.forEach((user) => {
    const option = document.createElement('option');
    option.value = user.user_id;
    option.textContent = user.login;
    groupUserList.appendChild(option);
  });
};

const clearMemberList = () => {
  const groupMemberList = document.querySelector('#group_member_list');
  groupMemberList.textContent = '';
};

const getMemberList = async (groupId) => {
  const groupMemberList = document.querySelector('#group_member_list');
  const members = await fetchAllMembers(groupId);

  groupMemberList.textContent = '';
  members.forEach((member) => {
    const option = document.createElement('option');
    option.value = member.user_id;
    option.textContent = member.login;
    option.selected = true;
    groupMemberList.appendChild(option);
  });
  document.getElementById('update_member_group_id').value = groupId;
};

const addGroupMembers = () => {
  // make sure that a group is selected
  const selectedGroup = document.querySelector('#group_list_select option:checked');
  if (selectedGroup === null) {
    // @todo refactor alert() to something more beautiful.
    alert('Please choose a group.');
  }

  const allUsers = document.getElementById('group_user_list');
  const selectedUsers = [...allUsers.options]
    .filter((option) => option.selected)
    .map((option) => {
      return { value: option.value, login: option.innerText };
    });
  const allMembers = document.getElementById('group_member_list');
  const members = [...allMembers.options].map((option) => {
    return { value: option.value, login: option.innerText };
  });

  if (selectedUsers) {
    selectedUsers.forEach((user) => {
      let isMember = false;

      members.forEach((member) => {
        isMember = user.value === member.value;
      });

      if (isMember === false) {
        const groupMemberList = document.getElementById('group_member_list');
        const option = document.createElement('option');
        option.value = user.value;
        option.textContent = user.login;
        option.selected = true;
        groupMemberList.appendChild(option);
      }
    });
  }
};

const removeGroupMembers = () => {
  console.log('removeGroupMembers');

  const memberList = document.getElementById('group_member_list');
  const allMembers = [...memberList.options].map((option) => option.value);
  const selectedMembers = [...memberList.options].filter((option) => option.selected).map((option) => option.value);

  if (selectedMembers.length === 0) {
    // @todo refactor alert() to something more beautiful.
    alert('Please choose a member.');
  }

  for (const member of [...document.querySelector('#group_member_list').options]) {
    if (selectedMembers.includes(member.value)) {
      member.remove();
    }
  }
};
