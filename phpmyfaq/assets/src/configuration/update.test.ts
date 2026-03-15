import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import {
  handleUpdateNextStepButton,
  handleUpdateInformation,
  handleConfigBackup,
  handleDatabaseUpdate,
} from './update';

describe('handleUpdateNextStepButton', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('should do nothing when button or input is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleUpdateNextStepButton();

    // No errors thrown
  });

  it('should navigate to step URL on button click', () => {
    document.body.innerHTML = `
      <input id="phpmyfaq-update-next-step" value="2" />
      <button id="phpmyfaq-update-next-step-button">Next</button>
    `;

    const replaceSpy = vi.fn();
    Object.defineProperty(window, 'location', {
      value: { replace: replaceSpy, href: 'http://localhost/update' },
      writable: true,
    });

    handleUpdateNextStepButton();

    const button = document.getElementById('phpmyfaq-update-next-step-button') as HTMLButtonElement;
    button.click();

    expect(replaceSpy).toHaveBeenCalledWith('?step=2');
  });

  it('should not navigate when step value is NaN', () => {
    document.body.innerHTML = `
      <input id="phpmyfaq-update-next-step" value="abc" />
      <button id="phpmyfaq-update-next-step-button">Next</button>
    `;

    const replaceSpy = vi.fn();
    Object.defineProperty(window, 'location', {
      value: { replace: replaceSpy, href: 'http://localhost/update' },
      writable: true,
    });

    handleUpdateNextStepButton();

    const button = document.getElementById('phpmyfaq-update-next-step-button') as HTMLButtonElement;
    button.click();

    expect(replaceSpy).not.toHaveBeenCalled();
  });

  it('should not navigate when step value is less than 1', () => {
    document.body.innerHTML = `
      <input id="phpmyfaq-update-next-step" value="0" />
      <button id="phpmyfaq-update-next-step-button">Next</button>
    `;

    const replaceSpy = vi.fn();
    Object.defineProperty(window, 'location', {
      value: { replace: replaceSpy, href: 'http://localhost/update' },
      writable: true,
    });

    handleUpdateNextStepButton();

    const button = document.getElementById('phpmyfaq-update-next-step-button') as HTMLButtonElement;
    button.click();

    expect(replaceSpy).not.toHaveBeenCalled();
  });
});

