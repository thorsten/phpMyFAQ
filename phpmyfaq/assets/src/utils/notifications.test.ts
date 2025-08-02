import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { Toast } from 'bootstrap';
import { pushNotification, pushErrorNotification } from './notifications';

vi.mock('bootstrap', () => ({
  Toast: {
    getOrCreateInstance: vi.fn(),
  },
}));

describe('notifications', (): void => {
  const mockToast = {
    show: vi.fn(),
    hide: vi.fn(),
    isShown: vi.fn(),
    dispose: vi.fn(),
  } as any;
  const mockGetOrCreateInstance = vi.mocked(Toast.getOrCreateInstance);

  beforeEach((): void => {
    vi.clearAllMocks();
    mockGetOrCreateInstance.mockReturnValue(mockToast as any);
  });

  afterEach((): void => {
    document.body.innerHTML = '';
  });

  describe('pushNotification', (): void => {
    it('should show notification with message when elements exist', (): void => {
      document.body.innerHTML = `
      <div id="pmf-notification"></div>
      <div id="pmf-notification-message"></div>
    `;

      const toast = document.getElementById('pmf-notification');
      const messageElement = document.getElementById('pmf-notification-message');

      pushNotification('Test message');

      expect(mockGetOrCreateInstance).toHaveBeenCalledWith(toast);
      expect(messageElement?.innerText).toBe('Test message');
      expect(mockToast.show).toHaveBeenCalledTimes(1);
    });

    it('should show notification without setting message when message is empty', (): void => {
      document.body.innerHTML = `
      <div id="pmf-notification"></div>
      <div id="pmf-notification-message">Original text</div>
    `;

      const toast = document.getElementById('pmf-notification');
      const messageElement = document.getElementById('pmf-notification-message');

      pushNotification('');

      expect(mockGetOrCreateInstance).toHaveBeenCalledWith(toast);
      expect(messageElement?.textContent).toBe('Original text');
      expect(mockToast.show).toHaveBeenCalledTimes(1);
    });

    it('should not show notification when toast element does not exist', (): void => {
      document.body.innerHTML = '<div id="pmf-notification-message"></div>';

      pushNotification('Test message');

      expect(mockGetOrCreateInstance).not.toHaveBeenCalled();
      expect(mockToast.show).not.toHaveBeenCalled();
    });

    it('should not show notification when message element does not exist', (): void => {
      document.body.innerHTML = '<div id="pmf-notification"></div>';

      pushNotification('Test message');

      expect(mockGetOrCreateInstance).not.toHaveBeenCalled();
      expect(mockToast.show).not.toHaveBeenCalled();
    });

    it('should not show notification when no elements exist', (): void => {
      document.body.innerHTML = '';

      pushNotification('Test message');

      expect(mockGetOrCreateInstance).not.toHaveBeenCalled();
      expect(mockToast.show).not.toHaveBeenCalled();
    });
  });

  describe('pushErrorNotification', (): void => {
    it('should show error notification with message when elements exist', (): void => {
      document.body.innerHTML = `
        <div id="pmf-notification-error"></div>
        <div id="pmf-notification-error-message"></div>
      `;

      const toast = document.getElementById('pmf-notification-error');
      const messageElement = document.getElementById('pmf-notification-error-message');

      pushErrorNotification('Error message');

      expect(mockGetOrCreateInstance).toHaveBeenCalledWith(toast);
      expect(messageElement?.innerText).toBe('Error message');
      expect(mockToast.show).toHaveBeenCalledTimes(1);
    });

    it('should show error notification without setting message when message is empty', (): void => {
      document.body.innerHTML = `
    <div id="pmf-notification-error"></div>
    <div id="pmf-notification-error-message">Original error</div>
  `;

      const toast = document.getElementById('pmf-notification-error');
      const messageElement = document.getElementById('pmf-notification-error-message');

      pushErrorNotification('');

      expect(mockGetOrCreateInstance).toHaveBeenCalledWith(toast);
      expect(messageElement?.innerText).toBe(undefined);
      expect(mockToast.show).toHaveBeenCalledTimes(1);
    });

    it('should not show error notification when toast element does not exist', (): void => {
      document.body.innerHTML = '<div id="pmf-notification-error-message"></div>';

      pushErrorNotification('Error message');

      expect(mockGetOrCreateInstance).not.toHaveBeenCalled();
      expect(mockToast.show).not.toHaveBeenCalled();
    });

    it('should not show error notification when message element does not exist', (): void => {
      document.body.innerHTML = '<div id="pmf-notification-error"></div>';

      pushErrorNotification('Error message');

      expect(mockGetOrCreateInstance).not.toHaveBeenCalled();
      expect(mockToast.show).not.toHaveBeenCalled();
    });

    it('should not show error notification when no elements exist', (): void => {
      document.body.innerHTML = '';

      pushErrorNotification('Error message');

      expect(mockGetOrCreateInstance).not.toHaveBeenCalled();
      expect(mockToast.show).not.toHaveBeenCalled();
    });
  });
});
