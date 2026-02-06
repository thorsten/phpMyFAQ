import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleWebPush } from './webpush';
import { fetchGenerateVapidKeys } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

vi.mock('../api');
vi.mock('../../../../assets/src/utils');

const setupBasicDom = (options?: { publicKey?: string; privateKey?: string; hasParent?: boolean }): void => {
  const { publicKey = '', privateKey = '', hasParent = true } = options ?? {};

  const availableFields = JSON.stringify([
    'main.titleFAQ',
    'push.vapidPublicKey',
    'push.vapidPrivateKey',
    'records.numberOfRecordsPerPage',
  ]);

  if (hasParent) {
    document.body.innerHTML = `
      <div>
        <input id="edit[push.vapidPublicKey]" name="edit[push.vapidPublicKey]" value="${publicKey}" />
      </div>
      <div>
        <input id="edit[push.vapidPrivateKey]" name="edit[push.vapidPrivateKey]" value="${privateKey}" />
      </div>
      <input type="hidden" name="availableFields" value='${availableFields}' />
      <input type="hidden" id="pmf-csrf-token" value="test-csrf-token" />
    `;
  } else {
    document.body.innerHTML = `
      <input id="edit[push.vapidPublicKey]" name="edit[push.vapidPublicKey]" value="${publicKey}" />
      <input id="edit[push.vapidPrivateKey]" name="edit[push.vapidPrivateKey]" value="${privateKey}" />
      <input type="hidden" name="availableFields" value='${availableFields}' />
      <input type="hidden" id="pmf-csrf-token" value="test-csrf-token" />
    `;
  }
};