describe('handleUpdateInformation', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should do nothing when URL does not end with /update', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/admin' },
      writable: true,
    });

    global.fetch = vi.fn();

    await handleUpdateInformation();

    expect(fetch).not.toHaveBeenCalled();
  });

  it('should do nothing when installed version input is missing', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update' },
      writable: true,
    });

    document.body.innerHTML = '<div></div>';
    global.fetch = vi.fn();

    await handleUpdateInformation();

    expect(fetch).not.toHaveBeenCalled();
  });

  it('should show success alert and enable button on successful check', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update' },
      writable: true,
    });

    document.body.innerHTML = `
      <input id="phpmyfaq-update-installed-version" value="4.1.0" />
      <div id="phpmyfaq-update-check-success" class="d-none"></div>
      <button id="phpmyfaq-update-next-step-button" class="disabled" disabled>Next</button>
    `;

    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ success: true }),
    });

    await handleUpdateInformation();

    expect(fetch).toHaveBeenCalledWith('../api/setup/check', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: '4.1.0',
    });

    const alert = document.getElementById('phpmyfaq-update-check-success');
    expect(alert?.classList.contains('d-none')).toBe(false);

    const button = document.getElementById('phpmyfaq-update-next-step-button') as HTMLButtonElement;
    expect(button.classList.contains('disabled')).toBe(false);
    expect(button.disabled).toBe(false);
  });

  it('should show error alert on failed check response', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update' },
      writable: true,
    });

    document.body.innerHTML = `
      <input id="phpmyfaq-update-installed-version" value="4.1.0" />
      <div id="phpmyfaq-update-check-alert" class="d-none"></div>
      <div id="phpmyfaq-update-check-result"></div>
    `;

    global.fetch = vi.fn().mockResolvedValue({
      ok: false,
      json: () => Promise.resolve({ message: 'Version mismatch' }),
    });

    await handleUpdateInformation();

    const alert = document.getElementById('phpmyfaq-update-check-alert');
    expect(alert?.classList.contains('d-none')).toBe(false);

    const result = document.getElementById('phpmyfaq-update-check-result');
    expect(result?.innerText).toBe('Version mismatch');
  });

  it('should show default error message when response has no message', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update' },
      writable: true,
    });

    document.body.innerHTML = `
      <input id="phpmyfaq-update-installed-version" value="4.1.0" />
      <div id="phpmyfaq-update-check-alert" class="d-none"></div>
      <div id="phpmyfaq-update-check-result"></div>
    `;

    global.fetch = vi.fn().mockResolvedValue({
      ok: false,
      json: () => Promise.resolve({}),
    });

    await handleUpdateInformation();

    const result = document.getElementById('phpmyfaq-update-check-result');
    expect(result?.innerText).toBe('Update check failed');
  });

  it('should show server config error on SyntaxError', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update' },
      writable: true,
    });

    document.body.innerHTML = `
      <input id="phpmyfaq-update-installed-version" value="4.1.0" />
      <div id="phpmyfaq-update-check-alert" class="d-none"></div>
      <div id="phpmyfaq-update-check-result"></div>
    `;

    global.fetch = vi.fn().mockRejectedValue(new SyntaxError('Unexpected token'));

    await handleUpdateInformation();

    const result = document.getElementById('phpmyfaq-update-check-result');
    expect(result?.innerText).toContain('The requested resource was not found');
    expect(result?.innerText).toContain('RewriteBase');
  });

  it('should show generic error message on other errors', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update' },
      writable: true,
    });

    document.body.innerHTML = `
      <input id="phpmyfaq-update-installed-version" value="4.1.0" />
      <div id="phpmyfaq-update-check-alert" class="d-none"></div>
      <div id="phpmyfaq-update-check-result"></div>
    `;

    global.fetch = vi.fn().mockRejectedValue(new Error('Network failure'));

    await handleUpdateInformation();

    const result = document.getElementById('phpmyfaq-update-check-result');
    expect(result?.innerText).toBe('Network failure');
  });

  it('should work with URL ending in /update/', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update/' },
      writable: true,
    });

    document.body.innerHTML = `
      <input id="phpmyfaq-update-installed-version" value="4.1.0" />
      <div id="phpmyfaq-update-check-success" class="d-none"></div>
      <button id="phpmyfaq-update-next-step-button" class="disabled" disabled>Next</button>
    `;

    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ success: true }),
    });

    await handleUpdateInformation();

    expect(fetch).toHaveBeenCalled();
  });
});

describe('handleConfigBackup', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  it('should do nothing when URL does not match step 2', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update?step=1' },
      writable: true,
    });

    global.fetch = vi.fn();

    await handleConfigBackup();

    expect(fetch).not.toHaveBeenCalled();
  });

  it('should do nothing when installed version input is missing', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update?step=2' },
      writable: true,
    });

    document.body.innerHTML = '<div></div>';
    global.fetch = vi.fn();

    await handleConfigBackup();

    expect(fetch).not.toHaveBeenCalled();
  });

  it('should call backup API on step 2', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update?step=2' },
      writable: true,
    });

    document.body.innerHTML = '<input id="phpmyfaq-update-installed-version" value="4.1.0" />';

    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ success: true }),
    });

    await handleConfigBackup();

    expect(fetch).toHaveBeenCalledWith('../api/setup/backup', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: '4.1.0',
    });
  });

  it('should log error on failed backup response', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update?step=2' },
      writable: true,
    });

    document.body.innerHTML = '<input id="phpmyfaq-update-installed-version" value="4.1.0" />';

    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    global.fetch = vi.fn().mockResolvedValue({
      ok: false,
    });

    await handleConfigBackup();

    expect(consoleSpy).toHaveBeenCalledWith('Network response was not ok');
    consoleSpy.mockRestore();
  });

  it('should log error on network failure', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update?step=2' },
      writable: true,
    });

    document.body.innerHTML = '<input id="phpmyfaq-update-installed-version" value="4.1.0" />';

    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    global.fetch = vi.fn().mockRejectedValue(new Error('Network error'));

    await handleConfigBackup();

    expect(consoleSpy).toHaveBeenCalledWith('Backup creation failed:', expect.any(Error));
    consoleSpy.mockRestore();
  });
});

