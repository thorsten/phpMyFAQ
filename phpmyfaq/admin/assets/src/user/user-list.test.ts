import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { activateUser, deleteUser, overwritePassword } from '../api';

vi.mock('../api');

global.fetch = vi.fn();

describe('User API', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should overwrite password', async () => {
    const mockResponse = { success: true };
    (overwritePassword as Mock).mockResolvedValue(mockResponse);

    const response = await overwritePassword('csrfToken', 'userId', 'newPassword', 'passwordRepeat');
    expect(overwritePassword).toHaveBeenCalledWith('csrfToken', 'userId', 'newPassword', 'passwordRepeat');
    expect(response).toEqual(mockResponse);
  });

  it('should activate user', async () => {
    const mockResponse = { success: true };
    (activateUser as Mock).mockResolvedValue(mockResponse);

    const response = await activateUser('userId', 'csrfToken');
    expect(activateUser).toHaveBeenCalledWith('userId', 'csrfToken');
    expect(response).toEqual(mockResponse);
  });

  it('should delete user', async () => {
    const mockResponse = { success: true };
    (deleteUser as Mock).mockResolvedValue(mockResponse);

    const response = await deleteUser('userId', 'csrfToken');
    expect(deleteUser).toHaveBeenCalledWith('userId', 'csrfToken');
    expect(response).toEqual(mockResponse);
  });
});

// Mocks for handleUserList tests
vi.mock('bootstrap', () => {
  const showFn = vi.fn();
  const hideFn = vi.fn();
  const modalInstance = { show: showFn, hide: hideFn };
  class ModalMock {
    show = showFn;
    hide = hideFn;
    static getOrCreateInstance = vi.fn(() => modalInstance);
  }
  return { Modal: ModalMock };
});

vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    addElement: vi.fn((_tag: string, props: Record<string, unknown>) => {
      const el = document.createElement('div');
      if (props.classList) el.className = props.classList as string;
      if (props.innerText) el.innerText = props.innerText as string;
      return el;
    }),
    pushNotification: vi.fn(),
    pushErrorNotification: vi.fn(),
  };
});

import { handleUserList } from './user-list';
import { addUser } from '../api';
import { addElement, pushNotification, pushErrorNotification } from '../../../../assets/src/utils';
import { Modal } from 'bootstrap';

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