describe('WebPush Functions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('handleWebPush', () => {
    it('should return early when public key input is missing', async () => {
      document.body.innerHTML = '<div></div>';

      await handleWebPush();

      expect(document.getElementById('pmf-generate-vapid-keys')).toBeNull();
    });

    it('should remove name attributes from VAPID key inputs', async () => {
      setupBasicDom();

      await handleWebPush();

      const publicKeyInput = document.getElementById('edit[push.vapidPublicKey]') as HTMLInputElement;
      const privateKeyInput = document.getElementById('edit[push.vapidPrivateKey]') as HTMLInputElement;

      expect(publicKeyInput.hasAttribute('name')).toBe(false);
      expect(privateKeyInput.hasAttribute('name')).toBe(false);
    });

    it('should filter VAPID fields from availableFields input', async () => {
      setupBasicDom();

      await handleWebPush();

      const availableFieldsInput = document.querySelector<HTMLInputElement>('input[name="availableFields"]');
      const fields = JSON.parse(availableFieldsInput?.value ?? '[]');

      expect(fields).not.toContain('push.vapidPublicKey');
      expect(fields).not.toContain('push.vapidPrivateKey');
      expect(fields).toContain('main.titleFAQ');
      expect(fields).toContain('records.numberOfRecordsPerPage');
    });

    it('should mask private key with bullet characters when it has a value', async () => {
      setupBasicDom({ privateKey: 'secret-private-key' });

      await handleWebPush();

      const privateKeyInput = document.getElementById('edit[push.vapidPrivateKey]') as HTMLInputElement;
      expect(privateKeyInput.value).toBe('\u2022'.repeat(20));
    });

    it('should not mask private key when it is empty', async () => {
      setupBasicDom({ privateKey: '' });

      await handleWebPush();

      const privateKeyInput = document.getElementById('edit[push.vapidPrivateKey]') as HTMLInputElement;
      expect(privateKeyInput.value).toBe('');
    });

    it('should add generate VAPID keys button', async () => {
      setupBasicDom();

      await handleWebPush();

      const button = document.getElementById('pmf-generate-vapid-keys') as HTMLButtonElement;
      expect(button).not.toBeNull();
      expect(button.type).toBe('button');
      expect(button.className).toBe('btn btn-outline-primary mt-2');
      expect(button.innerHTML).toContain('Generate VAPID Keys');
    });

    it('should not add the button multiple times', async () => {
      setupBasicDom();

      await handleWebPush();
      await handleWebPush();

      const buttons = document.querySelectorAll('#pmf-generate-vapid-keys');
      expect(buttons.length).toBe(1);
    });

    it('should generate VAPID keys on button click and show success', async () => {
      setupBasicDom();

      (fetchGenerateVapidKeys as Mock).mockResolvedValue({
        success: true,
        publicKey: 'generated-public-key',
      });

      await handleWebPush();

      const button = document.getElementById('pmf-generate-vapid-keys') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(fetchGenerateVapidKeys).toHaveBeenCalledWith('test-csrf-token');

      const publicKeyInput = document.getElementById('edit[push.vapidPublicKey]') as HTMLInputElement;
      expect(publicKeyInput.value).toBe('generated-public-key');

      expect(pushNotification).toHaveBeenCalledWith('VAPID keys have been generated successfully.');
    });

    it('should mask private key after successful generation', async () => {
      setupBasicDom();

      (fetchGenerateVapidKeys as Mock).mockResolvedValue({
        success: true,
        publicKey: 'generated-public-key',
      });

      await handleWebPush();

      const button = document.getElementById('pmf-generate-vapid-keys') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      const privateKeyInput = document.getElementById('edit[push.vapidPrivateKey]') as HTMLInputElement;
      expect(privateKeyInput.value).toBe('\u2022'.repeat(20));
    });

    it('should show error notification on API error response', async () => {
      setupBasicDom();

      (fetchGenerateVapidKeys as Mock).mockResolvedValue({
        success: false,
        publicKey: '',
        error: 'Server error occurred',
      });

      await handleWebPush();

      const button = document.getElementById('pmf-generate-vapid-keys') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('Server error occurred');
    });

    it('should show default error message when API returns no error string', async () => {
      setupBasicDom();

      (fetchGenerateVapidKeys as Mock).mockResolvedValue({
        success: false,
        publicKey: '',
      });

      await handleWebPush();

      const button = document.getElementById('pmf-generate-vapid-keys') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('Failed to generate VAPID keys.');
    });

    it('should show error notification when fetch rejects', async () => {
      setupBasicDom();

      (fetchGenerateVapidKeys as Mock).mockRejectedValue(new Error('Network error'));

      await handleWebPush();

      const button = document.getElementById('pmf-generate-vapid-keys') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('Failed to generate VAPID keys.');
    });

    it('should disable button during generation and re-enable after', async () => {
      setupBasicDom();

      let resolvePromise: (value: unknown) => void = () => {};
      (fetchGenerateVapidKeys as Mock).mockImplementation(
        () =>
          new Promise((resolve) => {
            resolvePromise = resolve;
          })
      );

      await handleWebPush();

      const button = document.getElementById('pmf-generate-vapid-keys') as HTMLButtonElement;
      button.click();

      // Button should be disabled and show spinner
      expect(button.disabled).toBe(true);
      expect(button.innerHTML).toContain('spinner-border');
      expect(button.innerHTML).toContain('Generating...');

      // Resolve the promise
      resolvePromise({ success: true, publicKey: 'key' });
      await new Promise((resolve) => setTimeout(resolve, 10));

      // Button should be re-enabled with original content
      expect(button.disabled).toBe(false);
      expect(button.innerHTML).toContain('Generate VAPID Keys');
    });

    it('should re-enable button after failed generation', async () => {
      setupBasicDom();

      (fetchGenerateVapidKeys as Mock).mockRejectedValue(new Error('fail'));

      await handleWebPush();

      const button = document.getElementById('pmf-generate-vapid-keys') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(button.disabled).toBe(false);
      expect(button.innerHTML).toContain('Generate VAPID Keys');
    });

    it('should return early when public key input has no parent element', async () => {
      // Create input without parent by using a document fragment trick
      document.body.innerHTML = `
        <input id="edit[push.vapidPublicKey]" value="" />
        <input type="hidden" id="pmf-csrf-token" value="test-csrf-token" />
      `;

      // Remove the parent element reference by moving the input to a fragment
      const input = document.getElementById('edit[push.vapidPublicKey]') as HTMLInputElement;
      const fragment = document.createDocumentFragment();
      fragment.appendChild(input);

      await handleWebPush();

      // Button should not be created since parentElement is null in a fragment
      expect(document.getElementById('pmf-generate-vapid-keys')).toBeNull();
    });

    it('should handle missing availableFields input gracefully', async () => {
      document.body.innerHTML = `
        <div>
          <input id="edit[push.vapidPublicKey]" name="edit[push.vapidPublicKey]" value="" />
        </div>
        <input type="hidden" id="pmf-csrf-token" value="test-csrf-token" />
      `;

      // Should not throw
      await handleWebPush();

      const button = document.getElementById('pmf-generate-vapid-keys') as HTMLButtonElement;
      expect(button).not.toBeNull();
    });

    it('should handle invalid JSON in availableFields gracefully', async () => {
      document.body.innerHTML = `
        <div>
          <input id="edit[push.vapidPublicKey]" name="edit[push.vapidPublicKey]" value="" />
        </div>
        <input type="hidden" name="availableFields" value="not-valid-json" />
        <input type="hidden" id="pmf-csrf-token" value="test-csrf-token" />
      `;

      // Should not throw due to catch block
      await handleWebPush();

      const button = document.getElementById('pmf-generate-vapid-keys') as HTMLButtonElement;
      expect(button).not.toBeNull();
    });

    it('should handle missing csrf token input', async () => {
      document.body.innerHTML = `
        <div>
          <input id="edit[push.vapidPublicKey]" name="edit[push.vapidPublicKey]" value="" />
        </div>
      `;

      (fetchGenerateVapidKeys as Mock).mockResolvedValue({
        success: true,
        publicKey: 'key',
      });

      await handleWebPush();

      const button = document.getElementById('pmf-generate-vapid-keys') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(fetchGenerateVapidKeys).toHaveBeenCalledWith('');
    });
  });
});
