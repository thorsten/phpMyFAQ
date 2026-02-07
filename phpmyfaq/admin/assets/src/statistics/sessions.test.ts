import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleSessionsFilter, handleSessions, handleClearVisits, handleDeleteSessions } from './sessions';
import { clearVisits, deleteSessions } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

vi.mock('../api', () => ({
  clearVisits: vi.fn(),
  deleteSessions: vi.fn(),
}));

vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    pushNotification: vi.fn(),
    pushErrorNotification: vi.fn(),
  };
});

const mockFetch = vi.fn();
global.fetch = mockFetch;

URL.createObjectURL = vi.fn(() => 'blob:http://localhost/fake-url');
URL.revokeObjectURL = vi.fn();

const flushPromises = async (): Promise<void> => {
  await new Promise((resolve) => setTimeout(resolve, 50));
};

describe('handleSessionsFilter', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  it('should do nothing when button is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleSessionsFilter();

    expect(document.body.innerHTML).toBe('<div></div>');
  });

  it('should set form action and submit on click', async () => {
    const submitSpy = vi.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(() => {});

    const timestamp = Math.floor(new Date('2024-06-15T12:00:00Z').getTime() / 1000);
    document.body.innerHTML = `
      <form id="pmf-admin-form-session" action="">
        <input id="day" type="hidden" value="${timestamp}" />
      </form>
      <button id="pmf-admin-session-day">Filter</button>
    `;

    handleSessionsFilter();

    const button = document.getElementById('pmf-admin-session-day') as HTMLButtonElement;
    button.click();

    await flushPromises();

    const form = document.getElementById('pmf-admin-form-session') as HTMLFormElement;
    expect(form.action).toContain('/statistics/sessions/2024-06-15');
    expect(submitSpy).toHaveBeenCalled();

    submitSpy.mockRestore();
  });
});

describe('handleSessions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    vi.restoreAllMocks();
    vi.spyOn(console, 'error').mockImplementation(() => {});
  });

  it('should do nothing when inputs are missing', () => {
    document.body.innerHTML = '<div></div>';

    handleSessions();

    expect(document.body.innerHTML).toBe('<div></div>');
  });

  it('should enable export button when both inputs have values', () => {
    document.body.innerHTML = `
      <input id="firstHour" type="text" value="" />
      <input id="lastHour" type="text" value="" />
      <button id="exportSessions">Export</button>
      <input id="csrf" type="hidden" value="token123" />
    `;

    handleSessions();

    const firstHour = document.getElementById('firstHour') as HTMLInputElement;
    const lastHour = document.getElementById('lastHour') as HTMLInputElement;
    const exportButton = document.getElementById('exportSessions') as HTMLButtonElement;

    expect(exportButton.disabled).toBe(true);

    firstHour.value = '10';
    firstHour.dispatchEvent(new Event('change'));
    expect(exportButton.disabled).toBe(true);

    lastHour.value = '20';
    lastHour.dispatchEvent(new Event('change'));
    expect(exportButton.disabled).toBe(false);
  });

  it('should disable export button when an input is cleared', () => {
    document.body.innerHTML = `
      <input id="firstHour" type="text" value="10" />
      <input id="lastHour" type="text" value="20" />
      <button id="exportSessions">Export</button>
      <input id="csrf" type="hidden" value="token123" />
    `;

    handleSessions();

    const firstHour = document.getElementById('firstHour') as HTMLInputElement;
    const lastHour = document.getElementById('lastHour') as HTMLInputElement;
    const exportButton = document.getElementById('exportSessions') as HTMLButtonElement;

    // Initially disabled by handleSessions
    expect(exportButton.disabled).toBe(true);

    // Trigger change to enable
    lastHour.dispatchEvent(new Event('change'));
    expect(exportButton.disabled).toBe(false);

    // Clear firstHour and trigger change on lastHour
    firstHour.value = '';
    lastHour.dispatchEvent(new Event('change'));
    expect(exportButton.disabled).toBe(true);
  });

  it('should create blob download on successful export', async () => {
    document.body.innerHTML = `
      <input id="firstHour" type="text" value="10" />
      <input id="lastHour" type="text" value="20" />
      <button id="exportSessions">Export</button>
      <input id="csrf" type="hidden" value="token123" />
    `;

    const fakeBlob = new Blob(['csv,data'], { type: 'text/csv' });
    mockFetch.mockResolvedValue({
      ok: true,
      blob: () => Promise.resolve(fakeBlob),
    });

    handleSessions();

    const exportButton = document.getElementById('exportSessions') as HTMLButtonElement;
    const lastHour = document.getElementById('lastHour') as HTMLInputElement;

    // Enable the export button by triggering the change event
    lastHour.dispatchEvent(new Event('change'));

    exportButton.click();

    await flushPromises();

    expect(mockFetch).toHaveBeenCalledWith('./api/session/export', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: 'token123',
        firstHour: '10',
        lastHour: '20',
      }),
    });

    expect(URL.createObjectURL).toHaveBeenCalledWith(fakeBlob);
    expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:http://localhost/fake-url');
  });

  it('should show error notification on non-ok response', async () => {
    document.body.innerHTML = `
      <input id="firstHour" type="text" value="10" />
      <input id="lastHour" type="text" value="20" />
      <button id="exportSessions">Export</button>
      <input id="csrf" type="hidden" value="token123" />
    `;

    mockFetch.mockResolvedValue({
      ok: false,
      json: () => Promise.resolve({ error: 'Export failed' }),
    });

    handleSessions();

    const exportButton = document.getElementById('exportSessions') as HTMLButtonElement;
    const lastHour = document.getElementById('lastHour') as HTMLInputElement;

    // Enable the export button by triggering change event
    lastHour.dispatchEvent(new Event('change'));

    exportButton.click();

    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith('Export failed');
  });

  it('should log error and show notification on fetch error', async () => {
    document.body.innerHTML = `
      <input id="firstHour" type="text" value="10" />
      <input id="lastHour" type="text" value="20" />
      <button id="exportSessions">Export</button>
      <input id="csrf" type="hidden" value="token123" />
    `;

    mockFetch.mockRejectedValue(new Error('Network failure'));

    handleSessions();

    const exportButton = document.getElementById('exportSessions') as HTMLButtonElement;
    const lastHour = document.getElementById('lastHour') as HTMLInputElement;

    // Enable the export button by triggering change event
    lastHour.dispatchEvent(new Event('change'));

    exportButton.click();

    await flushPromises();

    expect(console.error).toHaveBeenCalledWith('Network failure');
    expect(pushErrorNotification).toHaveBeenCalledWith('Network failure');
  });
});

