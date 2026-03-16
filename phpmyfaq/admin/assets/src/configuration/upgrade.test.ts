import { describe, it, expect, beforeEach, vi } from 'vitest';
import { handleStreamingProgress, handleCheckForUpdates } from './upgrade';

vi.mock('../../../../assets/src/utils', () => ({
  addElement: vi.fn((tag: string, props: Record<string, string>) => {
    const el = document.createElement(tag);
    if (props.id) el.id = props.id;
    if (props.innerText) el.innerText = props.innerText;
    return el;
  }),
  versionCompare: vi.fn(),
}));

vi.mock('../api', () => ({
  fetchHealthCheck: vi.fn(),
  activateMaintenanceMode: vi.fn(),
  checkForUpdates: vi.fn(),
  downloadPackage: vi.fn(),
  extractPackage: vi.fn(),
  startTemporaryBackup: vi.fn(),
  startInstallation: vi.fn(),
  startDatabaseUpdate: vi.fn(),
}));

import {
  fetchHealthCheck,
  activateMaintenanceMode,
  checkForUpdates as checkForUpdatesApi,
  downloadPackage,
  extractPackage,
} from '../api';
import { versionCompare } from '../../../../assets/src/utils';

/**
 * Helper to create a mock readable stream with progress updates
 */
function createStubStream(progressValues: string[]): ReadableStream<Uint8Array> {
  const encoder = new TextEncoder();
  let index = 0;

  return new ReadableStream({
    async pull(controller) {
      if (index < progressValues.length) {
        const data = JSON.stringify({ progress: progressValues[index] });
        controller.enqueue(encoder.encode(data));
        index++;
      } else {
        controller.close();
      }
    },
  });
}

/**
 * Helper to create a mock Response with a readable stream
 */
function createStubResponse(progressValues: string[]): Response {
  const stream = createStubStream(progressValues);
  return new Response(stream);
}

