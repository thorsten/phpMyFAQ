import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { wireAddUserModal } from './add-user';
import { addUser } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

vi.mock('../api');
vi.mock('../../../../assets/src/utils', () => ({
  pushNotification: vi.fn(),
  pushErrorNotification: vi.fn(),
}));

const { modalHide } = vi.hoisted(() => ({ modalHide: vi.fn() }));
vi.mock('bootstrap', () => ({
  Modal: class {
    hide = modalHide;
    static getOrCreateInstance = (): { hide: () => void } => ({ hide: modalHide });
  },
}));

const flushPromises = async (): Promise<void> => {
  await new Promise((resolve) => setTimeout(resolve, 10));
};

const setupDom = (): void => {
  document.body.innerHTML = `
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
};

describe('wireAddUserModal', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should do nothing when the add-user modal is absent', () => {
    document.body.innerHTML = '<div></div>';

    expect(() => wireAddUserModal(vi.fn())).not.toThrow();
  });

  it('should post the new user and run the success callback', async () => {
    setupDom();
    (addUser as Mock).mockResolvedValue({ success: 'added' });
    const onUserAdded = vi.fn().mockResolvedValue(undefined);

    wireAddUserModal(onUserAdded);
    (document.getElementById('pmf-add-user-action') as HTMLButtonElement).click();
    await flushPromises();

    expect(addUser).toHaveBeenCalledWith({
      csrf: 'csrf-add',
      userName: 'jdoe',
      realName: 'Jane Doe',
      email: 'jane@example.org',
      automaticPassword: true,
      password: '',
      passwordConfirm: '',
      isSuperAdmin: false,
    });
    expect(pushNotification).toHaveBeenCalledWith('added');
    expect(modalHide).toHaveBeenCalled();
    expect(onUserAdded).toHaveBeenCalledWith('jdoe');
  });

  it('should join array error responses into one error notification', async () => {
    setupDom();
    (addUser as Mock).mockResolvedValue(['Login invalid', 'Email missing']);
    const onUserAdded = vi.fn();

    wireAddUserModal(onUserAdded);
    (document.getElementById('pmf-add-user-action') as HTMLButtonElement).click();
    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith('Login invalid\nEmail missing');
    expect(onUserAdded).not.toHaveBeenCalled();
  });

  it('should push the generic error message when the request rejects', async () => {
    setupDom();
    (addUser as Mock).mockRejectedValue(new Error('network down'));

    wireAddUserModal(vi.fn());
    (document.getElementById('pmf-add-user-action') as HTMLButtonElement).click();
    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith('An error occurred.');
  });

  it('should toggle the password inputs with the automatic-password checkbox', () => {
    setupDom();

    wireAddUserModal(vi.fn());
    (document.getElementById('add_user_automatic_password') as HTMLInputElement).click();

    expect(document.getElementById('add_user_show_password_inputs')?.classList.contains('d-none')).toBe(false);
  });
});
