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

    const successMessage = document.getElementById('pmf-webauthn-success') as HTMLElement;
    const errorMessage = document.getElementById('pmf-webauthn-error') as HTMLElement;

    expect(successMessage.classList.contains('d-none')).toBe(false);
    expect(successMessage.textContent).toBe('Registration succeeded');
    expect(errorMessage.classList.contains('d-none')).toBe(true);
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
