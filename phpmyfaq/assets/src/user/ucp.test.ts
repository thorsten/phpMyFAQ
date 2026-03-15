import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('../api', () => ({
  updateUserControlPanelData: vi.fn(),
  removeTwofactorConfig: vi.fn(),
}));

vi.mock('../utils', () => ({
  addElement: vi.fn((tag: string, props: Record<string, string>) => {
    const el = document.createElement(tag);
    if (props.classList) el.className = props.classList;
    if (props.innerText) el.innerText = props.innerText;
    return el;
  }),
  pushNotification: vi.fn(),
  pushErrorNotification: vi.fn(),
}));

import { handleUserControlPanel } from './ucp';
import { updateUserControlPanelData, removeTwofactorConfig } from '../api';
import { pushNotification, pushErrorNotification } from '../utils';

describe('handleUserControlPanel', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should do nothing when submit button is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleUserControlPanel();

    expect(updateUserControlPanelData).not.toHaveBeenCalled();
  });

  describe('user data update', () => {
    it('should show success message on successful update', async () => {
      document.body.innerHTML = `
        <form id="pmf-user-control-panel-form">
          <input name="display_name" value="John Doe" />
        </form>
        <button id="pmf-submit-user-control-panel">Save</button>
        <div id="loader"></div>
        <div id="pmf-user-control-panel-response"></div>
      `;

      vi.mocked(updateUserControlPanelData).mockResolvedValue({ success: 'Profile updated' });

      handleUserControlPanel();

      const button = document.getElementById('pmf-submit-user-control-panel') as HTMLButtonElement;
      button.click();

      await vi.waitFor(() => {
        expect(updateUserControlPanelData).toHaveBeenCalled();
      });

      const loader = document.getElementById('loader') as HTMLElement;
      expect(loader.classList.contains('d-none')).toBe(true);

      const successAlert = document.querySelector('.alert-success') as HTMLElement | null;
      expect(successAlert).not.toBeNull();
      expect(successAlert?.innerText).toBe('Profile updated');
    });

    it('should show error message on failed update', async () => {
      document.body.innerHTML = `
        <form id="pmf-user-control-panel-form">
          <input name="email" value="invalid" />
        </form>
        <button id="pmf-submit-user-control-panel">Save</button>
        <div id="loader"></div>
        <div id="pmf-user-control-panel-response"></div>
      `;

      vi.mocked(updateUserControlPanelData).mockResolvedValue({ error: 'Invalid email address' });

      handleUserControlPanel();

      const button = document.getElementById('pmf-submit-user-control-panel') as HTMLButtonElement;
      button.click();

      await vi.waitFor(() => {
        expect(updateUserControlPanelData).toHaveBeenCalled();
      });

      const loader = document.getElementById('loader') as HTMLElement;
      expect(loader.classList.contains('d-none')).toBe(true);

      const errorAlert = document.querySelector('.alert-danger') as HTMLElement | null;
      expect(errorAlert).not.toBeNull();
      expect(errorAlert?.innerText).toBe('Invalid email address');
    });

    it('should send form data to API', async () => {
      document.body.innerHTML = `
        <form id="pmf-user-control-panel-form">
          <input name="display_name" value="Jane" />
          <input name="email" value="jane@example.com" />
        </form>
        <button id="pmf-submit-user-control-panel">Save</button>
        <div id="loader"></div>
        <div id="pmf-user-control-panel-response"></div>
      `;

      vi.mocked(updateUserControlPanelData).mockResolvedValue({ success: 'OK' });

      handleUserControlPanel();

      const button = document.getElementById('pmf-submit-user-control-panel') as HTMLButtonElement;
      button.click();

      await vi.waitFor(() => {
        expect(updateUserControlPanelData).toHaveBeenCalledTimes(1);
      });

      const passedData = vi.mocked(updateUserControlPanelData).mock.calls[0][0] as FormData;
      expect(passedData).toBeInstanceOf(FormData);
      expect(passedData.get('display_name')).toBe('Jane');
      expect(passedData.get('email')).toBe('jane@example.com');
    });
  });

  describe('remove two-factor authentication', () => {
    it('should not set up listener when confirm button is missing', () => {
      document.body.innerHTML = `
        <form id="pmf-user-control-panel-form"></form>
        <button id="pmf-submit-user-control-panel">Save</button>
        <div id="loader"></div>
        <div id="pmf-user-control-panel-response"></div>
      `;

      handleUserControlPanel();

      expect(removeTwofactorConfig).not.toHaveBeenCalled();
    });

    it('should remove two-factor config and show notification on success', async () => {
      document.body.innerHTML = `
        <form id="pmf-user-control-panel-form"></form>
        <button id="pmf-submit-user-control-panel">Save</button>
        <div id="loader"></div>
        <div id="pmf-user-control-panel-response"></div>
        <input id="pmf-csrf-token-remove-twofactor" value="csrf-token-123" />
        <button id="pmf-remove-twofactor-confirm">Remove 2FA</button>
        <input id="twofactor_enabled" type="checkbox" checked />
        <div id="removeCurrentConfig" style="display: block;"></div>
      `;

      vi.mocked(removeTwofactorConfig).mockResolvedValue({ success: '2FA removed successfully' });

      handleUserControlPanel();

      const confirmButton = document.getElementById('pmf-remove-twofactor-confirm') as HTMLButtonElement;
      confirmButton.click();

      await vi.waitFor(() => {
        expect(removeTwofactorConfig).toHaveBeenCalledWith('csrf-token-123');
      });

      expect(pushNotification).toHaveBeenCalledWith('2FA removed successfully');

      const checkbox = document.getElementById('twofactor_enabled') as HTMLInputElement;
      expect(checkbox.checked).toBe(false);

      const configSection = document.getElementById('removeCurrentConfig') as HTMLElement;
      expect(configSection.style.display).toBe('none');
    });

    it('should show error notification when remove fails', async () => {
      document.body.innerHTML = `
        <form id="pmf-user-control-panel-form"></form>
        <button id="pmf-submit-user-control-panel">Save</button>
        <div id="loader"></div>
        <div id="pmf-user-control-panel-response"></div>
        <input id="pmf-csrf-token-remove-twofactor" value="bad-token" />
        <button id="pmf-remove-twofactor-confirm">Remove 2FA</button>
      `;

      vi.mocked(removeTwofactorConfig).mockResolvedValue({ error: 'Invalid CSRF token' });

      handleUserControlPanel();

      const confirmButton = document.getElementById('pmf-remove-twofactor-confirm') as HTMLButtonElement;
      confirmButton.click();

      await vi.waitFor(() => {
        expect(removeTwofactorConfig).toHaveBeenCalledWith('bad-token');
      });

      expect(pushErrorNotification).toHaveBeenCalledWith('Invalid CSRF token');
    });

    it('should not uncheck checkbox or hide config section when they are missing', async () => {
      document.body.innerHTML = `
        <form id="pmf-user-control-panel-form"></form>
        <button id="pmf-submit-user-control-panel">Save</button>
        <div id="loader"></div>
        <div id="pmf-user-control-panel-response"></div>
        <input id="pmf-csrf-token-remove-twofactor" value="csrf-token" />
        <button id="pmf-remove-twofactor-confirm">Remove 2FA</button>
      `;

      vi.mocked(removeTwofactorConfig).mockResolvedValue({ success: 'Done' });

      handleUserControlPanel();

      const confirmButton = document.getElementById('pmf-remove-twofactor-confirm') as HTMLButtonElement;
      confirmButton.click();

      await vi.waitFor(() => {
        expect(removeTwofactorConfig).toHaveBeenCalled();
      });

      // Should not throw even when optional elements are missing
      expect(pushNotification).toHaveBeenCalledWith('Done');
    });
  });
});
