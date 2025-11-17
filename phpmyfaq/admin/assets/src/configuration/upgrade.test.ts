import { describe, it, expect, beforeEach, vi } from 'vitest';
import { handleStreamingProgress } from './upgrade';

/**
 * Helper to create a mock readable stream with progress updates
 */
function createMockStream(progressValues: string[]): ReadableStream<Uint8Array> {
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
function createMockResponse(progressValues: string[]): Response {
  const stream = createMockStream(progressValues);
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
    const response = createMockResponse(['10%', '25%', '50%', '75%', '90%']);

    await handleStreamingProgress(response, progressBarId);

    // Check final state
    expect(progressBar.style.width).toBe('100%');
    expect(progressBar.innerText).toBe('100%');
    expect(progressBar.classList.contains('bg-success')).toBe(true);
    expect(progressBar.classList.contains('bg-primary')).toBe(false);
    expect(progressBar.classList.contains('progress-bar-animated')).toBe(false);
  });

  it('should handle empty stream and complete with 100%', async () => {
    const response = createMockResponse([]);

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
    const response = createMockResponse(['50%']);
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
    const response = createMockResponse(progressValues);

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
    const response = createMockResponse(['50%']);

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
    const response = createMockResponse(progressValues);

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

    const response = createMockResponse(['33%', '66%', '99%']);
    await handleStreamingProgress(response, 'integration-test-bar');

    const progressBar = document.getElementById('integration-test-bar') as HTMLElement;
    expect(progressBar.style.width).toBe('100%');
    expect(progressBar.innerText).toBe('100%');
    expect(progressBar.classList.contains('bg-success')).toBe(true);
  });
});
