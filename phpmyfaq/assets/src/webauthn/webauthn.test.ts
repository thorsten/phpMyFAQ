import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('./register', () => ({
  webauthnRegister: vi.fn(),
}));

vi.mock('./authenticate', () => ({
  webauthnAuthenticate: vi.fn(),
}));

import { webauthnAuthenticate } from './authenticate';
import { webauthnRegister } from './register';
import { handleWebAuthn } from './webauthn';

describe('handleWebAuthn', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    global.fetch = vi.fn();
  });

  it('does nothing when neither form exists', () => {
    handleWebAuthn();

    expect(fetch).not.toHaveBeenCalled();
    expect(webauthnRegister).not.toHaveBeenCalled();
    expect(webauthnAuthenticate).not.toHaveBeenCalled();
  });

  it('handles successful registration', async () => {
    document.body.innerHTML = `
      <form id="pmf-webauthn-form"></form>
      <input id="webauthn" value="thorsten" />
      <input id="pmf-csrf-token-webauthn" value="csrf-123" />
      <div id="pmf-webauthn-error">old error</div>
      <div id="pmf-webauthn-success" class="d-none"></div>
    `;

    vi.mocked(fetch)
      .mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValue({ challenge: { publicKey: {} } }),
      } as unknown as Response)
      .mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValue({ success: 'ok', message: 'Registration succeeded' }),
      } as unknown as Response);

    vi.mocked(webauthnRegister).mockImplementation(async (_challenge, callback) => {
      await callback(true, 'signed-registration');
    });

    handleWebAuthn();

    const form = document.getElementById('pmf-webauthn-form') as HTMLFormElement;
    form.dispatchEvent(new Event('submit'));

    await vi.waitFor(() => {
      expect(fetch).toHaveBeenCalledWith('./api/webauthn/prepare', expect.any(Object));
    });

    await vi.waitFor(() => {
      expect(webauthnRegister).toHaveBeenCalled();
      expect(fetch).toHaveBeenCalledWith('./api/webauthn/register', expect.any(Object));
    });

    const registerCall = vi.mocked(fetch).mock.calls.find(([url]) => url === './api/webauthn/register');
    expect(JSON.parse((registerCall?.[1]?.body as string) ?? '{}')).toEqual({
      register: 'signed-registration',
      'pmf-csrf-token': 'csrf-123',
    });

    const successMessage = document.getElementById('pmf-webauthn-success') as HTMLElement;
    const errorMessage = document.getElementById('pmf-webauthn-error') as HTMLElement;

    expect(successMessage.classList.contains('d-none')).toBe(false);
    expect(successMessage.textContent).toBe('Registration succeeded');
    expect(errorMessage.classList.contains('d-none')).toBe(true);
  });

  it('sends the current password along when the form asks for a password step-up', async () => {
    document.body.innerHTML = `
      <form id="pmf-webauthn-form" data-pmf-step-up="password"></form>
      <input id="webauthn" value="thorsten" />
      <input id="pmf-webauthn-current-password" value="s3cret" />
      <input id="pmf-csrf-token-webauthn" value="csrf-123" />
      <div id="pmf-webauthn-error"></div>
      <div id="pmf-webauthn-success" class="d-none"></div>
    `;

    vi.mocked(fetch)
      .mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValue({ challenge: { publicKey: {} } }),
      } as unknown as Response)
      .mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValue({ success: 'ok', message: 'Registration succeeded' }),
      } as unknown as Response);

    vi.mocked(webauthnRegister).mockImplementation(async (_challenge, callback) => {
      await callback(true, 'signed-registration');
    });

    handleWebAuthn();

    (document.getElementById('pmf-webauthn-form') as HTMLFormElement).dispatchEvent(new Event('submit'));

    await vi.waitFor(() => {
      expect(fetch).toHaveBeenCalledWith('./api/webauthn/register', expect.any(Object));
    });

    const prepareCall = vi.mocked(fetch).mock.calls.find(([url]) => url === './api/webauthn/prepare');
    expect(JSON.parse((prepareCall?.[1]?.body as string) ?? '{}')).toEqual({
      username: 'thorsten',
      'pmf-csrf-token': 'csrf-123',
      'current-password': 's3cret',
    });
    expect(webauthnAuthenticate).not.toHaveBeenCalled();
  });

  it('confirms with an existing passkey first when the form asks for a passkey step-up', async () => {
    document.body.innerHTML = `
      <form id="pmf-webauthn-form" data-pmf-step-up="passkey"></form>
      <input id="webauthn" value="thorsten" />
      <input id="pmf-csrf-token-webauthn" value="csrf-123" />
      <div id="pmf-webauthn-error"></div>
      <div id="pmf-webauthn-success" class="d-none"></div>
    `;

    vi.mocked(fetch)
      .mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValue({ challenge: 'login-challenge' }),
      } as unknown as Response)
      .mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValue({ challenge: { publicKey: {} } }),
      } as unknown as Response)
      .mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValue({ success: 'ok', message: 'Registration succeeded' }),
      } as unknown as Response);

    vi.mocked(webauthnAuthenticate).mockImplementation(async (_key, callback) => {
      callback(true, { type: 'public-key', rawId: [1] } as never);
    });
    vi.mocked(webauthnRegister).mockImplementation(async (_challenge, callback) => {
      await callback(true, 'signed-registration');
    });

    handleWebAuthn();

    (document.getElementById('pmf-webauthn-form') as HTMLFormElement).dispatchEvent(new Event('submit'));

    await vi.waitFor(() => {
      expect(fetch).toHaveBeenCalledWith('./api/webauthn/register', expect.any(Object));
    });

    const urls = vi.mocked(fetch).mock.calls.map(([url]) => url);
    expect(urls).toEqual(['./api/webauthn/prepare-login', './api/webauthn/prepare', './api/webauthn/register']);
    expect(webauthnAuthenticate).toHaveBeenCalledWith({ challenge: 'login-challenge' }, expect.any(Function));

    const prepareCall = vi.mocked(fetch).mock.calls.find(([url]) => url === './api/webauthn/prepare');
    expect(JSON.parse((prepareCall?.[1]?.body as string) ?? '{}')).toEqual({
      username: 'thorsten',
      'pmf-csrf-token': 'csrf-123',
      assertion: { type: 'public-key', rawId: [1] },
    });
  });

  it('shows the error of the existing passkey when the passkey step-up fails', async () => {
    document.body.innerHTML = `
      <form id="pmf-webauthn-form" data-pmf-step-up="passkey"></form>
      <input id="webauthn" value="thorsten" />
      <input id="pmf-csrf-token-webauthn" value="csrf-123" />
      <div id="pmf-webauthn-error"></div>
      <div id="pmf-webauthn-success" class="d-none"></div>
    `;

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: vi.fn().mockResolvedValue({ challenge: 'login-challenge' }),
    } as unknown as Response);

    vi.mocked(webauthnAuthenticate).mockImplementation(async (_key, callback) => {
      callback(false, 'Authentication aborted');
    });

    handleWebAuthn();

    (document.getElementById('pmf-webauthn-form') as HTMLFormElement).dispatchEvent(new Event('submit'));

    const errorMessage = document.getElementById('pmf-webauthn-error') as HTMLElement;
    await vi.waitFor(() => {
      expect(errorMessage.textContent).toBe('Authentication aborted');
    });

    expect(fetch).toHaveBeenCalledTimes(1);
    expect(webauthnRegister).not.toHaveBeenCalled();
    expect(errorMessage.classList.contains('d-none')).toBe(false);
  });

  it('shows the server error when the step-up is rejected', async () => {
    document.body.innerHTML = `
      <form id="pmf-webauthn-form" data-pmf-step-up="password"></form>
      <input id="webauthn" value="thorsten" />
      <input id="pmf-webauthn-current-password" value="wrong" />
      <input id="pmf-csrf-token-webauthn" value="csrf-123" />
      <div id="pmf-webauthn-error"></div>
      <div id="pmf-webauthn-success" class="d-none"></div>
    `;

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: false,
      json: vi.fn().mockResolvedValue({ error: 'The old password must be entered correctly.' }),
    } as unknown as Response);

    handleWebAuthn();

    (document.getElementById('pmf-webauthn-form') as HTMLFormElement).dispatchEvent(new Event('submit'));

    const errorMessage = document.getElementById('pmf-webauthn-error') as HTMLElement;
    await vi.waitFor(() => {
      expect(errorMessage.textContent).toBe('The old password must be entered correctly.');
    });

    expect(webauthnRegister).not.toHaveBeenCalled();
    expect(errorMessage.classList.contains('d-none')).toBe(false);
  });

  it('shows the callback error when registration fails in WebAuthn', async () => {
    document.body.innerHTML = `
      <form id="pmf-webauthn-form"></form>
      <input id="webauthn" value="thorsten" />
      <input id="pmf-csrf-token-webauthn" value="csrf-123" />
      <div id="pmf-webauthn-error"></div>
      <div id="pmf-webauthn-success" class="d-none"></div>
    `;

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: vi.fn().mockResolvedValue({ challenge: { publicKey: {} } }),
    } as unknown as Response);

    vi.mocked(webauthnRegister).mockImplementation(async (_challenge, callback) => {
      await callback(false, 'Registration aborted');
    });

    handleWebAuthn();

    const form = document.getElementById('pmf-webauthn-form') as HTMLFormElement;
    form.dispatchEvent(new Event('submit'));

    await vi.waitFor(() => {
      expect(webauthnRegister).toHaveBeenCalled();
    });

    const errorMessage = document.getElementById('pmf-webauthn-error') as HTMLElement;
    expect(errorMessage.textContent).toBe('Registration aborted');
    expect(errorMessage.classList.contains('d-none')).toBe(false);
  });

  it('shows an error when registration prepare fails', async () => {
    document.body.innerHTML = `
      <form id="pmf-webauthn-form"></form>
      <input id="webauthn" value="thorsten" />
      <input id="pmf-csrf-token-webauthn" value="csrf-123" />
      <div id="pmf-webauthn-error"></div>
      <div id="pmf-webauthn-success" class="d-none"></div>
    `;

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: false,
      json: vi.fn(),
    } as unknown as Response);

    handleWebAuthn();

    const form = document.getElementById('pmf-webauthn-form') as HTMLFormElement;
    form.dispatchEvent(new Event('submit'));

    await vi.waitFor(() => {
      expect(fetch).toHaveBeenCalled();
    });

    const errorMessage = document.getElementById('pmf-webauthn-error') as HTMLElement;
    expect(errorMessage.textContent).toBe("Couldn't initiate registration.");
    expect(errorMessage.classList.contains('d-none')).toBe(false);
  });

  it('shows the callback error when login authentication fails', async () => {
    document.body.innerHTML = `
      <form id="pmf-webauthn-login-form"></form>
      <input name="faqusername" value="thorsten" />
      <div id="pmf-webauthn-error"></div>
      <div id="pmf-webauthn-success" class="d-none"></div>
    `;

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: vi.fn().mockResolvedValue({ challenge: [1, 2, 3], allowCredentials: [] }),
    } as unknown as Response);

    vi.mocked(webauthnAuthenticate).mockImplementation(async (_payload, callback) => {
      await callback(false, 'Authentication aborted');
    });

    handleWebAuthn();

    const form = document.getElementById('pmf-webauthn-login-form') as HTMLFormElement;
    form.dispatchEvent(new Event('submit'));

    await vi.waitFor(() => {
      expect(webauthnAuthenticate).toHaveBeenCalled();
    });

    const errorMessage = document.getElementById('pmf-webauthn-error') as HTMLElement;
    expect(errorMessage.textContent).toBe('Authentication aborted');
    expect(errorMessage.classList.contains('d-none')).toBe(false);
  });

  it('shows the API error when prepare-login fails', async () => {
    document.body.innerHTML = `
      <form id="pmf-webauthn-login-form"></form>
      <input name="faqusername" value="thorsten" />
      <div id="pmf-webauthn-error"></div>
      <div id="pmf-webauthn-success" class="d-none"></div>
    `;

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: false,
      json: vi.fn().mockResolvedValue({ error: 'Prepare login failed' }),
    } as unknown as Response);

    handleWebAuthn();

    const form = document.getElementById('pmf-webauthn-login-form') as HTMLFormElement;
    form.dispatchEvent(new Event('submit'));

    await vi.waitFor(() => {
      expect(fetch).toHaveBeenCalled();
    });

    await vi.waitFor(() => {
      const errorMessage = document.getElementById('pmf-webauthn-error') as HTMLElement;
      expect(errorMessage.textContent).toBe('Prepare login failed');
      expect(errorMessage.classList.contains('d-none')).toBe(false);
    });
  });

  it('shows the API error when login submission fails', async () => {
    document.body.innerHTML = `
      <form id="pmf-webauthn-login-form"></form>
      <input name="faqusername" value="thorsten" />
      <div id="pmf-webauthn-error"></div>
      <div id="pmf-webauthn-success" class="d-none"></div>
    `;

    vi.mocked(fetch)
      .mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValue({ challenge: [1, 2, 3], allowCredentials: [] }),
      } as unknown as Response)
      .mockResolvedValueOnce({
        ok: false,
        json: vi.fn().mockResolvedValue({ error: 'Login failed' }),
      } as unknown as Response);

    vi.mocked(webauthnAuthenticate).mockImplementation(async (_payload, callback) => {
      await callback(true, {
        type: 'public-key',
        originalChallenge: [1, 2, 3],
        rawId: [1],
        response: {
          authenticatorData: [2],
          clientData: {
            type: 'webauthn.get',
            challenge: 'challenge',
            origin: 'https://localhost',
          },
          clientDataJSONarray: [3],
          signature: [4],
        },
      });
    });

    handleWebAuthn();

    const form = document.getElementById('pmf-webauthn-login-form') as HTMLFormElement;
    form.dispatchEvent(new Event('submit'));

    await vi.waitFor(() => {
      expect(fetch).toHaveBeenCalledWith('./api/webauthn/login', expect.any(Object));
    });

    const errorMessage = document.getElementById('pmf-webauthn-error') as HTMLElement;
    expect(errorMessage.textContent).toBe('Login failed');
    expect(errorMessage.classList.contains('d-none')).toBe(false);
  });
});
