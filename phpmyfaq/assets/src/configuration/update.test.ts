import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { handleConfigBackup, handleUpdateInformation } from './update';

const setLocation = (href: string, pathname: string): void => {
  Object.defineProperty(window, 'location', {
    value: { href, pathname, replace: vi.fn() },
    writable: true,
  });
};

const okResponse = (): Response =>
  ({
    ok: true,
    headers: new Headers({ 'content-type': 'application/json' }),
    json: async () => ({ message: 'Installation check successful' }),
  }) as unknown as Response;

const getSentHeaders = (fetchMock: ReturnType<typeof vi.fn>): Record<string, string> =>
  fetchMock.mock.calls[0][1].headers as Record<string, string>;

describe('update wizard authorization', () => {
  let fetchMock: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    fetchMock = vi.fn().mockResolvedValue(okResponse());
    vi.stubGlobal('fetch', fetchMock);
    window.sessionStorage.clear();
    setLocation('https://faq.example.org/update', '/update');
  });

  afterEach(() => {
    document.body.innerHTML = '';
    window.sessionStorage.clear();
    vi.unstubAllGlobals();
  });

  test('sends no token header when the update runs with an administrator session', async () => {
    document.body.innerHTML = `
      <input id="phpmyfaq-update-installed-version" type="hidden" value="4.1.6">
      <button id="phpmyfaq-update-next-step-button" disabled></button>
      <div id="phpmyfaq-update-check-success" class="d-none"></div>
      <div id="phpmyfaq-update-check-alert" class="d-none"><div id="phpmyfaq-update-check-result"></div></div>
    `;

    await handleUpdateInformation();

    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(getSentHeaders(fetchMock)['x-pmf-update-token']).toBeUndefined();
  });

  test('waits for the token instead of checking on page load', async () => {
    document.body.innerHTML = `
      <input id="phpmyfaq-update-installed-version" type="hidden" value="4.1.6">
      <input id="phpmyfaq-update-token" type="text" value="">
      <button id="phpmyfaq-update-check-button"></button>
      <button id="phpmyfaq-update-next-step-button" disabled></button>
      <div id="phpmyfaq-update-check-success" class="d-none"></div>
      <div id="phpmyfaq-update-check-alert" class="d-none"><div id="phpmyfaq-update-check-result"></div></div>
    `;

    await handleUpdateInformation();

    expect(fetchMock).not.toHaveBeenCalled();
  });

  test('sends the entered token and remembers it for the next steps', async () => {
    document.body.innerHTML = `
      <input id="phpmyfaq-update-installed-version" type="hidden" value="4.1.6">
      <input id="phpmyfaq-update-token" type="text" value=" 0123456789abcdef0123456789abcdef ">
      <button id="phpmyfaq-update-check-button"></button>
      <button id="phpmyfaq-update-next-step-button" disabled></button>
      <div id="phpmyfaq-update-check-success" class="d-none"></div>
      <div id="phpmyfaq-update-check-alert" class="d-none"><div id="phpmyfaq-update-check-result"></div></div>
    `;

    await handleUpdateInformation();
    (document.getElementById('phpmyfaq-update-check-button') as HTMLButtonElement).click();
    await vi.waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1));

    expect(getSentHeaders(fetchMock)['x-pmf-update-token']).toBe('0123456789abcdef0123456789abcdef');
    expect(window.sessionStorage.getItem('phpmyfaq-update-token')).toBe('0123456789abcdef0123456789abcdef');

    const nextStepButton = document.getElementById('phpmyfaq-update-next-step-button') as HTMLButtonElement;
    expect(nextStepButton.disabled).toBe(false);
  });

  test('reuses the remembered token on the following steps', async () => {
    window.sessionStorage.setItem('phpmyfaq-update-token', '0123456789abcdef0123456789abcdef');
    setLocation('https://faq.example.org/update?step=2', '/update');
    document.body.innerHTML = `
      <input id="phpmyfaq-update-installed-version" type="hidden" value="4.1.6">
    `;

    await handleConfigBackup();

    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(getSentHeaders(fetchMock)['x-pmf-update-token']).toBe('0123456789abcdef0123456789abcdef');
  });
});
