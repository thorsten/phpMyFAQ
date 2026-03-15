import { describe, it, expect, vi, beforeEach } from 'vitest';

const mockHighlightElement = vi.fn();

vi.mock('highlight.js', () => ({
  default: {
    highlightElement: mockHighlightElement,
  },
}));

describe('highlight', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.resetModules();
    document.body.innerHTML = '';
  });

  it('should call hljs.highlightElement for each pre code block', async () => {
    document.body.innerHTML = `
      <pre><code class="language-js">const x = 1;</code></pre>
      <pre><code class="language-php">echo "hello";</code></pre>
    `;

    // Import triggers the DOMContentLoaded listener registration
    await import('./highlight');

    // Fire the event
    document.dispatchEvent(new Event('DOMContentLoaded'));

    expect(mockHighlightElement).toHaveBeenCalledTimes(2);

    const codeElements = document.querySelectorAll('pre code');
    expect(mockHighlightElement).toHaveBeenCalledWith(codeElements[0]);
    expect(mockHighlightElement).toHaveBeenCalledWith(codeElements[1]);
  });

  it('should not call hljs.highlightElement when no code blocks exist', async () => {
    document.body.innerHTML = '<div>No code here</div>';

    await import('./highlight');

    document.dispatchEvent(new Event('DOMContentLoaded'));

    expect(mockHighlightElement).not.toHaveBeenCalled();
  });

  it('should only highlight code inside pre tags', async () => {
    document.body.innerHTML = `
      <code>inline code</code>
      <pre><code>block code</code></pre>
      <div><code>another inline</code></div>
    `;

    mockHighlightElement.mockClear();

    await import('./highlight');

    document.dispatchEvent(new Event('DOMContentLoaded'));

    // The listener may fire multiple times due to module caching across tests,
    // but each firing should only target pre>code elements.
    // Verify that only pre>code elements were passed.
    const calls = mockHighlightElement.mock.calls;
    const preCode = document.querySelector('pre code') as HTMLElement;
    const calledElements = calls.map((call) => call[0]);
    expect(calledElements).toContain(preCode);

    // Inline code elements should never be highlighted
    const inlineCodes = document.querySelectorAll('div > code, body > code');
    inlineCodes.forEach((el) => {
      expect(calledElements).not.toContain(el);
    });
  });
});