describe('handleStreamingProgress', () => {
  let progressBar: HTMLDivElement;
  const progressBarId = 'test-progress-bar';

  beforeEach(() => {
    // Clear the document body before each test
    document.body.innerHTML = '';

    // Create a fresh progress bar element
    progressBar = document.createElement('div');
    progressBar.id = progressBarId;
    progressBar.className = 'progress-bar progress-bar-animated bg-primary';
    progressBar.style.width = '0%';
    progressBar.innerText = '0%';
    document.body.appendChild(progressBar);

    // Clear all mocks
    vi.clearAllMocks();
  });

  it('should update progress bar with streaming values', async () => {
    const response = createStubResponse(['10%', '25%', '50%', '75%', '90%']);

    await handleStreamingProgress(response, progressBarId);

    // Check final state
    expect(progressBar.style.width).toBe('100%');
    expect(progressBar.innerText).toBe('100%');
    expect(progressBar.classList.contains('bg-success')).toBe(true);
    expect(progressBar.classList.contains('bg-primary')).toBe(false);
    expect(progressBar.classList.contains('progress-bar-animated')).toBe(false);
  });

  it('should handle empty stream and complete with 100%', async () => {
    const response = createStubResponse([]);

    await handleStreamingProgress(response, progressBarId);

    expect(progressBar.style.width).toBe('100%');
    expect(progressBar.innerText).toBe('100%');
    expect(progressBar.classList.contains('bg-success')).toBe(true);
  });

  it('should handle invalid JSON in stream gracefully', async () => {
    const encoder = new TextEncoder();
    const stream = new ReadableStream({
      async pull(controller) {
        // Send invalid JSON
        controller.enqueue(encoder.encode('invalid json {'));
        controller.enqueue(encoder.encode(JSON.stringify({ progress: '50%' })));
        controller.close();
      },
    });

    const response = new Response(stream);
    const consoleDebugSpy = vi.spyOn(console, 'debug').mockImplementation(() => {});

    await handleStreamingProgress(response, progressBarId);

    // Should log the parse error but continue
    expect(consoleDebugSpy).toHaveBeenCalled();
    expect(progressBar.style.width).toBe('100%'); // Final state should still be 100%

    consoleDebugSpy.mockRestore();
  });

  it('should handle missing progress bar element', async () => {
    const response = createStubResponse(['50%']);
    const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

    await handleStreamingProgress(response, 'non-existent-id');

    expect(consoleErrorSpy).toHaveBeenCalledWith('Progress bar element with id "non-existent-id" not found');

    consoleErrorSpy.mockRestore();
  });

  it('should handle null response body', async () => {
    const response = new Response(null);
    const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

    await handleStreamingProgress(response, progressBarId);

    expect(consoleErrorSpy).toHaveBeenCalledWith('Response body is null, cannot stream progress');

    consoleErrorSpy.mockRestore();
  });

  it('should update progress bar incrementally', async () => {
    const progressValues = ['25%', '50%', '75%'];
    const response = createStubResponse(progressValues);

    // We need to intercept the updates as they happen
    const updates: string[] = [];

    // Track each width update
    Object.defineProperty(progressBar.style, 'width', {
      get() {
        return this._width || '0%';
      },
      set(value) {
        this._width = value;
        if (value !== '100%') {
          updates.push(value);
        }
      },
      configurable: true,
    });

    await handleStreamingProgress(response, progressBarId);

    // Should have updated with each progress value
    expect(updates).toContain('25%');
    expect(updates).toContain('50%');
    expect(updates).toContain('75%');

    // Final state should be 100%
    expect(progressBar.style.width).toBe('100%');
  });

  it('should handle responses without progress property', async () => {
    const encoder = new TextEncoder();
    const stream = new ReadableStream({
      async pull(controller) {
        // Send JSON without progress property
        controller.enqueue(encoder.encode(JSON.stringify({ status: 'processing' })));
        controller.enqueue(encoder.encode(JSON.stringify({ progress: '50%' })));
        controller.close();
      },
    });

    const response = new Response(stream);

    await handleStreamingProgress(response, progressBarId);

    // Should complete successfully
    expect(progressBar.style.width).toBe('100%');
    expect(progressBar.classList.contains('bg-success')).toBe(true);
  });

  it('should remove animation classes when complete', async () => {
    const response = createStubResponse(['50%']);

    // Verify initial state has animation classes
    expect(progressBar.classList.contains('progress-bar-animated')).toBe(true);
    expect(progressBar.classList.contains('bg-primary')).toBe(true);

    await handleStreamingProgress(response, progressBarId);

    // Should remove animation classes
    expect(progressBar.classList.contains('progress-bar-animated')).toBe(false);
    expect(progressBar.classList.contains('bg-primary')).toBe(false);
    expect(progressBar.classList.contains('bg-success')).toBe(true);
  });

  it('should handle rapid progress updates', async () => {
    // Create many rapid updates
    const progressValues = Array.from({ length: 20 }, (_, i) => `${(i + 1) * 5}%`);
    const response = createStubResponse(progressValues);

    await handleStreamingProgress(response, progressBarId);

    expect(progressBar.style.width).toBe('100%');
    expect(progressBar.classList.contains('bg-success')).toBe(true);
  });
});

describe('Streaming progress error scenarios', () => {
  let progressBar: HTMLDivElement;
  const progressBarId = 'error-test-progress-bar';

  beforeEach(() => {
    document.body.innerHTML = '';
    progressBar = document.createElement('div');
    progressBar.id = progressBarId;
    progressBar.className = 'progress-bar progress-bar-animated bg-primary';
    progressBar.style.width = '0%';
    progressBar.innerText = '0%';
    document.body.appendChild(progressBar);
  });

  it('should handle stream read errors', async () => {
    const stream = new ReadableStream({
      async pull(controller) {
        controller.error(new Error('Stream read error'));
      },
    });

    const response = new Response(stream);

    await expect(handleStreamingProgress(response, progressBarId)).rejects.toThrow('Stream read error');
  });

  it('should handle TextDecoder errors', async () => {
    const stream = new ReadableStream({
      async pull(controller) {
        // Send invalid UTF-8 bytes
        controller.enqueue(new Uint8Array([0xff, 0xfe, 0xfd]));
        controller.close();
      },
    });

    const response = new Response(stream);
    const consoleDebugSpy = vi.spyOn(console, 'debug').mockImplementation(() => {});

    // TextDecoder should handle invalid bytes gracefully with replacement characters
    await handleStreamingProgress(response, progressBarId);

    // Should still complete
    expect(progressBar.style.width).toBe('100%');

    consoleDebugSpy.mockRestore();
  });
});

