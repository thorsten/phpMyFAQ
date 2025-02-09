import { describe, it, expect, vi, afterEach, Mock } from 'vitest';
import {
  fetchHealthCheck,
  activateMaintenanceMode,
  checkForUpdates,
  downloadPackage,
  extractPackage,
  startTemporaryBackup,
  startInstallation,
  startDatabaseUpdate,
} from './upgrade';

global.fetch = vi.fn();

describe('Upgrade API', (): void => {
  afterEach((): void => {
    vi.restoreAllMocks();
  });

  it('fetchHealthCheck should fetch health check and return JSON response if successful', async (): Promise<void> => {
    const mockResponse = { success: 'true', message: 'Health check passed' };
    (fetch as Mock).mockResolvedValue({
      ok: true,
      json: async () => mockResponse,
    });

    const result = await fetchHealthCheck();
    expect(result).toEqual(mockResponse);
    expect(fetch).toHaveBeenCalledWith('./api/health-check', {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  it('activateMaintenanceMode should activate maintenance mode', async (): Promise<void> => {
    const mockResponse = { success: 'true' };
    (fetch as Mock).mockResolvedValue({
      ok: true,
      json: async () => mockResponse,
    });

    const result = await activateMaintenanceMode('csrfToken');
    expect(result).toEqual(mockResponse);
    expect(fetch).toHaveBeenCalledWith('./api/configuration/activate-maintenance-mode', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ csrf: 'csrfToken' }),
    });
  });

  it('checkForUpdates should check for updates', async (): Promise<void> => {
    const mockResponse = { success: 'true', version: '1.0.0' };
    (fetch as Mock).mockResolvedValue({
      ok: true,
      json: async () => mockResponse,
    });

    const result = await checkForUpdates();
    expect(result).toEqual(mockResponse);
    expect(fetch).toHaveBeenCalledWith('./api/update-check', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });
  });

  it('downloadPackage should download a package', async (): Promise<void> => {
    const mockResponse = { success: 'true' };
    (fetch as Mock).mockResolvedValue({
      ok: true,
      json: async () => mockResponse,
    });

    const result = await downloadPackage('1.0.0');
    expect(result).toEqual(mockResponse);
    expect(fetch).toHaveBeenCalledWith('./api/download-package/1.0.0', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });
  });

  it('extractPackage should extract a package', async (): Promise<void> => {
    const mockResponse = { success: 'true' };
    (fetch as Mock).mockResolvedValue({
      ok: true,
      json: async () => mockResponse,
    });

    const result = await extractPackage();
    expect(result).toEqual(mockResponse);
    expect(fetch).toHaveBeenCalledWith('./api/extract-package', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });
  });

  it('startTemporaryBackup should start a temporary backup', async (): Promise<void> => {
    const mockResponse = new Response(JSON.stringify({ progress: '50%' }), {
      headers: { 'Content-Type': 'application/json' },
    });
    (fetch as Mock).mockResolvedValue(mockResponse);
    await startTemporaryBackup();
    expect(fetch).toHaveBeenCalledWith('./api/create-temporary-backup', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });
  });

  it('startInstallation should start the installation', async (): Promise<void> => {
    const mockResponse = new Response(JSON.stringify({ progress: '50%' }), {
      headers: { 'Content-Type': 'application/json' },
    });
    (fetch as Mock).mockResolvedValue(mockResponse);
    await startInstallation();
    expect(fetch).toHaveBeenCalledWith('./api/install-package', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });
  });

  it('startDatabaseUpdate should start the database update', async (): Promise<void> => {
    const mockResponse = new Response(JSON.stringify({ progress: '50%' }), {
      headers: { 'Content-Type': 'application/json' },
    });
    (fetch as Mock).mockResolvedValue(mockResponse);
    await startDatabaseUpdate();
    expect(fetch).toHaveBeenCalledWith('./api/update-database', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });
  });
});
