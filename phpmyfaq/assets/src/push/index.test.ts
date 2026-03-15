import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

vi.mock('../api/push', () => ({
  getVapidPublicKey: vi.fn(),
  subscribePush: vi.fn(),
  unsubscribePush: vi.fn(),
}));

vi.mock('../utils', () => ({
  addElement: vi.fn((_tag: string, props: Record<string, string>, children: Node[] = []) => {
    const el = document.createElement(_tag);
    if (props.className) el.className = props.className;
    if (props.role) el.setAttribute('role', props.role);
    children.forEach((child) => el.appendChild(child));
    return el;
  }),
}));

import { handlePushNotifications } from './index';
import { getVapidPublicKey, subscribePush, unsubscribePush } from '../api/push';

const setupServiceWorker = (existingSubscription: PushSubscription | null = null) => {
  const mockSubscription = {
    endpoint: 'https://push.example.com/sub/new',
    getKey: vi.fn().mockReturnValue(new ArrayBuffer(8)),
    unsubscribe: vi.fn().mockResolvedValue(true),
  };

  const mockRegistration = {
    pushManager: {
      subscribe: vi.fn().mockResolvedValue(mockSubscription),
      getSubscription: vi.fn().mockResolvedValue(existingSubscription),
    },
  };

  Object.defineProperty(navigator, 'serviceWorker', {
    value: { register: vi.fn().mockResolvedValue(mockRegistration) },
    writable: true,
    configurable: true,
  });

  return { mockRegistration, mockSubscription };
};

