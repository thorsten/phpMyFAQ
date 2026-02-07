import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleGroups } from './groups';
import { fetchAllGroups, fetchAllMembers, fetchAllUsersForGroups, fetchGroup, fetchGroupRights } from '../api';
import { selectAll, unSelectAll } from '../utils';

vi.mock('../api');
vi.mock('../utils');

const flushPromises = async (): Promise<void> => {
  await new Promise((resolve) => setTimeout(resolve, 10));
};

const setupFullDom = (): void => {
  document.body.innerHTML = `
    <select id="group_list_select"></select>
    <select id="group_user_list" multiple></select>
    <select id="group_member_list" multiple></select>
    <input id="update_group_id" value="" />
    <input id="update_group_name" value="" />
    <input id="update_group_description" value="" />
    <input id="update_group_auto_join" type="checkbox" />
    <input id="rights_group_id" value="" />
    <input id="update_member_group_id" value="" />
    <div id="groupRights">
      <input type="checkbox" id="group_right_1" />
      <input type="checkbox" id="group_right_3" />
      <input type="checkbox" id="group_right_5" />
    </div>
    <button class="pmf-add-member">Add</button>
    <button class="pmf-remove-member">Remove</button>
    <button id="select_all_group_user_list">Select All Users</button>
    <button id="unselect_all_group_user_list">Unselect All Users</button>
    <button id="select_all_members">Select All Members</button>
    <button id="unselect_all_members">Unselect All Members</button>
    <button id="saveGroupDetails" disabled>Save Details</button>
    <button id="saveMembersList" disabled>Save Members</button>
    <button id="saveGroupRights" disabled>Save Rights</button>
    <button id="deleteGroup" disabled>Delete</button>
    <button id="groupAddMember" disabled>Add Member</button>
    <button id="groupRemoveMember" disabled>Remove Member</button>
    <input class="permission" type="checkbox" disabled />
    <input class="permission" type="checkbox" disabled />
  `;
};

const mockDefaultApis = (): void => {
  (fetchAllGroups as Mock).mockResolvedValue([
    { group_id: '1', name: 'Admins' },
    { group_id: '2', name: 'Users' },
  ]);
  (fetchAllUsersForGroups as Mock).mockResolvedValue([
    { user_id: '10', login: 'alice' },
    { user_id: '20', login: 'bob' },
  ]);
  (fetchAllMembers as Mock).mockResolvedValue([]);
  (fetchGroup as Mock).mockResolvedValue({
    group_id: '1',
    name: 'Admins',
    description: 'Admin group',
    auto_join: '1',
  });
  (fetchGroupRights as Mock).mockResolvedValue(['1', '3']);
};