describe('Progress bar integration scenarios', () => {
  it('should work with real DOM progress bar structure', async () => {
    document.body.innerHTML = `
      <div class="progress" role="progressbar">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
             id="integration-test-bar" style="width: 0">
          0%
        </div>
      </div>
    `;

    const response = createStubResponse(['33%', '66%', '99%']);
    await handleStreamingProgress(response, 'integration-test-bar');

    const progressBar = document.getElementById('integration-test-bar') as HTMLElement;
    expect(progressBar.style.width).toBe('100%');
    expect(progressBar.innerText).toBe('100%');
    expect(progressBar.classList.contains('bg-success')).toBe(true);
  });
});

describe('handleCheckForUpdates - Health Check', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = `
      <button id="pmf-button-check-health">Check</button>
      <p id="result-check-health">Pending</p>
      <div id="pmf-update-step-health-check"></div>
      <button id="pmf-button-activate-maintenance-mode" class="d-none" data-pmf-csrf="csrf123"></button>
      <button id="pmf-button-check-updates">Check Updates</button>
      <button id="pmf-button-download-now">Download</button>
      <button id="pmf-button-extract-package">Extract</button>
      <button id="pmf-button-install-package">Install</button>
    `;
  });

  it('should show success on healthy check', async () => {
    vi.mocked(fetchHealthCheck).mockResolvedValue({ success: 'System is healthy' });

    handleCheckForUpdates();

    const button = document.getElementById('pmf-button-check-health') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(fetchHealthCheck).toHaveBeenCalled();
    });

    const card = document.getElementById('pmf-update-step-health-check') as HTMLElement;
    expect(card.classList.contains('text-bg-success')).toBe(true);
  });

  it('should show warning and activate button on warning response', async () => {
    vi.mocked(fetchHealthCheck).mockResolvedValue({ warning: 'Maintenance mode not active' });

    handleCheckForUpdates();

    const button = document.getElementById('pmf-button-check-health') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(fetchHealthCheck).toHaveBeenCalled();
    });

    const card = document.getElementById('pmf-update-step-health-check') as HTMLElement;
    expect(card.classList.contains('text-bg-warning')).toBe(true);

    const activateBtn = document.getElementById('pmf-button-activate-maintenance-mode') as HTMLElement;
    expect(activateBtn.classList.contains('d-none')).toBe(false);
  });

  it('should show error on error response', async () => {
    vi.mocked(fetchHealthCheck).mockResolvedValue({ error: 'Critical issue found' });

    handleCheckForUpdates();

    const button = document.getElementById('pmf-button-check-health') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(fetchHealthCheck).toHaveBeenCalled();
    });

    const card = document.getElementById('pmf-update-step-health-check') as HTMLElement;
    expect(card.classList.contains('text-bg-danger')).toBe(true);
  });

  it('should handle fetch error gracefully', async () => {
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    vi.mocked(fetchHealthCheck).mockRejectedValue(new Error('Network error'));

    handleCheckForUpdates();

    const button = document.getElementById('pmf-button-check-health') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(consoleSpy).toHaveBeenCalled();
    });

    consoleSpy.mockRestore();
  });
});

describe('handleCheckForUpdates - Activate Maintenance Mode', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = `
      <button id="pmf-button-check-health">Check</button>
      <p id="result-check-health">Pending</p>
      <div id="pmf-update-step-health-check" class="text-bg-warning"></div>
      <button id="pmf-button-activate-maintenance-mode" data-pmf-csrf="csrf-token-123"></button>
      <button id="pmf-button-check-updates">Check Updates</button>
      <button id="pmf-button-download-now">Download</button>
      <button id="pmf-button-extract-package">Extract</button>
      <button id="pmf-button-install-package">Install</button>
    `;
  });

  it('should activate maintenance mode and update UI', async () => {
    vi.mocked(activateMaintenanceMode).mockResolvedValue({ success: 'Maintenance mode activated' });

    handleCheckForUpdates();

    const button = document.getElementById('pmf-button-activate-maintenance-mode') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(activateMaintenanceMode).toHaveBeenCalledWith('csrf-token-123');
    });

    const card = document.getElementById('pmf-update-step-health-check') as HTMLElement;
    expect(card.classList.contains('text-bg-success')).toBe(true);
    expect(card.classList.contains('text-bg-warning')).toBe(false);

    expect(button.classList.contains('d-none')).toBe(true);
  });
});