describe('handleUserList', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    vi.spyOn(console, 'error').mockImplementation(() => {});
  });

  it('should do nothing when no activate or delete buttons exist', () => {
    document.body.innerHTML = '<div></div>';

    expect(() => handleUserList()).not.toThrow();
  });

  it('should activate user, update icon, and remove button on success', async () => {
    (activateUser as Mock).mockResolvedValue({ success: 'User activated' });

    document.body.innerHTML = `
      <button class="btn-activate-user" id="btn_activate_user_id_42" data-csrf-token="csrf123" data-user-id="42">Activate</button>
      <i class="icon_user_id_42 bi-ban"></i>
      <div id="pmf-user-message"></div>
    `;

    handleUserList();

    const button = document.querySelector('.btn-activate-user') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(activateUser).toHaveBeenCalledWith('42', 'csrf123');
    const icon = document.querySelector('.icon_user_id_42') as HTMLElement;
    expect(icon.classList.contains('bi-ban')).toBe(false);
    expect(icon.classList.contains('bi-check-circle-o')).toBe(true);
    expect(document.getElementById('btn_activate_user_id_42')).toBeNull();
  });

  it('should show error alert when activateUser returns error', async () => {
    (activateUser as Mock).mockResolvedValue({ error: 'Activation failed' });

    document.body.innerHTML = `
      <button class="btn-activate-user" data-csrf-token="csrf123" data-user-id="42">Activate</button>
      <div id="pmf-user-message"></div>
    `;

    handleUserList();

    const button = document.querySelector('.btn-activate-user') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(addElement).toHaveBeenCalledWith('div', {
      classList: 'alert alert-danger',
      innerText: 'Activation failed',
    });
  });

  it('should return early when data attributes are missing on activate button', async () => {
    document.body.innerHTML = `
      <button class="btn-activate-user">Activate</button>
    `;

    handleUserList();

    const button = document.querySelector('.btn-activate-user') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(activateUser).not.toHaveBeenCalled();
    expect(console.error).toHaveBeenCalledWith('Missing data-csrf-token or data-user-id attribute');
  });

  it('should show modal and store username/userId when delete button is clicked', async () => {
    document.body.innerHTML = `
      <button class="btn-delete-user" data-username="testuser" data-user-id="42">Delete</button>
      <div id="pmf-modal-user-confirm-delete"></div>
      <div id="pmf-username-delete"></div>
      <input id="pmf-user-id-delete" />
      <input id="source_page" />
      <button id="pmf-delete-user-yes"></button>
      <input id="csrf-token-delete-user" value="csrf-del" />
    `;

    handleUserList();

    const deleteBtn = document.querySelector('.btn-delete-user') as HTMLButtonElement;
    deleteBtn.click();

    expect(Modal.getOrCreateInstance).toHaveBeenCalled();
    const usernameEl = document.getElementById('pmf-username-delete') as HTMLElement;
    expect(usernameEl.innerText).toBe('testuser');
    const userIdEl = document.getElementById('pmf-user-id-delete') as HTMLInputElement;
    expect(userIdEl.value).toBe('42');
    const sourcePage = document.getElementById('source_page') as HTMLInputElement;
    expect(sourcePage.value).toBe('user-list');
  });

  it('should delete user, remove row, and show notification on confirm delete', async () => {
    (deleteUser as Mock).mockResolvedValue({ success: 'User deleted' });

    document.body.innerHTML = `
      <button class="btn-delete-user" data-username="testuser" data-user-id="42">Delete</button>
      <div id="pmf-modal-user-confirm-delete"></div>
      <div id="pmf-username-delete"></div>
      <input id="pmf-user-id-delete" value="42" />
      <input id="source_page" value="user-list" />
      <button id="pmf-delete-user-yes"></button>
      <input id="csrf-token-delete-user" value="csrf-del" />
      <div id="row_user_id_42">Test</div>
    `;

    handleUserList();

    const confirmBtn = document.getElementById('pmf-delete-user-yes') as HTMLButtonElement;
    confirmBtn.dispatchEvent(new MouseEvent('click', { bubbles: true }));

    await flushPromises();

    expect(deleteUser).toHaveBeenCalledWith('42', 'csrf-del');
    expect(pushNotification).toHaveBeenCalledWith('User deleted');
    expect(document.getElementById('row_user_id_42')).toBeNull();
  });

  it('should show error notification on delete user error', async () => {
    (deleteUser as Mock).mockResolvedValue({ error: 'Delete failed' });

    document.body.innerHTML = `
      <button class="btn-delete-user" data-username="testuser" data-user-id="42">Delete</button>
      <div id="pmf-modal-user-confirm-delete"></div>
      <div id="pmf-username-delete"></div>
      <input id="pmf-user-id-delete" value="42" />
      <input id="source_page" value="user-list" />
      <button id="pmf-delete-user-yes"></button>
      <input id="csrf-token-delete-user" value="csrf-del" />
    `;

    handleUserList();

    const confirmBtn = document.getElementById('pmf-delete-user-yes') as HTMLButtonElement;
    confirmBtn.dispatchEvent(new MouseEvent('click', { bubbles: true }));

    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith('Delete failed');
  });

  it('should show error notification when modal element for delete is missing', () => {
    document.body.innerHTML = `
      <button class="btn-delete-user" data-username="testuser" data-user-id="42">Delete</button>
    `;

    handleUserList();

    const deleteBtn = document.querySelector('.btn-delete-user') as HTMLButtonElement;
    deleteBtn.click();

    expect(pushErrorNotification).toHaveBeenCalledWith('Fehler: Löschdialog nicht gefunden.');
  });

  it('should navigate to the CSV export endpoint when the export button is clicked', () => {
    document.body.innerHTML = `
      <table id="pmf-admin-user-table"></table>
      <button id="pmf-button-export-users" type="button"></button>
    `;
    // jsdom cannot navigate; swap window.location for a writable stub
    const originalLocation = window.location;
    Object.defineProperty(window, 'location', { value: { href: '' }, writable: true });

    try {
      handleUserList();
      (document.getElementById('pmf-button-export-users') as HTMLButtonElement).click();

      expect(window.location.href).toBe('./api/user/users.csv');
    } finally {
      Object.defineProperty(window, 'location', { value: originalLocation, writable: true });
    }
  });

  it('should wire the add-user modal on the user-list page', async () => {
    document.body.innerHTML = `
      <table id="pmf-admin-user-table"></table>
      <div id="addUserModal">
        <form id="pmf-add-user-form" data-msg-error="An error occurred.">
          <input id="add_user_csrf" value="csrf-add" />
          <input id="add_user_name" value="jdoe" />
          <input id="add_user_realname" value="Jane Doe" />
          <input id="add_user_email" value="jane@example.org" />
          <input id="add_user_automatic_password" type="checkbox" checked />
          <div id="add_user_show_password_inputs" class="d-none">
            <input id="add_user_password" value="" />
            <input id="add_user_password_confirm" value="" />
          </div>
        </form>
        <button id="pmf-add-user-action" type="button"></button>
      </div>
    `;
    (addUser as Mock).mockResolvedValue({ success: 'added' });
    // success path calls location.reload(); stub it for jsdom
    const originalLocation = window.location;
    Object.defineProperty(window, 'location', {
      value: { href: '', reload: vi.fn() },
      writable: true,
    });

    try {
      handleUserList();
      (document.getElementById('pmf-add-user-action') as HTMLButtonElement).click();
      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(addUser).toHaveBeenCalled();
      expect((window.location as unknown as { reload: Mock }).reload).toHaveBeenCalled();
    } finally {
      Object.defineProperty(window, 'location', { value: originalLocation, writable: true });
    }
  });

  it('should not wire the add-user modal when the user-list table is absent', () => {
    document.body.innerHTML = `
      <div id="addUserModal">
        <form id="pmf-add-user-form"></form>
        <button id="pmf-add-user-action" type="button"></button>
      </div>
    `;

    handleUserList();
    (document.getElementById('pmf-add-user-action') as HTMLButtonElement).click();

    expect(addUser).not.toHaveBeenCalled();
  });
});