describe('handleGroups', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    vi.spyOn(window, 'alert').mockImplementation(() => {});
  });

  it('should return early when #group_list_select is missing', async () => {
    document.body.innerHTML = '<div></div>';

    await handleGroups();

    expect(fetchAllGroups).not.toHaveBeenCalled();
  });

  it('should populate group select with fetched groups', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();

    const select = document.getElementById('group_list_select') as HTMLSelectElement;
    expect(select.options.length).toBe(2);
    expect(select.options[0].value).toBe('1');
    expect(select.options[0].textContent).toBe('Admins');
    expect(select.options[1].value).toBe('2');
    expect(select.options[1].textContent).toBe('Users');
  });

  it('should populate user list on initial load', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();

    const userList = document.getElementById('group_user_list') as HTMLSelectElement;
    expect(userList.options.length).toBe(2);
    expect(userList.options[0].value).toBe('10');
    expect(userList.options[0].textContent).toBe('alice');
    expect(userList.options[1].value).toBe('20');
    expect(userList.options[1].textContent).toBe('bob');
  });

  it('should call selectAll when select all users button is clicked', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();

    const btn = document.getElementById('select_all_group_user_list') as HTMLButtonElement;
    btn.click();

    expect(selectAll).toHaveBeenCalledWith('group_user_list');
  });

  it('should call unSelectAll when unselect all users button is clicked', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();

    const btn = document.getElementById('unselect_all_group_user_list') as HTMLButtonElement;
    btn.click();

    expect(unSelectAll).toHaveBeenCalledWith('group_user_list');
  });

  it('should call selectAll when select all members button is clicked', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();

    const btn = document.getElementById('select_all_members') as HTMLButtonElement;
    btn.click();

    expect(selectAll).toHaveBeenCalledWith('group_member_list');
  });

  it('should call unSelectAll when unselect all members button is clicked', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();

    const btn = document.getElementById('unselect_all_members') as HTMLButtonElement;
    btn.click();

    expect(unSelectAll).toHaveBeenCalledWith('group_member_list');
  });

  describe('handleGroupSelect (on change)', () => {
    it('should fetch group data and enable buttons when a group is selected', async () => {
      setupFullDom();
      mockDefaultApis();
      (fetchAllMembers as Mock).mockResolvedValue([{ user_id: '10', login: 'alice' }]);

      await handleGroups();

      const select = document.getElementById('group_list_select') as HTMLSelectElement;
      select.value = '1';
      select.dispatchEvent(new Event('change'));

      await flushPromises();

      // Group data should be filled
      expect(fetchGroup).toHaveBeenCalledWith('1');
      expect((document.getElementById('update_group_id') as HTMLInputElement).value).toBe('1');
      expect((document.getElementById('update_group_name') as HTMLInputElement).value).toBe('Admins');
      expect((document.getElementById('update_group_description') as HTMLInputElement).value).toBe('Admin group');
      expect((document.getElementById('update_group_auto_join') as HTMLInputElement).checked).toBe(true);

      // Group rights should be checked
      expect(fetchGroupRights).toHaveBeenCalledWith('1');
      expect((document.getElementById('group_right_1') as HTMLInputElement).checked).toBe(true);
      expect((document.getElementById('group_right_3') as HTMLInputElement).checked).toBe(true);
      expect((document.getElementById('group_right_5') as HTMLInputElement).checked).toBe(false);

      // Members should be populated
      expect(fetchAllMembers).toHaveBeenCalledWith('1');
      const memberList = document.getElementById('group_member_list') as HTMLSelectElement;
      expect(memberList.options.length).toBe(1);
      expect(memberList.options[0].value).toBe('10');

      // Buttons should be enabled
      expect((document.getElementById('saveGroupDetails') as HTMLButtonElement).disabled).toBe(false);
      expect((document.getElementById('saveMembersList') as HTMLButtonElement).disabled).toBe(false);
      expect((document.getElementById('saveGroupRights') as HTMLButtonElement).disabled).toBe(false);
      expect((document.getElementById('deleteGroup') as HTMLButtonElement).disabled).toBe(false);

      // Permission checkboxes should be enabled
      document.querySelectorAll<HTMLInputElement>('.permission').forEach((perm) => {
        expect(perm.disabled).toBe(false);
      });
    });

    it('should set auto_join to false when auto_join is "0"', async () => {
      setupFullDom();
      mockDefaultApis();
      (fetchGroup as Mock).mockResolvedValue({
        group_id: '2',
        name: 'Users',
        description: '',
        auto_join: '0',
      });

      await handleGroups();

      const select = document.getElementById('group_list_select') as HTMLSelectElement;
      select.value = '2';
      select.dispatchEvent(new Event('change'));

      await flushPromises();

      expect((document.getElementById('update_group_auto_join') as HTMLInputElement).checked).toBe(false);
    });
  });

  describe('addGroupMembers', () => {
    it('should add selected users to member list', async () => {
      setupFullDom();
      mockDefaultApis();

      await handleGroups();

      // Select a group first (so option:checked works)
      const groupSelect = document.getElementById('group_list_select') as HTMLSelectElement;
      groupSelect.value = '1';
      groupSelect.dispatchEvent(new Event('change'));
      await flushPromises();

      // Select a user in the user list
      const userList = document.getElementById('group_user_list') as HTMLSelectElement;
      // After group select, user list is repopulated
      const userOption = userList.options[0];
      if (userOption) {
        userOption.selected = true;
      }

      // Clear member list to start fresh
      const memberList = document.getElementById('group_member_list') as HTMLSelectElement;
      memberList.textContent = '';

      // Click add member
      const addBtn = document.querySelector('.pmf-add-member') as HTMLButtonElement;
      addBtn.click();

      // Member should be added
      expect(memberList.options.length).toBe(1);
      expect(memberList.options[0].selected).toBe(true);
    });

    it('should show alert when no group is selected', async () => {
      setupFullDom();
      mockDefaultApis();

      await handleGroups();

      // Ensure no option is checked in group_list_select
      const groupSelect = document.getElementById('group_list_select') as HTMLSelectElement;
      groupSelect.innerHTML = '';

      const addBtn = document.querySelector('.pmf-add-member') as HTMLButtonElement;
      addBtn.click();

      expect(window.alert).toHaveBeenCalledWith('Please choose a group.');
    });

    it('should not add duplicate members', async () => {
      setupFullDom();
      mockDefaultApis();
      (fetchAllMembers as Mock).mockResolvedValue([{ user_id: '10', login: 'alice' }]);

      await handleGroups();

      // Select a group
      const groupSelect = document.getElementById('group_list_select') as HTMLSelectElement;
      groupSelect.value = '1';
      groupSelect.dispatchEvent(new Event('change'));
      await flushPromises();

      // alice (user_id 10) is already a member
      const userList = document.getElementById('group_user_list') as HTMLSelectElement;
      // Select alice in user list
      for (const opt of userList.options) {
        opt.selected = opt.value === '10';
      }

      const memberCountBefore = (document.getElementById('group_member_list') as HTMLSelectElement).options.length;

      const addBtn = document.querySelector('.pmf-add-member') as HTMLButtonElement;
      addBtn.click();

      const memberCountAfter = (document.getElementById('group_member_list') as HTMLSelectElement).options.length;
      expect(memberCountAfter).toBe(memberCountBefore);
    });
  });

  describe('removeGroupMembers', () => {
    it('should remove selected members from member list', async () => {
      setupFullDom();
      mockDefaultApis();
      (fetchAllMembers as Mock).mockResolvedValue([
        { user_id: '10', login: 'alice' },
        { user_id: '20', login: 'bob' },
      ]);

      await handleGroups();

      // Select a group to populate members
      const groupSelect = document.getElementById('group_list_select') as HTMLSelectElement;
      groupSelect.value = '1';
      groupSelect.dispatchEvent(new Event('change'));
      await flushPromises();

      const memberList = document.getElementById('group_member_list') as HTMLSelectElement;
      expect(memberList.options.length).toBe(2);

      // Select only alice for removal
      for (const opt of memberList.options) {
        opt.selected = opt.value === '10';
      }

      const removeBtn = document.querySelector('.pmf-remove-member') as HTMLButtonElement;
      removeBtn.click();

      expect(memberList.options.length).toBe(1);
      expect(memberList.options[0].value).toBe('20');
    });

    it('should show alert when no member is selected', async () => {
      setupFullDom();
      mockDefaultApis();
      (fetchAllMembers as Mock).mockResolvedValue([{ user_id: '10', login: 'alice' }]);

      await handleGroups();

      // Select a group to populate members
      const groupSelect = document.getElementById('group_list_select') as HTMLSelectElement;
      groupSelect.value = '1';
      groupSelect.dispatchEvent(new Event('change'));
      await flushPromises();

      // Deselect all members
      const memberList = document.getElementById('group_member_list') as HTMLSelectElement;
      for (const opt of memberList.options) {
        opt.selected = false;
      }

      const removeBtn = document.querySelector('.pmf-remove-member') as HTMLButtonElement;
      removeBtn.click();

      expect(window.alert).toHaveBeenCalledWith('Please choose a member.');
    });
  });
});