describe('handleClearVisits', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  it('should do nothing when button is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleClearVisits();

    expect(clearVisits).not.toHaveBeenCalled();
  });

  it('should show error when csrf is missing', async () => {
    document.body.innerHTML = `
      <button id="pmf-admin-clear-visits">Clear Visits</button>
    `;

    handleClearVisits();

    const button = document.getElementById('pmf-admin-clear-visits') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith('Missing CSRF token');
    expect(clearVisits).not.toHaveBeenCalled();
  });

  it('should call clearVisits and show notification on success', async () => {
    document.body.innerHTML = `
      <button id="pmf-admin-clear-visits" data-pmf-csrf="csrf-token-123">Clear Visits</button>
    `;

    (clearVisits as Mock).mockResolvedValue({ success: 'Visits cleared successfully' });

    handleClearVisits();

    const button = document.getElementById('pmf-admin-clear-visits') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(clearVisits).toHaveBeenCalledWith('csrf-token-123');
    expect(pushNotification).toHaveBeenCalledWith('Visits cleared successfully');
  });

  it('should show error on error response', async () => {
    document.body.innerHTML = `
      <button id="pmf-admin-clear-visits" data-pmf-csrf="csrf-token-123">Clear Visits</button>
    `;

    (clearVisits as Mock).mockResolvedValue({ error: 'Clear visits failed' });

    handleClearVisits();

    const button = document.getElementById('pmf-admin-clear-visits') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(clearVisits).toHaveBeenCalledWith('csrf-token-123');
    expect(pushErrorNotification).toHaveBeenCalledWith('Clear visits failed');
  });

  it('should show error when no response received', async () => {
    document.body.innerHTML = `
      <button id="pmf-admin-clear-visits" data-pmf-csrf="csrf-token-123">Clear Visits</button>
    `;

    (clearVisits as Mock).mockResolvedValue(undefined);

    handleClearVisits();

    const button = document.getElementById('pmf-admin-clear-visits') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(clearVisits).toHaveBeenCalledWith('csrf-token-123');
    expect(pushErrorNotification).toHaveBeenCalledWith('No response received');
  });
});

describe('handleDeleteSessions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  it('should do nothing when button is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleDeleteSessions();

    expect(deleteSessions).not.toHaveBeenCalled();
  });

  it('should call deleteSessions with correct params on success', async () => {
    document.body.innerHTML = `
      <input id="pmf-csrf-token" type="hidden" value="csrf-token-456" />
      <input id="month" type="text" value="2024-06" />
      <button id="pmf-admin-delete-sessions">Delete Sessions</button>
    `;

    (deleteSessions as Mock).mockResolvedValue({ success: 'Sessions deleted successfully' });

    handleDeleteSessions();

    const button = document.getElementById('pmf-admin-delete-sessions') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(deleteSessions).toHaveBeenCalledWith('csrf-token-456', '2024-06');
    expect(pushNotification).toHaveBeenCalledWith('Sessions deleted successfully');
  });

  it('should show error on error response', async () => {
    document.body.innerHTML = `
      <input id="pmf-csrf-token" type="hidden" value="csrf-token-456" />
      <input id="month" type="text" value="2024-06" />
      <button id="pmf-admin-delete-sessions">Delete Sessions</button>
    `;

    (deleteSessions as Mock).mockResolvedValue({ error: 'Delete sessions failed' });

    handleDeleteSessions();

    const button = document.getElementById('pmf-admin-delete-sessions') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(deleteSessions).toHaveBeenCalledWith('csrf-token-456', '2024-06');
    expect(pushErrorNotification).toHaveBeenCalledWith('Delete sessions failed');
  });

  it('should show error when no response received', async () => {
    document.body.innerHTML = `
      <input id="pmf-csrf-token" type="hidden" value="csrf-token-456" />
      <input id="month" type="text" value="2024-06" />
      <button id="pmf-admin-delete-sessions">Delete Sessions</button>
    `;

    (deleteSessions as Mock).mockResolvedValue(undefined);

    handleDeleteSessions();

    const button = document.getElementById('pmf-admin-delete-sessions') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(deleteSessions).toHaveBeenCalledWith('csrf-token-456', '2024-06');
    expect(pushErrorNotification).toHaveBeenCalledWith('No response received');
  });
});
