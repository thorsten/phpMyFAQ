import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleExportAdminLog, handleVerifyAdminLog, handleDeleteAdminLog } from './admin-log';
import { deleteAdminLog } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

vi.mock('../api');
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

const mockCreateObjectURL = vi.fn().mockReturnValue('blob:http://localhost/fake-url');
const mockRevokeObjectURL = vi.fn();
window.URL.createObjectURL = mockCreateObjectURL;
window.URL.revokeObjectURL = mockRevokeObjectURL;

const flushPromises = async (): Promise<void> => {
  await new Promise((resolve) => setTimeout(resolve, 50));
};

describe('Admin Log Functions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('handleExportAdminLog', () => {
    it('should do nothing when button is missing', () => {
      document.body.innerHTML = '<div></div>';

      handleExportAdminLog();

      expect(mockFetch).not.toHaveBeenCalled();
      expect(pushErrorNotification).not.toHaveBeenCalled();
    });

    it('should show error when csrf is missing', async () => {
      document.body.innerHTML = `
        <button id="pmf-export-admin-log">Export</button>
      `;

      handleExportAdminLog();

      const button = document.getElementById('pmf-export-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(pushErrorNotification).toHaveBeenCalledWith('Missing CSRF token');
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('should download file via blob on success', async () => {
      document.body.innerHTML = `
        <button id="pmf-export-admin-log" data-pmf-csrf="test-csrf">Export</button>
      `;

      const mockBlob = new Blob(['csv,data'], { type: 'text/csv' });
      const mockHeaders = new Headers({
        'Content-Disposition': 'attachment; filename="admin-log-2024.csv"',
      });

      mockFetch.mockResolvedValue({
        ok: true,
        blob: () => Promise.resolve(mockBlob),
        headers: mockHeaders,
      });

      const clickSpy = vi.fn();
      const createElementOriginal = document.createElement.bind(document);
      vi.spyOn(document, 'createElement').mockImplementation((tag: string) => {
        const el = createElementOriginal(tag);
        if (tag === 'a') {
          el.click = clickSpy;
        }
        return el;
      });

      handleExportAdminLog();

      const button = document.getElementById('pmf-export-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(mockFetch).toHaveBeenCalledWith('/admin/api/statistics/admin-log/export', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ csrf: 'test-csrf' }),
      });

      expect(mockCreateObjectURL).toHaveBeenCalledWith(mockBlob);
      expect(clickSpy).toHaveBeenCalled();
      expect(mockRevokeObjectURL).toHaveBeenCalledWith('blob:http://localhost/fake-url');
      expect(pushNotification).toHaveBeenCalledWith('Admin log exported successfully');

      vi.restoreAllMocks();
    });

    it('should use fallback filename when Content-Disposition is missing', async () => {
      document.body.innerHTML = `
        <button id="pmf-export-admin-log" data-pmf-csrf="test-csrf">Export</button>
      `;

      const mockBlob = new Blob(['csv,data'], { type: 'text/csv' });
      const mockHeaders = new Headers();

      mockFetch.mockResolvedValue({
        ok: true,
        blob: () => Promise.resolve(mockBlob),
        headers: mockHeaders,
      });

      let capturedDownload = '';
      const createElementOriginal = document.createElement.bind(document);
      vi.spyOn(document, 'createElement').mockImplementation((tag: string) => {
        const el = createElementOriginal(tag);
        if (tag === 'a') {
          el.click = vi.fn();
          const originalDescriptor = Object.getOwnPropertyDescriptor(HTMLAnchorElement.prototype, 'download');
          Object.defineProperty(el, 'download', {
            set(val: string) {
              capturedDownload = val;
              if (originalDescriptor && originalDescriptor.set) {
                originalDescriptor.set.call(el, val);
              }
            },
            get() {
              return capturedDownload;
            },
          });
        }
        return el;
      });

      handleExportAdminLog();

      const button = document.getElementById('pmf-export-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(capturedDownload).toBe('admin-log-export.csv');

      vi.restoreAllMocks();
    });

    it('should show error on non-ok response', async () => {
      document.body.innerHTML = `
        <button id="pmf-export-admin-log" data-pmf-csrf="test-csrf">Export</button>
      `;

      mockFetch.mockResolvedValue({
        ok: false,
        json: () => Promise.resolve({ error: 'Server error occurred' }),
      });

      handleExportAdminLog();

      const button = document.getElementById('pmf-export-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(pushErrorNotification).toHaveBeenCalledWith('Server error occurred');
    });

    it('should show fallback error message on non-ok response without error field', async () => {
      document.body.innerHTML = `
        <button id="pmf-export-admin-log" data-pmf-csrf="test-csrf">Export</button>
      `;

      mockFetch.mockResolvedValue({
        ok: false,
        json: () => Promise.resolve({}),
      });

      handleExportAdminLog();

      const button = document.getElementById('pmf-export-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(pushErrorNotification).toHaveBeenCalledWith('Export failed');
    });

    it('should show error on fetch failure', async () => {
      document.body.innerHTML = `
        <button id="pmf-export-admin-log" data-pmf-csrf="test-csrf">Export</button>
      `;

      mockFetch.mockRejectedValue(new Error('Network failure'));

      handleExportAdminLog();

      const button = document.getElementById('pmf-export-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(pushErrorNotification).toHaveBeenCalledWith('Export error: Error: Network failure');
    });
  });

  describe('handleVerifyAdminLog', () => {
    it('should return early when elements are missing', async () => {
      document.body.innerHTML = '<div></div>';

      await handleVerifyAdminLog();

      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('should return early when only button exists but result container is missing', async () => {
      document.body.innerHTML = `
        <button id="pmf-button-verify-admin-log" data-pmf-csrf="test-csrf">Verify</button>
      `;

      await handleVerifyAdminLog();

      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('should show success alert when verification is valid', async () => {
      document.body.innerHTML = `
        <button id="pmf-button-verify-admin-log" data-pmf-csrf="test-csrf">
          <i class="bi bi-shield-check"></i> Verify
        </button>
        <div id="pmf-admin-log-verification-result" class="d-none"></div>
      `;

      mockFetch.mockResolvedValue({
        ok: true,
        json: () =>
          Promise.resolve({
            success: true,
            verification: {
              valid: true,
              verified: 100,
              total: 100,
            },
          }),
      });

      await handleVerifyAdminLog();

      const button = document.getElementById('pmf-button-verify-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      const resultContainer = document.getElementById('pmf-admin-log-verification-result') as HTMLDivElement;
      expect(resultContainer.className).toBe('alert alert-success');
      expect(resultContainer.innerHTML).toContain('Integrity verified');
      expect(resultContainer.innerHTML).toContain('100 of 100');
    });

    it('should show danger alert when verification is invalid', async () => {
      document.body.innerHTML = `
        <button id="pmf-button-verify-admin-log" data-pmf-csrf="test-csrf">
          <i class="bi bi-shield-check"></i> Verify
        </button>
        <div id="pmf-admin-log-verification-result" class="d-none"></div>
      `;

      mockFetch.mockResolvedValue({
        ok: true,
        json: () =>
          Promise.resolve({
            success: true,
            verification: {
              valid: false,
              verified: 8,
              failed: 2,
              errors: ['Entry 5 was tampered', 'Entry 9 hash mismatch'],
            },
          }),
      });

      await handleVerifyAdminLog();

      const button = document.getElementById('pmf-button-verify-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      const resultContainer = document.getElementById('pmf-admin-log-verification-result') as HTMLDivElement;
      expect(resultContainer.className).toBe('alert alert-danger');
      expect(resultContainer.innerHTML).toContain('Entry 5 was tampered');
      expect(resultContainer.innerHTML).toContain('Entry 9 hash mismatch');
      expect(resultContainer.innerHTML).toContain('8');
      expect(resultContainer.innerHTML).toContain('2');
    });

    it('should show warning alert on error response', async () => {
      document.body.innerHTML = `
        <button id="pmf-button-verify-admin-log" data-pmf-csrf="test-csrf">
          <i class="bi bi-shield-check"></i> Verify
        </button>
        <div id="pmf-admin-log-verification-result" class="d-none"></div>
      `;

      mockFetch.mockResolvedValue({
        ok: true,
        json: () =>
          Promise.resolve({
            success: false,
            error: 'Verification service unavailable',
          }),
      });

      await handleVerifyAdminLog();

      const button = document.getElementById('pmf-button-verify-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      const resultContainer = document.getElementById('pmf-admin-log-verification-result') as HTMLDivElement;
      expect(resultContainer.className).toBe('alert alert-warning');
      expect(resultContainer.textContent).toBe('Verification service unavailable');
    });

    it('should show fallback error message when error field is missing', async () => {
      document.body.innerHTML = `
        <button id="pmf-button-verify-admin-log" data-pmf-csrf="test-csrf">
          <i class="bi bi-shield-check"></i> Verify
        </button>
        <div id="pmf-admin-log-verification-result" class="d-none"></div>
      `;

      mockFetch.mockResolvedValue({
        ok: true,
        json: () =>
          Promise.resolve({
            success: false,
          }),
      });

      await handleVerifyAdminLog();

      const button = document.getElementById('pmf-button-verify-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      const resultContainer = document.getElementById('pmf-admin-log-verification-result') as HTMLDivElement;
      expect(resultContainer.className).toBe('alert alert-warning');
      expect(resultContainer.textContent).toBe('Fehler bei der Verifikation');
    });

    it('should show error when csrf is missing', async () => {
      document.body.innerHTML = `
        <button id="pmf-button-verify-admin-log">
          <i class="bi bi-shield-check"></i> Verify
        </button>
        <div id="pmf-admin-log-verification-result" class="d-none"></div>
      `;

      await handleVerifyAdminLog();

      const button = document.getElementById('pmf-button-verify-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      const resultContainer = document.getElementById('pmf-admin-log-verification-result') as HTMLDivElement;
      expect(resultContainer.className).toBe('alert alert-danger');
      expect(resultContainer.textContent).toContain('CSRF Token not found');
    });

    it('should re-enable button in finally block', async () => {
      document.body.innerHTML = `
        <button id="pmf-button-verify-admin-log" data-pmf-csrf="test-csrf">
          <i class="bi bi-shield-check"></i> Verify
        </button>
        <div id="pmf-admin-log-verification-result" class="d-none"></div>
      `;

      mockFetch.mockResolvedValue({
        ok: true,
        json: () =>
          Promise.resolve({
            success: true,
            verification: {
              valid: true,
              verified: 10,
              total: 10,
            },
          }),
      });

      await handleVerifyAdminLog();

      const button = document.getElementById('pmf-button-verify-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(button.disabled).toBe(false);
      expect(button.innerHTML).toContain('bi-shield-check');
      expect(button.innerHTML).toContain('Integrität prüfen');
    });

    it('should re-enable button even after fetch failure', async () => {
      document.body.innerHTML = `
        <button id="pmf-button-verify-admin-log" data-pmf-csrf="test-csrf">
          <i class="bi bi-shield-check"></i> Verify
        </button>
        <div id="pmf-admin-log-verification-result" class="d-none"></div>
      `;

      mockFetch.mockRejectedValue(new Error('Network failure'));

      await handleVerifyAdminLog();

      const button = document.getElementById('pmf-button-verify-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(button.disabled).toBe(false);
      expect(button.innerHTML).toContain('bi-shield-check');
      expect(button.innerHTML).toContain('Integrität prüfen');
    });

    it('should call fetch with correct URL and headers', async () => {
      document.body.innerHTML = `
        <button id="pmf-button-verify-admin-log" data-pmf-csrf="my-csrf-token">
          <i class="bi bi-shield-check"></i> Verify
        </button>
        <div id="pmf-admin-log-verification-result" class="d-none"></div>
      `;

      mockFetch.mockResolvedValue({
        ok: true,
        json: () =>
          Promise.resolve({
            success: true,
            verification: { valid: true, verified: 5, total: 5 },
          }),
      });

      await handleVerifyAdminLog();

      const button = document.getElementById('pmf-button-verify-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(mockFetch).toHaveBeenCalledWith('./api/statistics/admin-log/verify?csrf=my-csrf-token', {
        method: 'GET',
        headers: { Accept: 'application/json' },
      });
    });
  });

  describe('handleDeleteAdminLog', () => {
    it('should do nothing when button is missing', () => {
      document.body.innerHTML = '<div></div>';

      handleDeleteAdminLog();

      expect(deleteAdminLog).not.toHaveBeenCalled();
      expect(pushErrorNotification).not.toHaveBeenCalled();
    });

    it('should show error when csrf is missing', async () => {
      document.body.innerHTML = `
        <button id="pmf-delete-admin-log">Delete</button>
      `;

      handleDeleteAdminLog();

      const button = document.getElementById('pmf-delete-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(pushErrorNotification).toHaveBeenCalledWith('Missing CSRF token');
      expect(deleteAdminLog).not.toHaveBeenCalled();
    });

    it('should call deleteAdminLog and show notification on success', async () => {
      document.body.innerHTML = `
        <button id="pmf-delete-admin-log" data-pmf-csrf="test-csrf">Delete</button>
      `;

      (deleteAdminLog as Mock).mockResolvedValue({ success: 'Admin log deleted' });

      handleDeleteAdminLog();

      const button = document.getElementById('pmf-delete-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(deleteAdminLog).toHaveBeenCalledWith('test-csrf');
      expect(pushNotification).toHaveBeenCalledWith('Admin log deleted');
    });

    it('should show error notification on error response', async () => {
      document.body.innerHTML = `
        <button id="pmf-delete-admin-log" data-pmf-csrf="test-csrf">Delete</button>
      `;

      (deleteAdminLog as Mock).mockResolvedValue({ error: 'Deletion failed' });

      handleDeleteAdminLog();

      const button = document.getElementById('pmf-delete-admin-log') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(deleteAdminLog).toHaveBeenCalledWith('test-csrf');
      expect(pushErrorNotification).toHaveBeenCalledWith('Deletion failed');
    });
  });
});
