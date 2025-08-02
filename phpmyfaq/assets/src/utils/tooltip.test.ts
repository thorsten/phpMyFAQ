import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { Tooltip } from 'bootstrap';

// Mock Bootstrap Tooltip
vi.mock('bootstrap', () => ({
  Tooltip: vi.fn(),
}));

// Import the module to trigger the DOMContentLoaded event listener
import './tooltip';

describe('tooltip', () => {
  const mockTooltip = vi.mocked(Tooltip);

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('should initialize tooltips on DOMContentLoaded', () => {
    // Setup DOM with tooltip elements
    document.body.innerHTML = `
      <button data-bs-toggle="tooltip" title="First tooltip">Button 1</button>
      <span data-bs-toggle="tooltip" title="Second tooltip">Span 1</span>
      <div data-bs-toggle="tooltip" title="Third tooltip">Div 1</div>
    `;

    // Simulate DOMContentLoaded event
    const event = new Event('DOMContentLoaded');
    document.dispatchEvent(event);

    // Verify Tooltip was called for each element
    expect(mockTooltip).toHaveBeenCalledTimes(3);
    expect(mockTooltip).toHaveBeenCalledWith(document.querySelector('button[data-bs-toggle="tooltip"]'));
    expect(mockTooltip).toHaveBeenCalledWith(document.querySelector('span[data-bs-toggle="tooltip"]'));
    expect(mockTooltip).toHaveBeenCalledWith(document.querySelector('div[data-bs-toggle="tooltip"]'));
  });

  it('should not initialize tooltips when no elements exist', () => {
    // Setup DOM without tooltip elements
    document.body.innerHTML = '<div>No tooltips here</div>';

    // Simulate DOMContentLoaded event
    const event = new Event('DOMContentLoaded');
    document.dispatchEvent(event);

    // Verify Tooltip was not called
    expect(mockTooltip).not.toHaveBeenCalled();
  });

  it('should handle empty document', () => {
    // Empty document
    document.body.innerHTML = '';

    // Simulate DOMContentLoaded event
    const event = new Event('DOMContentLoaded');
    document.dispatchEvent(event);

    // Verify Tooltip was not called
    expect(mockTooltip).not.toHaveBeenCalled();
  });
});