describe('handleDatabaseUpdate', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  it('should do nothing when URL does not match step 3', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update?step=1' },
      writable: true,
    });

    global.fetch = vi.fn();

    await handleDatabaseUpdate();

    expect(fetch).not.toHaveBeenCalled();
  });

  it('should do nothing when installed version input is missing', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update?step=3' },
      writable: true,
    });

    document.body.innerHTML = '<div></div>';
    global.fetch = vi.fn();

    await handleDatabaseUpdate();

    expect(fetch).not.toHaveBeenCalled();
  });

  it('should show success on successful database update', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update?step=3' },
      writable: true,
    });

    document.body.innerHTML = `
      <input id="phpmyfaq-update-installed-version" value="4.1.0" />
      <div id="result-update" class="progress-bar-animated" style="width: 0%"></div>
      <div id="phpmyfaq-update-database-success" class="d-none"></div>
    `;

    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ success: 'Database updated successfully' }),
    });

    await handleDatabaseUpdate();

    expect(fetch).toHaveBeenCalledWith('../api/setup/update-database', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: '4.1.0',
    });

    const progressBar = document.getElementById('result-update') as HTMLElement;
    expect(progressBar.style.width).toBe('100%');
    expect(progressBar.innerText).toBe('100%');
    expect(progressBar.classList.contains('progress-bar-animated')).toBe(false);

    const alert = document.getElementById('phpmyfaq-update-database-success') as HTMLElement;
    expect(alert.classList.contains('d-none')).toBe(false);
    expect(alert.innerText).toBe('Database updated successfully');
  });

  it('should show error on failed database update response', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update?step=3' },
      writable: true,
    });

    document.body.innerHTML = `
      <input id="phpmyfaq-update-installed-version" value="4.1.0" />
      <div id="result-update" class="progress-bar-animated" style="width: 0%"></div>
      <div id="phpmyfaq-update-database-error" class="d-none"></div>
      <div id="error-messages"></div>
    `;

    global.fetch = vi.fn().mockResolvedValue({
      ok: false,
      json: () => Promise.resolve({ error: '<p>Migration failed</p>' }),
    });

    await handleDatabaseUpdate();

    const progressBar = document.getElementById('result-update') as HTMLElement;
    expect(progressBar.style.width).toBe('100%');
    expect(progressBar.classList.contains('progress-bar-animated')).toBe(false);

    const alert = document.getElementById('phpmyfaq-update-database-error') as HTMLElement;
    expect(alert.classList.contains('d-none')).toBe(false);

    const errorMessages = document.getElementById('error-messages') as HTMLElement;
    expect(errorMessages.innerHTML).toBe('<p>Migration failed</p>');
  });

  it('should show error alert on network failure', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update?step=3' },
      writable: true,
    });

    document.body.innerHTML = `
      <input id="phpmyfaq-update-installed-version" value="4.1.0" />
      <div id="phpmyfaq-update-database-error" class="d-none"></div>
    `;

    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    global.fetch = vi.fn().mockRejectedValue(new Error('Connection refused'));

    await handleDatabaseUpdate();

    const alert = document.getElementById('phpmyfaq-update-database-error') as HTMLElement;
    expect(alert.classList.contains('d-none')).toBe(false);
    expect(alert.innerText).toBe('Error: Connection refused');

    consoleSpy.mockRestore();
  });

  it('should work with URL ending in /update/?step=3', async () => {
    Object.defineProperty(window, 'location', {
      value: { href: 'http://localhost/update/?step=3' },
      writable: true,
    });

    document.body.innerHTML = `
      <input id="phpmyfaq-update-installed-version" value="4.1.0" />
      <div id="result-update" class="progress-bar-animated"></div>
      <div id="phpmyfaq-update-database-success" class="d-none"></div>
    `;

    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ success: 'Done' }),
    });

    await handleDatabaseUpdate();

    expect(fetch).toHaveBeenCalled();
  });
});
