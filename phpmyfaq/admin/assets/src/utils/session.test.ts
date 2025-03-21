import { describe, it, expect, vi, afterEach } from 'vitest';
import { handleSessionTimeout } from './session';

vi.mock('bootstrap', () => ({
  Modal: vi.fn().mockImplementation(() => ({
    show: vi.fn(),
    hide: vi.fn(),
  })),
}));

describe('Session Utils', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('handleSessionTimeout', () => {
    it('should observe attribute changes and reload page on button click', () => {
      document.body.innerHTML = `
        <div id="pmf-show-session-warning" data-value="hide"></div>
        <div id="sessionWarningModal"></div>
        <button id="pmf-button-reload-page"></button>
      `;

      const mockObserver = {
        observe: vi.fn(),
        disconnect: vi.fn(),
        takeRecords: vi.fn(),
      };
      global.MutationObserver = vi.fn(() => mockObserver) as unknown as typeof MutationObserver;

      const mockReload = vi.fn();
      Object.defineProperty(global, 'location', {
        value: {
          reload: mockReload,
        },
        writable: true,
      });

      handleSessionTimeout();

      expect(mockObserver.observe).toHaveBeenCalled();
      const reloadButton = document.getElementById('pmf-button-reload-page');
      reloadButton?.click();
      expect(mockReload).toHaveBeenCalled();
    });
  });
});
