import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleUsers } from './users';
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
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { wireAddUserModal } from './add-user';

vi.mock('../api');
vi.mock('../../../../assets/src/utils', () => ({
  capitalize: (value: string): string => value.charAt(0).toUpperCase() + value.slice(1),
  pushNotification: vi.fn(),
  pushErrorNotification: vi.fn(),
}));
vi.mock('./add-user');

const { modalShow, modalHide } = vi.hoisted(() => ({ modalShow: vi.fn(), modalHide: vi.fn() }));
vi.mock('bootstrap', () => ({
  Modal: class {
    show = modalShow;
    hide = modalHide;
    static getInstance = (): { hide: () => void } => ({ hide: modalHide });
    static getOrCreateInstance = (): { show: () => void; hide: () => void } => ({
      show: modalShow,
      hide: modalHide,
    });
  },
}));

const flushPromises = async (): Promise<void> => {
  await new Promise((resolve) => setTimeout(resolve, 10));
};

const setupFullDom = (userId = ''): void => {
  document.body.innerHTML = `
    <input type="hidden" id="current_user_id" value="${userId}">
    <input id="pmf-user-filter" type="search" />
    <div id="pmf-user-list" data-label-blocked="blocked"></div>
    <div id="pmf-user-empty-state"></div>
    <div id="pmf-user-detail" class="d-none"
         data-csrf-update="csrf-update" data-csrf-rights="csrf-rights"
         data-csrf-delete="csrf-delete" data-msg-error="An error occurred."
         data-current-user-id="42">
      <div id="pmf-password-overwrite-row"></div>
      <div id="pmf-password-self-row" class="d-none"></div>
      <span id="pmf-selected-user-name"></span>
      <small id="pmf-selected-user-login"></small>
      <button id="pmf-delete-user-button" type="button"></button>
      <input type="hidden" id="last_modified" value="" />
      <input id="auth_source" type="text" />
      <select id="user_status">
        <option value="active">active</option>
        <option value="blocked">blocked</option>
        <option value="protected">protected</option>
      </select>
      <input id="display_name" type="text" />
      <input id="email" type="email" />
      <input id="is_superadmin" type="checkbox" />
      <input id="overwrite_twofactor" type="checkbox" disabled />
      <button id="pmf-user-save" type="button"></button>
      <input id="pmf-user-permission-filter" type="search" />
      <button id="pmf-user-check-all" type="button"></button>
      <button id="pmf-user-uncheck-all" type="button"></button>
      <div id="pmf-user-permission-list">
        <div class="form-check">
          <input id="user_right_1" type="checkbox" value="1" class="form-check-input permission" />
          <label for="user_right_1">Add FAQ</label>
        </div>
        <div class="form-check">
          <input id="user_right_3" type="checkbox" value="3" class="form-check-input permission" />
          <label for="user_right_3">Edit FAQ</label>
        </div>
      </div>
      <button id="pmf-user-rights-save" type="button"></button>
    </div>
    <div id="pmf-modal-user-confirm-delete">
      <input type="hidden" id="csrf-token-delete-user" value="csrf-delete" />
      <input type="hidden" id="pmf-user-id-delete" value="" />
      <input type="hidden" id="source_page" value="" />
      <span id="pmf-username-delete"></span>
      <button id="pmf-delete-user-yes" type="button"></button>
    </div>
    <div id="pmf-modal-user-password-overwrite">
      <input type="hidden" id="modal_csrf" value="csrf-password" />
      <input id="npass" type="password" value="secret-password" />
      <input id="bpass" type="password" value="secret-password" />
      <button id="pmf-user-password-overwrite-action" type="button"></button>
    </div>
  `;
};

const aliceData = {
  userId: '10',
  login: 'alice',
  displayName: 'Alice Doe',
  email: 'alice@example.org',
  status: 'active',
  lastModified: '20260705120000',
  authSource: 'local',
  twoFactorEnabled: 0,
  isSuperadmin: false,
};

const mockDefaultApis = (): void => {
  (fetchAllUsers as Mock).mockResolvedValue([
    {
      id: 10,
      status: 'active',
      isSuperAdmin: false,
      isVisible: 1,
      displayName: 'Alice Doe',
      userName: 'alice',
      email: 'alice@example.org',
      authSource: 'local',
    },
    {
      id: 20,
      status: 'blocked',
      isSuperAdmin: false,
      isVisible: 1,
      displayName: 'Bob Roe',
      userName: 'bob',
      email: 'bob@example.org',
      authSource: 'local',
    },
  ]);
  (fetchUsers as Mock).mockResolvedValue([{ label: 'alice', value: 10 }]);
  (fetchUserData as Mock).mockResolvedValue(aliceData);
  (fetchUserRights as Mock).mockResolvedValue(['1']);
  (updateUserData as Mock).mockResolvedValue({ success: 'saved' });
  (updateUserRights as Mock).mockResolvedValue({ success: 'saved' });
  (deleteUser as Mock).mockResolvedValue({ success: 'deleted' });
  (overwritePassword as Mock).mockResolvedValue({ success: 'password saved' });
};