describe('handlePushNotifications', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.head.innerHTML = '<base href="http://localhost/">';
    document.body.innerHTML = '';
    localStorage.clear();

    Object.defineProperty(window, 'isSecureContext', { value: true, writable: true, configurable: true });
    Object.defineProperty(window, 'PushManager', { value: vi.fn(), writable: true, configurable: true });
    Object.defineProperty(window, 'Notification', {
      value: { permission: 'default' },
      writable: true,
      configurable: true,
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should return early when neither button nor banner exist', async () => {
    document.body.innerHTML = '<div></div>';
    await handlePushNotifications();
    expect(getVapidPublicKey).not.toHaveBeenCalled();
  });

  it('should return early for banner-only when already dismissed', async () => {
    document.body.innerHTML = '<div id="pmf-push-banner"></div>';
    localStorage.setItem('pmf-push-banner-dismissed', 'dismissed');
    await handlePushNotifications();
    expect(getVapidPublicKey).not.toHaveBeenCalled();
  });

  it('should return early for banner-only when notification permission is granted', async () => {
    document.body.innerHTML = '<div id="pmf-push-banner"></div>';
    Object.defineProperty(window, 'Notification', {
      value: { permission: 'granted' },
      writable: true,
      configurable: true,
    });
    await handlePushNotifications();
    expect(getVapidPublicKey).not.toHaveBeenCalled();
  });

  it('should return early for banner-only when notification permission is denied', async () => {
    document.body.innerHTML = '<div id="pmf-push-banner"></div>';
    Object.defineProperty(window, 'Notification', {
      value: { permission: 'denied' },
      writable: true,
      configurable: true,
    });
    await handlePushNotifications();
    expect(getVapidPublicKey).not.toHaveBeenCalled();
  });

  it('should hide banner when context is not secure', async () => {
    document.body.innerHTML = '<div id="pmf-push-banner"></div>';
    Object.defineProperty(window, 'isSecureContext', { value: false, writable: true, configurable: true });
    await handlePushNotifications();
    expect(document.getElementById('pmf-push-banner')?.classList.contains('d-none')).toBe(true);
  });

  it('should disable button when PushManager is not supported', async () => {
    document.body.innerHTML = `
      <button id="pmf-push-toggle" data-label-not-supported="Not supported">Toggle</button>
      <div id="pmf-push-banner"></div>
    `;
    Object.defineProperty(window, 'PushManager', { value: undefined, writable: true, configurable: true });

    await handlePushNotifications();

    const button = document.getElementById('pmf-push-toggle') as HTMLButtonElement;
    expect(button.disabled).toBe(true);
    expect(button.textContent).toBe('Not supported');
    expect(document.getElementById('pmf-push-banner')?.classList.contains('d-none')).toBe(true);
  });

  it('should hide elements when VAPID is not enabled', async () => {
    document.body.innerHTML = `
      <div><div><button id="pmf-push-toggle">Toggle</button></div></div>
      <div id="pmf-push-banner"></div>
    `;
    setupServiceWorker();
    vi.mocked(getVapidPublicKey).mockResolvedValue({ enabled: false, vapidPublicKey: '' });

    await handlePushNotifications();

    expect(document.getElementById('pmf-push-banner')?.classList.contains('d-none')).toBe(true);
  });

  it('should show banner when not subscribed and permission is default', async () => {
    document.body.innerHTML = '<div id="pmf-push-banner" class="d-none"></div>';
    setupServiceWorker(null);
    vi.mocked(getVapidPublicKey).mockResolvedValue({ enabled: true, vapidPublicKey: 'test-key' });

    await handlePushNotifications();

    expect(document.getElementById('pmf-push-banner')?.classList.contains('d-none')).toBe(false);
  });

  it('should not show banner when already subscribed', async () => {
    document.body.innerHTML = '<div id="pmf-push-banner" class="d-none"></div>';

    const existingSub = {
      endpoint: 'https://push.example.com/existing',
      getKey: vi.fn().mockReturnValue(new ArrayBuffer(8)),
      unsubscribe: vi.fn(),
    } as unknown as PushSubscription;

    setupServiceWorker(existingSub);
    vi.mocked(getVapidPublicKey).mockResolvedValue({ enabled: true, vapidPublicKey: 'test-key' });

    await handlePushNotifications();

    expect(document.getElementById('pmf-push-banner')?.classList.contains('d-none')).toBe(true);
  });

  it('should dismiss banner on dismiss button click', async () => {
    document.body.innerHTML = `
      <div id="pmf-push-banner" class="d-none">
        <button id="pmf-push-banner-enable">Enable</button>
        <button id="pmf-push-banner-dismiss">Dismiss</button>
      </div>
    `;
    setupServiceWorker(null);
    vi.mocked(getVapidPublicKey).mockResolvedValue({ enabled: true, vapidPublicKey: 'test-key' });

    await handlePushNotifications();

    document.getElementById('pmf-push-banner-dismiss')?.click();

    expect(document.getElementById('pmf-push-banner')?.classList.contains('d-none')).toBe(true);
    expect(localStorage.getItem('pmf-push-banner-dismissed')).toBe('dismissed');
  });

  it('should initialize UCP button as subscribed when subscription exists', async () => {
    document.body.innerHTML = `
      <button id="pmf-push-toggle" data-label-enable="Enable" data-label-disable="Disable" disabled>Loading</button>
    `;

    const existingSub = {
      endpoint: 'https://push.example.com/existing',
      getKey: vi.fn().mockReturnValue(new ArrayBuffer(8)),
      unsubscribe: vi.fn().mockResolvedValue(true),
    } as unknown as PushSubscription;

    setupServiceWorker(existingSub);
    vi.mocked(getVapidPublicKey).mockResolvedValue({ enabled: true, vapidPublicKey: 'test-key' });

    await handlePushNotifications();

    const button = document.getElementById('pmf-push-toggle') as HTMLButtonElement;
    expect(button.disabled).toBe(false);
    expect(button.textContent).toBe('Disable');
    expect(button.classList.contains('btn-outline-secondary')).toBe(true);
  });

  it('should initialize UCP button as not subscribed when no subscription', async () => {
    document.body.innerHTML = `
      <button id="pmf-push-toggle" data-label-enable="Enable" data-label-disable="Disable" disabled>Loading</button>
    `;
    setupServiceWorker(null);
    vi.mocked(getVapidPublicKey).mockResolvedValue({ enabled: true, vapidPublicKey: 'test-key' });

    await handlePushNotifications();

    const button = document.getElementById('pmf-push-toggle') as HTMLButtonElement;
    expect(button.disabled).toBe(false);
    expect(button.textContent).toBe('Enable');
    expect(button.classList.contains('btn-primary')).toBe(true);
  });

  it('should unsubscribe when toggle clicked while subscribed', async () => {
    document.body.innerHTML = `
      <button id="pmf-push-toggle" data-label-enable="Enable" data-label-disable="Disable" data-msg-disabled="Disabled!" disabled></button>
      <div id="pmf-push-toast-container"></div>
    `;

    const mockUnsubscribe = vi.fn().mockResolvedValue(true);
    const existingSub = {
      endpoint: 'https://push.example.com/existing',
      getKey: vi.fn().mockReturnValue(new ArrayBuffer(8)),
      unsubscribe: mockUnsubscribe,
    } as unknown as PushSubscription;

    setupServiceWorker(existingSub);
    vi.mocked(getVapidPublicKey).mockResolvedValue({ enabled: true, vapidPublicKey: 'test-key' });
    vi.mocked(unsubscribePush).mockResolvedValue({ success: true });

    await handlePushNotifications();

    const button = document.getElementById('pmf-push-toggle') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(unsubscribePush).toHaveBeenCalledWith('https://push.example.com/existing');
    });

    expect(mockUnsubscribe).toHaveBeenCalled();
    expect(button.textContent).toBe('Enable');
    expect(button.disabled).toBe(false);
  });

  it('should subscribe when toggle clicked while not subscribed', async () => {
    document.body.innerHTML = `
      <button id="pmf-push-toggle" data-label-enable="Enable" data-label-disable="Disable" data-msg-enabled="Enabled!" disabled></button>
      <div id="pmf-push-toast-container"></div>
    `;
    setupServiceWorker(null);
    vi.mocked(getVapidPublicKey).mockResolvedValue({ enabled: true, vapidPublicKey: 'test-key' });
    vi.mocked(subscribePush).mockResolvedValue({ success: true });

    await handlePushNotifications();

    const button = document.getElementById('pmf-push-toggle') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(subscribePush).toHaveBeenCalled();
    });

    expect(button.textContent).toBe('Disable');
    expect(button.disabled).toBe(false);
  });

  it('should handle setup errors gracefully', async () => {
    document.body.innerHTML = `
      <button id="pmf-push-toggle">Toggle</button>
      <div id="pmf-push-banner"></div>
    `;
    setupServiceWorker();
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    vi.mocked(getVapidPublicKey).mockRejectedValue(new Error('Server error'));

    await handlePushNotifications();

    expect(consoleSpy).toHaveBeenCalled();
    expect((document.getElementById('pmf-push-toggle') as HTMLButtonElement).disabled).toBe(true);
    expect(document.getElementById('pmf-push-banner')?.classList.contains('d-none')).toBe(true);

    consoleSpy.mockRestore();
  });
});