describe('handleCheckForUpdates - Check Updates', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = `
      <button id="pmf-button-check-health">Check</button>
      <button id="pmf-button-activate-maintenance-mode" class="d-none"></button>
      <p id="result-check-health"></p>
      <div id="pmf-update-step-health-check"></div>
      <button id="pmf-button-check-updates">Check Updates</button>
      <div id="spinner-check-versions" class="d-none"></div>
      <div id="dateLastChecked"></div>
      <div id="versionCurrent">4.1.0</div>
      <div id="versionLastChecked"></div>
      <p id="result-check-versions">Pending</p>
      <div id="pmf-update-step-check-versions"></div>
      <button id="pmf-button-download-now">Download</button>
      <button id="pmf-button-extract-package">Extract</button>
      <button id="pmf-button-install-package">Install</button>
    `;
  });

  it('should show newer version available with success styling', async () => {
    // Set innerText explicitly for jsdom compatibility
    (document.getElementById('versionCurrent') as HTMLElement).innerText = '4.1.0';

    vi.mocked(checkForUpdatesApi).mockResolvedValue({
      dateLastChecked: '2026-03-16T12:00:00Z',
      version: '4.2.0',
      message: 'New version available',
    });
    vi.mocked(versionCompare).mockReturnValue(true);

    handleCheckForUpdates();

    const button = document.getElementById('pmf-button-check-updates') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(checkForUpdatesApi).toHaveBeenCalled();
    });

    const card = document.getElementById('pmf-update-step-check-versions') as HTMLElement;
    expect(card.classList.contains('text-bg-success')).toBe(true);

    const spinner = document.getElementById('spinner-check-versions') as HTMLElement;
    expect(spinner.classList.contains('d-none')).toBe(true);

    expect(button.disabled).toBe(true);

    const versionLastChecked = document.getElementById('versionLastChecked') as HTMLElement;
    expect(versionLastChecked.innerText).toBe('4.2.0');
  });

  it('should show danger and disable download when version is older', async () => {
    (document.getElementById('versionCurrent') as HTMLElement).innerText = '4.1.0';

    vi.mocked(checkForUpdatesApi).mockResolvedValue({
      version: '4.0.0',
      message: 'You are up to date',
    });
    vi.mocked(versionCompare).mockReturnValue(false);

    handleCheckForUpdates();

    const button = document.getElementById('pmf-button-check-updates') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(checkForUpdatesApi).toHaveBeenCalled();
    });

    const card = document.getElementById('pmf-update-step-check-versions') as HTMLElement;
    expect(card.classList.contains('text-bg-danger')).toBe(true);

    const downloadButton = document.getElementById('pmf-button-download-now') as HTMLButtonElement;
    expect(downloadButton.disabled).toBe(true);
  });

  it('should show error when no version is returned', async () => {
    vi.mocked(checkForUpdatesApi).mockResolvedValue({
      message: 'Could not check for updates',
    });

    handleCheckForUpdates();

    const button = document.getElementById('pmf-button-check-updates') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(checkForUpdatesApi).toHaveBeenCalled();
    });

    const card = document.getElementById('pmf-update-step-check-versions') as HTMLElement;
    expect(card.classList.contains('text-bg-danger')).toBe(true);
  });
});