const selectFirstUser = async (): Promise<void> => {
  (document.querySelector('.pmf-user-item') as HTMLButtonElement).click();
  await flushPromises();
};

describe('handleUsers', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    window.history.pushState({}, '', '/');
  });

  it('should return early when #pmf-user-list is missing', async () => {
    document.body.innerHTML = '<div></div>';

    await handleUsers();

    expect(fetchAllUsers).not.toHaveBeenCalled();
  });

  it('should render the initial user list with display name, login, and blocked badge', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleUsers();

    const items = document.querySelectorAll<HTMLElement>('.pmf-user-item');
    expect(items.length).toBe(2);
    expect(items[0].dataset.userId).toBe('10');
    expect(items[0].textContent).toContain('Alice Doe');
    expect(items[0].textContent).toContain('alice');
    expect(items[1].querySelector('.badge')?.textContent).toBe('blocked');
  });

  it('should cap the initial list at 50 users', async () => {
    setupFullDom();
    mockDefaultApis();
    (fetchAllUsers as Mock).mockResolvedValue(
      Array.from({ length: 75 }, (_, index) => ({
        id: index + 1,
        status: 'active',
        isSuperAdmin: false,
        isVisible: 1,
        displayName: `User ${index + 1}`,
        userName: `user${index + 1}`,
        email: `user${index + 1}@example.org`,
        authSource: 'local',
      }))
    );

    await handleUsers();

    expect(document.querySelectorAll('.pmf-user-item').length).toBe(50);
  });

  it('should query the server for matches after the filter debounce', async () => {
    setupFullDom();
    mockDefaultApis();

    // Initial load must run on real timers before switching to fake timers for
    // the debounce window — mixing them here would make the async initial
    // refreshUserList hang inside the fake-timer scheduler.
    await handleUsers();
    vi.useFakeTimers();

    const filter = document.getElementById('pmf-user-filter') as HTMLInputElement;
    filter.value = 'ali';
    filter.dispatchEvent(new Event('input'));

    expect(fetchUsers).not.toHaveBeenCalled();
    await vi.advanceTimersByTimeAsync(300);
    vi.useRealTimers();
    await flushPromises();

    expect(fetchUsers).toHaveBeenCalledWith('ali');
    const items = document.querySelectorAll<HTMLElement>('.pmf-user-item');
    expect(items.length).toBe(1);
    expect(items[0].dataset.userId).toBe('10');
  });

  it('should restore the full list when the filter is cleared after a server-side search', async () => {
    setupFullDom();
    mockDefaultApis();

    // Initial load runs on real timers.
    await handleUsers();
    vi.useFakeTimers();

    const filter = document.getElementById('pmf-user-filter') as HTMLInputElement;

    // Type a filter term and wait for the debounce — server returns 1 result.
    filter.value = 'ali';
    filter.dispatchEvent(new Event('input'));
    await vi.advanceTimersByTimeAsync(300);
    vi.useRealTimers();
    await flushPromises();
    expect(document.querySelectorAll('.pmf-user-item').length).toBe(1);

    // Clearing the input should trigger fetchAllUsers and restore 2 items.
    vi.useFakeTimers();
    filter.value = '';
    filter.dispatchEvent(new Event('input'));
    await vi.advanceTimersByTimeAsync(300);
    vi.useRealTimers();
    await flushPromises();

    expect(fetchAllUsers).toHaveBeenCalledTimes(2);
    expect(document.querySelectorAll('.pmf-user-item').length).toBe(2);
  });

  it('should load user data and rights and show the detail card on selection', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleUsers();
    await selectFirstUser();

    expect(fetchUserData).toHaveBeenCalledWith('10');
    expect(fetchUserRights).toHaveBeenCalledWith('10');
    expect((document.getElementById('display_name') as HTMLInputElement).value).toBe('Alice Doe');
    expect((document.getElementById('email') as HTMLInputElement).value).toBe('alice@example.org');
    expect((document.getElementById('user_status') as HTMLSelectElement).value).toBe('active');
    expect((document.getElementById('auth_source') as HTMLInputElement).value).toBe('Local');
    expect((document.getElementById('last_modified') as HTMLInputElement).value).toBe('20260705120000');
    expect(document.getElementById('pmf-selected-user-name')?.textContent).toBe('Alice Doe');
    expect(document.getElementById('pmf-selected-user-login')?.textContent).toBe('alice');
    expect((document.getElementById('user_right_1') as HTMLInputElement).checked).toBe(true);
    expect((document.getElementById('user_right_3') as HTMLInputElement).checked).toBe(false);
    expect(document.getElementById('pmf-user-empty-state')?.classList.contains('d-none')).toBe(true);
    expect(document.getElementById('pmf-user-detail')?.classList.contains('d-none')).toBe(false);
    expect((document.querySelector('.pmf-user-item') as HTMLElement).classList.contains('active')).toBe(true);
  });

  it('should auto-select the deep-linked user from #current_user_id', async () => {
    setupFullDom('10');
    mockDefaultApis();

    await handleUsers();
    await flushPromises();

    expect(fetchUserData).toHaveBeenCalledWith('10');
    expect(document.getElementById('pmf-user-detail')?.classList.contains('d-none')).toBe(false);
  });

  it('should hide the delete button for protected users', async () => {
    setupFullDom();
    mockDefaultApis();
    (fetchUserData as Mock).mockResolvedValue({ ...aliceData, status: 'protected' });

    await handleUsers();
    await selectFirstUser();

    expect(document.getElementById('pmf-delete-user-button')?.classList.contains('d-none')).toBe(true);
  });

  it('should enable the two-factor reset checkbox only when the user has 2FA', async () => {
    setupFullDom();
    mockDefaultApis();
    (fetchUserData as Mock).mockResolvedValue({ ...aliceData, twoFactorEnabled: 1 });

    await handleUsers();
    await selectFirstUser();

    expect((document.getElementById('overwrite_twofactor') as HTMLInputElement).disabled).toBe(false);
  });

  it('should keep the two-factor checkbox disabled when the API sends a "0" string', async () => {
    setupFullDom();
    mockDefaultApis();
    (fetchUserData as Mock).mockResolvedValue({ ...aliceData, twoFactorEnabled: '0' });

    await handleUsers();
    await selectFirstUser();

    expect((document.getElementById('overwrite_twofactor') as HTMLInputElement).disabled).toBe(true);
  });

  it('should leave is_superadmin unchecked when isSuperadmin is the "0" string', async () => {
    setupFullDom();
    mockDefaultApis();
    (fetchUserData as Mock).mockResolvedValue({ ...aliceData, isSuperadmin: '0' });

    await handleUsers();
    await selectFirstUser();

    expect((document.getElementById('is_superadmin') as HTMLInputElement).checked).toBe(false);
  });

  it('should save the profile with a PUT payload and notify on success', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleUsers();
    await selectFirstUser();

    (document.getElementById('display_name') as HTMLInputElement).value = 'Alice Renamed';
    (document.getElementById('pmf-user-save') as HTMLButtonElement).click();
    await flushPromises();

    expect(updateUserData).toHaveBeenCalledWith({
      csrfToken: 'csrf-update',
      userId: '10',
      display_name: 'Alice Renamed',
      email: 'alice@example.org',
      last_modified: '20260705120000',
      user_status: 'active',
      is_superadmin: false,
      overwrite_twofactor: false,
    });
    expect(pushNotification).toHaveBeenCalledWith('saved');
  });

  it('should push an error notification when the profile save fails', async () => {
    setupFullDom();
    mockDefaultApis();
    (updateUserData as Mock).mockResolvedValue({ error: 'nope' });

    await handleUsers();
    await selectFirstUser();

    (document.getElementById('pmf-user-save') as HTMLButtonElement).click();
    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith('nope');
  });

  it('should push the generic error message when the profile save rejects', async () => {
    setupFullDom();
    mockDefaultApis();
    (updateUserData as Mock).mockRejectedValue(new Error('network down'));

    await handleUsers();
    await selectFirstUser();

    (document.getElementById('pmf-user-save') as HTMLButtonElement).click();
    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith('An error occurred.');
  });

  it('should save checked permissions as right values', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleUsers();
    await selectFirstUser();

    (document.getElementById('user_right_3') as HTMLInputElement).checked = true;
    (document.getElementById('pmf-user-rights-save') as HTMLButtonElement).click();
    await flushPromises();

    expect(updateUserRights).toHaveBeenCalledWith('10', ['1', '3'], 'csrf-rights');
    expect(pushNotification).toHaveBeenCalledWith('saved');
  });

  it('should filter the permission list by label', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleUsers();

    const filter = document.getElementById('pmf-user-permission-filter') as HTMLInputElement;
    filter.value = 'edit';
    filter.dispatchEvent(new Event('input'));

    const rows = document.querySelectorAll<HTMLElement>('#pmf-user-permission-list .form-check');
    expect(rows[0].classList.contains('d-none')).toBe(true);
    expect(rows[1].classList.contains('d-none')).toBe(false);
  });

  it('should check and uncheck all permissions via the toggle buttons', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleUsers();

    (document.getElementById('pmf-user-check-all') as HTMLButtonElement).click();
    document.querySelectorAll<HTMLInputElement>('#pmf-user-permission-list input.permission').forEach((checkbox) => {
      expect(checkbox.checked).toBe(true);
    });

    (document.getElementById('pmf-user-uncheck-all') as HTMLButtonElement).click();
    document.querySelectorAll<HTMLInputElement>('#pmf-user-permission-list input.permission').forEach((checkbox) => {
      expect(checkbox.checked).toBe(false);
    });
  });

  it('should delete the user after confirmation and return to the empty state', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleUsers();
    await selectFirstUser();

    // Simulate being on the edit URL before deletion.
    window.history.pushState({}, '', './user/edit/10');
    expect(window.location.pathname.endsWith('/user/edit/10')).toBe(true);

    (document.getElementById('pmf-delete-user-button') as HTMLButtonElement).click();
    expect(document.getElementById('pmf-username-delete')?.textContent).toBe('Alice Doe');
    expect((document.getElementById('source_page') as HTMLInputElement).value).toBe('users');
    expect(modalShow).toHaveBeenCalled();

    (document.getElementById('pmf-delete-user-yes') as HTMLButtonElement).click();
    await flushPromises();

    expect(deleteUser).toHaveBeenCalledWith('10', 'csrf-delete');
    expect(pushNotification).toHaveBeenCalledWith('deleted');
    expect(document.getElementById('pmf-user-detail')?.classList.contains('d-none')).toBe(true);
    expect(document.getElementById('pmf-user-empty-state')?.classList.contains('d-none')).toBe(false);
    expect(window.location.pathname.endsWith('/user')).toBe(true);
  });

  it('should overwrite the password via the modal action', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleUsers();
    await selectFirstUser();

    (document.getElementById('pmf-user-password-overwrite-action') as HTMLButtonElement).click();
    await flushPromises();

    expect(overwritePassword).toHaveBeenCalledWith('csrf-password', '10', 'secret-password', 'secret-password');
    expect(pushNotification).toHaveBeenCalledWith('password saved');
    expect(modalHide).toHaveBeenCalled();
  });

  it('should show the overwrite action when selecting another user', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleUsers();
    await selectFirstUser();

    expect(document.getElementById('pmf-password-overwrite-row')?.classList.contains('d-none')).toBe(false);
    expect(document.getElementById('pmf-password-self-row')?.classList.contains('d-none')).toBe(true);
  });

  it('should point admins to the self-service password form for their own account', async () => {
    setupFullDom();
    mockDefaultApis();
    (fetchUserData as Mock).mockResolvedValue({ ...aliceData, userId: '42' });

    await handleUsers();
    await selectFirstUser();

    expect(document.getElementById('pmf-password-overwrite-row')?.classList.contains('d-none')).toBe(true);
    expect(document.getElementById('pmf-password-self-row')?.classList.contains('d-none')).toBe(false);
  });

  it('should fall back to server search when the new user is not in the initial list', async () => {
    setupFullDom();
    mockDefaultApis();

    // 75 users — none is 'jdoe', so the initial refreshUserList won't find them.
    (fetchAllUsers as Mock).mockResolvedValue(
      Array.from({ length: 75 }, (_, index) => ({
        id: index + 1,
        status: 'active',
        isSuperAdmin: false,
        isVisible: 1,
        displayName: `User ${index + 1}`,
        userName: `user${index + 1}`,
        email: `user${index + 1}@example.org`,
        authSource: 'local',
      }))
    );
    // Server-side search returns the freshly-created account.
    (fetchUsers as Mock).mockResolvedValue([{ label: 'jdoe', value: 99 }]);

    await handleUsers();

    // Retrieve and invoke the callback registered with wireAddUserModal.
    const onUserAdded = (wireAddUserModal as Mock).mock.calls[0][0] as (name: string) => Promise<void>;
    await onUserAdded('jdoe');

    // The item for the new user must exist in the DOM.
    const newItem = document.querySelector<HTMLElement>('[data-user-id="99"]');
    expect(newItem).not.toBeNull();

    // fetchUserData must have been called (item was auto-clicked).
    expect(fetchUserData).toHaveBeenCalledWith('99');
  });
});
