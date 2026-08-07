import { describe, it, expect, beforeEach, vi } from 'vitest';
import { handleStreamingProgress } from './upgrade';

interface StubStreamMessage {
  progress?: string;
  success?: string;
  error?: string;
  message?: string;
  status?: string;
}

/**
 * Helper to create a mock readable stream with newline-delimited JSON lines,
 * matching the server protocol: progress lines followed by a terminal line.
 */
function createStubStream(progressValues: string[], terminal: StubStreamMessage | null): ReadableStream<Uint8Array> {
  const encoder = new TextEncoder();
  const lines: string[] = progressValues.map((progress: string): string => JSON.stringify({ progress }) + '\n');
  if (terminal !== null) {
    lines.push(JSON.stringify(terminal));
  }
  let index = 0;

  return new ReadableStream({
    async pull(controller) {
      if (index < lines.length) {
        controller.enqueue(encoder.encode(lines[index]));
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
function createStubResponse(
  progressValues: string[],
  terminal: StubStreamMessage | null = { success: 'Step finished' }
): Response {
  const stream = createStubStream(progressValues, terminal);
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

  it('should treat a message terminal line as success', async () => {
    const response = createStubResponse(['50%'], { message: 'Package successfully extracted.' });

    await handleStreamingProgress(response, progressBarId);

    expect(progressBar.style.width).toBe('100%');
    expect(progressBar.classList.contains('bg-success')).toBe(true);
  });

  it('should reject when the stream carries a terminal error', async () => {
    const response = createStubResponse(['25%'], { error: 'Install package failed: could not copy 3 path(s)' });

    await expect(handleStreamingProgress(response, progressBarId)).rejects.toThrow(
      'Install package failed: could not copy 3 path(s)'
    );

    expect(progressBar.classList.contains('bg-danger')).toBe(true);
    expect(progressBar.classList.contains('bg-success')).toBe(false);
  });

  it('should reject when the stream ends without a terminal message', async () => {
    // Simulates the server process dying mid-step (e.g. a timeout while copying files)
    const response = createStubResponse(['10%', '20%'], null);

    await expect(handleStreamingProgress(response, progressBarId)).rejects.toThrow(
      'The server stopped responding before this step was finished. Please check the server logs.'
    );

    expect(progressBar.classList.contains('bg-danger')).toBe(true);
    expect(progressBar.classList.contains('bg-success')).toBe(false);
  });

  it('should reject on an empty stream', async () => {
    const response = createStubResponse([], null);

    await expect(handleStreamingProgress(response, progressBarId)).rejects.toThrow(
      'The server stopped responding before this step was finished. Please check the server logs.'
    );

    expect(progressBar.classList.contains('bg-danger')).toBe(true);
  });

  it('should handle a terminal line split across chunks', async () => {
    const encoder = new TextEncoder();
    const stream = new ReadableStream({
      async pull(controller) {
        controller.enqueue(encoder.encode('{"progress":"50%"}\n{"succ'));
        controller.enqueue(encoder.encode('ess":"Package successfully installed."}'));
        controller.close();
      },
    });

    const response = new Response(stream);

    await handleStreamingProgress(response, progressBarId);

    expect(progressBar.style.width).toBe('100%');
    expect(progressBar.classList.contains('bg-success')).toBe(true);
  });

  it('should handle invalid JSON in stream gracefully', async () => {
    const encoder = new TextEncoder();
    const stream = new ReadableStream({
      async pull(controller) {
        // Send invalid JSON followed by a valid terminal line
        controller.enqueue(encoder.encode('invalid json {\n'));
        controller.enqueue(encoder.encode(JSON.stringify({ success: 'Step finished' })));
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

  it('should handle lines without progress property', async () => {
    const encoder = new TextEncoder();
    const stream = new ReadableStream({
      async pull(controller) {
        // Send JSON without progress property
        controller.enqueue(encoder.encode(JSON.stringify({ status: 'processing' }) + '\n'));
        controller.enqueue(encoder.encode(JSON.stringify({ progress: '50%' }) + '\n'));
        controller.enqueue(encoder.encode(JSON.stringify({ success: 'Step finished' })));
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

  it('should reject on undecodable bytes without a terminal message', async () => {
    const stream = new ReadableStream({
      async pull(controller) {
        // Send invalid UTF-8 bytes
        controller.enqueue(new Uint8Array([0xff, 0xfe, 0xfd]));
        controller.close();
      },
    });

    const response = new Response(stream);
    const consoleDebugSpy = vi.spyOn(console, 'debug').mockImplementation(() => {});

    // Replacement characters cannot form a terminal message, so this counts as a dead stream
    await expect(handleStreamingProgress(response, progressBarId)).rejects.toThrow(
      'The server stopped responding before this step was finished. Please check the server logs.'
    );

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