describe('handleCheckForUpdates - Download Package', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = `
      <button id="pmf-button-check-health">Check</button>
      <button id="pmf-button-activate-maintenance-mode" class="d-none"></button>
      <p id="result-check-health"></p>
      <div id="pmf-update-step-health-check"></div>
      <button id="pmf-button-check-updates">Check Updates</button>
      <button id="pmf-button-download-now">Download</button>
      <div id="versionLastChecked">4.2.0</div>
      <div id="releaseEnvironment">stable</div>
      <div id="spinner-download-new-version" class="d-none"></div>
      <p id="result-download-new-version">Pending</p>
      <div id="pmf-update-step-download"></div>
      <div id="pmf-update-step-extract-package" class="d-none"></div>
      <button id="pmf-button-extract-package">Extract</button>
      <button id="pmf-button-install-package">Install</button>
    `;
  });

  it('should download package and show extract step on success', async () => {
    (document.getElementById('versionLastChecked') as HTMLElement).innerText = '4.2.0';
    (document.getElementById('releaseEnvironment') as HTMLElement).innerText = 'stable';

    vi.mocked(downloadPackage).mockResolvedValue({ success: 'Package downloaded' });

    handleCheckForUpdates();

    const button = document.getElementById('pmf-button-download-now') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(downloadPackage).toHaveBeenCalledWith('4.2.0');
    });

    const card = document.getElementById('pmf-update-step-download') as HTMLElement;
    expect(card.classList.contains('text-bg-success')).toBe(true);

    const extractStep = document.getElementById('pmf-update-step-extract-package') as HTMLElement;
    expect(extractStep.classList.contains('d-none')).toBe(false);

    expect(button.disabled).toBe(true);
  });

  it('should use nightly version when release environment is nightly', async () => {
    (document.getElementById('versionLastChecked') as HTMLElement).innerText = '4.2.0';
    const releaseEnv = document.getElementById('releaseEnvironment') as HTMLElement;
    releaseEnv.innerText = 'nightly';

    vi.mocked(downloadPackage).mockResolvedValue({ success: 'Downloaded' });

    handleCheckForUpdates();

    const button = document.getElementById('pmf-button-download-now') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(downloadPackage).toHaveBeenCalledWith('nightly');
    });
  });

  it('should show error on download failure', async () => {
    (document.getElementById('versionLastChecked') as HTMLElement).innerText = '4.2.0';
    (document.getElementById('releaseEnvironment') as HTMLElement).innerText = 'stable';

    vi.mocked(downloadPackage).mockResolvedValue({ error: 'Download failed' });

    handleCheckForUpdates();

    const button = document.getElementById('pmf-button-download-now') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(downloadPackage).toHaveBeenCalled();
    });

    const card = document.getElementById('pmf-update-step-download') as HTMLElement;
    expect(card.classList.contains('text-bg-danger')).toBe(true);
  });
});

describe('handleCheckForUpdates - Extract Package', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = `
      <button id="pmf-button-check-health">Check</button>
      <button id="pmf-button-activate-maintenance-mode" class="d-none"></button>
      <p id="result-check-health"></p>
      <div id="pmf-update-step-health-check"></div>
      <button id="pmf-button-check-updates">Check Updates</button>
      <button id="pmf-button-download-now">Download</button>
      <button id="pmf-button-extract-package">Extract</button>
      <div id="spinner-extract-package" class="d-none"></div>
      <p id="result-extract-package">Pending</p>
      <div id="pmf-update-step-extract-package"></div>
      <div id="pmf-update-step-install-package" class="d-none"></div>
      <button id="pmf-button-install-package">Install</button>
    `;
  });

  it('should extract package and show install step on success', async () => {
    vi.mocked(extractPackage).mockResolvedValue({ message: 'Package extracted' });

    handleCheckForUpdates();

    const button = document.getElementById('pmf-button-extract-package') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(extractPackage).toHaveBeenCalled();
    });

    const card = document.getElementById('pmf-update-step-extract-package') as HTMLElement;
    expect(card.classList.contains('text-bg-success')).toBe(true);

    const installStep = document.getElementById('pmf-update-step-install-package') as HTMLElement;
    expect(installStep.classList.contains('d-none')).toBe(false);

    expect(button.disabled).toBe(true);
  });
});

describe('handleCheckForUpdates - no buttons', () => {
  it('should not throw when no buttons exist', () => {
    document.body.innerHTML = '<div></div>';

    expect(() => handleCheckForUpdates()).not.toThrow();
  });
});
