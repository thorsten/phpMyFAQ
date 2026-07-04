import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleGroups } from './groups';
import {
  deleteGroup,
  fetchAllGroups,
  fetchAllMembers,
  fetchAllUsersForGroups,
  fetchCategoriesForRestrictions,
  fetchGroup,
  fetchGroupCategoryRestrictions,
  fetchGroupRights,
  updateGroup,
  updateGroupMembers,
  updateGroupPermissions,
} from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

vi.mock('../api');
vi.mock('../../../../assets/src/utils', () => ({
  pushNotification: vi.fn(),
  pushErrorNotification: vi.fn(),
}));

const { modalShow, modalHide } = vi.hoisted(() => ({ modalShow: vi.fn(), modalHide: vi.fn() }));
vi.mock('bootstrap', () => ({
  Modal: class {
    show = modalShow;
    hide = modalHide;
    static getInstance = (): { hide: () => void } => ({ hide: modalHide });
    static getOrCreateInstance = (): { show: () => void } => ({ show: modalShow });
  },
}));

const flushPromises = async (): Promise<void> => {
  await new Promise((resolve) => setTimeout(resolve, 10));
};

const setupFullDom = (): void => {
  document.body.innerHTML = `
    <input id="pmf-group-filter" type="search" />
    <div id="pmf-group-list"></div>
    <div id="pmf-group-empty-state"></div>
    <div id="pmf-group-detail" class="d-none"
         data-csrf-update="csrf-update" data-csrf-members="csrf-members"
         data-csrf-permissions="csrf-permissions" data-csrf-delete="csrf-delete"
         data-msg-error="An error occurred.">
      <span id="pmf-selected-group-name"></span>
      <button id="pmf-delete-group-button" type="button"></button>
      <span id="pmf-member-count">0</span>
      <input id="update_group_name" type="text" />
      <textarea id="update_group_description"></textarea>
      <input id="update_group_auto_join" type="checkbox" />
      <button id="saveGroupDetails" type="button"></button>
      <input id="pmf-user-search" type="search" />
      <div id="pmf-user-search-results" class="d-none"></div>
      <ul id="pmf-member-list" data-label-remove="Remove member"></ul>
      <button id="saveMembersList" type="button"></button>
      <input id="pmf-permission-filter" type="search" />
      <button id="pmf-group-check-all" type="button"></button>
      <button id="pmf-group-uncheck-all" type="button"></button>
      <div id="pmf-permission-list">
        <div class="form-check">
          <input id="group_right_1" type="checkbox" value="1" class="form-check-input permission" />
          <label for="group_right_1">Add FAQ</label>
        </div>
        <div class="form-check">
          <input id="group_right_3" type="checkbox" value="3" class="form-check-input permission" />
          <label for="group_right_3">Edit FAQ</label>
        </div>
        <div class="form-check">
          <input id="group_right_5" type="checkbox" value="5" class="form-check-input permission" />
          <label for="group_right_5">Delete FAQ</label>
        </div>
      </div>
      <button id="saveGroupRights" type="button"></button>
      <div id="categoryRestrictionsBody" data-msg-empty="No permissions." data-msg-help="Help."
           data-msg-saved="Restrictions saved." data-csrf-token="csrf-restrictions"></div>
      <button id="saveCategoryRestrictions" type="button"></button>
    </div>
    <div id="pmf-group-delete-modal">
      <strong id="pmf-group-delete-name"></strong>
      <button id="pmf-confirm-group-delete" type="button"></button>
    </div>
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
  (fetchCategoriesForRestrictions as Mock).mockResolvedValue([]);
  (fetchGroupCategoryRestrictions as Mock).mockResolvedValue({});
  (updateGroup as Mock).mockResolvedValue({ success: 'saved' });
  (updateGroupMembers as Mock).mockResolvedValue({ success: 'saved' });
  (updateGroupPermissions as Mock).mockResolvedValue({ success: 'saved' });
  (deleteGroup as Mock).mockResolvedValue({ success: 'deleted' });
};

const selectFirstGroup = async (): Promise<void> => {
  (document.querySelector('.pmf-group-item') as HTMLButtonElement).click();
  await flushPromises();
};

describe('handleGroups', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should return early when #pmf-group-list is missing', async () => {
    document.body.innerHTML = '<div></div>';

    await handleGroups();

    expect(fetchAllUsersForGroups).not.toHaveBeenCalled();
    expect(fetchAllGroups).not.toHaveBeenCalled();
  });

  it('should render fetched groups as list items', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();

    const items = document.querySelectorAll('.pmf-group-item');
    expect(items.length).toBe(2);
    expect(items[0].textContent).toBe('Admins');
    expect((items[0] as HTMLElement).dataset.groupId).toBe('1');
    expect(items[1].textContent).toBe('Users');
  });

  it('should filter the group list by name', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();

    const filter = document.getElementById('pmf-group-filter') as HTMLInputElement;
    filter.value = 'adm';
    filter.dispatchEvent(new Event('input'));

    const items = document.querySelectorAll<HTMLElement>('.pmf-group-item');
    expect(items[0].classList.contains('d-none')).toBe(false);
    expect(items[1].classList.contains('d-none')).toBe(true);
  });

  it('should load group details and show the detail card when a group is selected', async () => {
    setupFullDom();
    mockDefaultApis();
    (fetchAllMembers as Mock).mockResolvedValue([{ user_id: '10', login: 'alice' }]);

    await handleGroups();
    await selectFirstGroup();

    expect(fetchGroup).toHaveBeenCalledWith('1');
    expect((document.getElementById('update_group_name') as HTMLInputElement).value).toBe('Admins');
    expect((document.getElementById('update_group_description') as HTMLTextAreaElement).value).toBe('Admin group');
    expect((document.getElementById('update_group_auto_join') as HTMLInputElement).checked).toBe(true);
    expect(document.getElementById('pmf-selected-group-name')?.textContent).toBe('Admins');

    expect((document.getElementById('group_right_1') as HTMLInputElement).checked).toBe(true);
    expect((document.getElementById('group_right_3') as HTMLInputElement).checked).toBe(true);
    expect((document.getElementById('group_right_5') as HTMLInputElement).checked).toBe(false);

    expect(document.querySelectorAll('#pmf-member-list li').length).toBe(1);
    expect(document.getElementById('pmf-member-count')?.textContent).toBe('1');

    expect(document.getElementById('pmf-group-empty-state')?.classList.contains('d-none')).toBe(true);
    expect(document.getElementById('pmf-group-detail')?.classList.contains('d-none')).toBe(false);
    expect((document.querySelector('.pmf-group-item') as HTMLElement).classList.contains('active')).toBe(true);
  });

  it('should suggest only non-members in the user search and add the clicked user', async () => {
    setupFullDom();
    mockDefaultApis();
    (fetchAllMembers as Mock).mockResolvedValue([{ user_id: '10', login: 'alice' }]);

    await handleGroups();
    await selectFirstGroup();

    const search = document.getElementById('pmf-user-search') as HTMLInputElement;
    search.value = 'b';
    search.dispatchEvent(new Event('input'));

    const results = document.getElementById('pmf-user-search-results') as HTMLElement;
    expect(results.classList.contains('d-none')).toBe(false);
    const suggestions = results.querySelectorAll('button');
    expect(suggestions.length).toBe(1);
    expect(suggestions[0].textContent).toBe('bob');

    suggestions[0].click();

    expect(document.querySelectorAll('#pmf-member-list li').length).toBe(2);
    expect(document.getElementById('pmf-member-count')?.textContent).toBe('2');
    expect(results.classList.contains('d-none')).toBe(true);
    expect(search.value).toBe('');
  });

  it('should remove a member row via its remove button', async () => {
    setupFullDom();
    mockDefaultApis();
    (fetchAllMembers as Mock).mockResolvedValue([
      { user_id: '10', login: 'alice' },
      { user_id: '20', login: 'bob' },
    ]);

    await handleGroups();
    await selectFirstGroup();

    expect(document.querySelectorAll('#pmf-member-list li').length).toBe(2);
    (document.querySelector('#pmf-member-list li button') as HTMLButtonElement).click();

    expect(document.querySelectorAll('#pmf-member-list li').length).toBe(1);
    expect(document.getElementById('pmf-member-count')?.textContent).toBe('1');
  });

  it('should save group details and update the visible group name', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();
    await selectFirstGroup();

    (document.getElementById('update_group_name') as HTMLInputElement).value = 'Renamed';
    (document.getElementById('update_group_auto_join') as HTMLInputElement).checked = false;
    (document.getElementById('saveGroupDetails') as HTMLButtonElement).click();
    await flushPromises();

    expect(updateGroup).toHaveBeenCalledWith('1', 'Renamed', 'Admin group', false, 'csrf-update');
    expect(pushNotification).toHaveBeenCalledWith('saved');
    expect(document.getElementById('pmf-selected-group-name')?.textContent).toBe('Renamed');
    expect((document.querySelector('.pmf-group-item') as HTMLElement).textContent).toBe('Renamed');
  });

  it('should push an error notification when saving details fails', async () => {
    setupFullDom();
    mockDefaultApis();
    (updateGroup as Mock).mockResolvedValue({ error: 'nope' });

    await handleGroups();
    await selectFirstGroup();

    (document.getElementById('saveGroupDetails') as HTMLButtonElement).click();
    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith('nope');
  });

  it('should push an error notification when saving details rejects with a network failure', async () => {
    setupFullDom();
    mockDefaultApis();
    (updateGroup as Mock).mockRejectedValue(new Error('network down'));

    await handleGroups();
    await selectFirstGroup();

    (document.getElementById('saveGroupDetails') as HTMLButtonElement).click();
    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith('An error occurred.');
  });

  it('should save the member list as member IDs', async () => {
    setupFullDom();
    mockDefaultApis();
    (fetchAllMembers as Mock).mockResolvedValue([
      { user_id: '10', login: 'alice' },
      { user_id: '20', login: 'bob' },
    ]);

    await handleGroups();
    await selectFirstGroup();

    (document.getElementById('saveMembersList') as HTMLButtonElement).click();
    await flushPromises();

    expect(updateGroupMembers).toHaveBeenCalledWith('1', [10, 20], 'csrf-members');
    expect(pushNotification).toHaveBeenCalledWith('saved');
  });

  it('should save checked permissions as right IDs', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();
    await selectFirstGroup();

    // fetchGroupRights checked rights 1 and 3; additionally check right 5
    (document.getElementById('group_right_5') as HTMLInputElement).checked = true;
    (document.getElementById('saveGroupRights') as HTMLButtonElement).click();
    await flushPromises();

    expect(updateGroupPermissions).toHaveBeenCalledWith('1', [1, 3, 5], 'csrf-permissions');
    expect(pushNotification).toHaveBeenCalledWith('saved');
  });

  it('should check and uncheck all permissions via the toggle buttons', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();
    await selectFirstGroup();

    (document.getElementById('pmf-group-check-all') as HTMLButtonElement).click();
    document.querySelectorAll<HTMLInputElement>('#pmf-permission-list input.permission').forEach((checkbox) => {
      expect(checkbox.checked).toBe(true);
    });

    (document.getElementById('pmf-group-uncheck-all') as HTMLButtonElement).click();
    document.querySelectorAll<HTMLInputElement>('#pmf-permission-list input.permission').forEach((checkbox) => {
      expect(checkbox.checked).toBe(false);
    });
  });

  it('should delete the group after confirmation and return to the empty state', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();
    await selectFirstGroup();

    (document.getElementById('pmf-delete-group-button') as HTMLButtonElement).click();
    expect(document.getElementById('pmf-group-delete-name')?.textContent).toBe('Admins');
    expect(modalShow).toHaveBeenCalled();

    (document.getElementById('pmf-confirm-group-delete') as HTMLButtonElement).click();
    await flushPromises();

    expect(deleteGroup).toHaveBeenCalledWith('1', 'csrf-delete');
    expect(pushNotification).toHaveBeenCalledWith('deleted');
    expect(document.getElementById('pmf-group-detail')?.classList.contains('d-none')).toBe(true);
    expect(document.getElementById('pmf-group-empty-state')?.classList.contains('d-none')).toBe(false);
  });
});
