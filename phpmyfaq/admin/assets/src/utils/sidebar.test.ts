import { describe, it, expect, vi, afterEach } from 'vitest';
import { sidebarToggle } from './sidebar';

describe('Sidebar Utils', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('sidebarToggle', () => {
    it('should toggle sidebar class and update localStorage on button click', () => {
      document.body.innerHTML = `
        <button id="sidebarToggle"></button>
      `;

      const mockSetItem = vi.fn();
      Object.defineProperty(global, 'localStorage', {
        value: {
          setItem: mockSetItem,
        },
        writable: true,
      });

      sidebarToggle();

      const toggleButton = document.getElementById('sidebarToggle');
      toggleButton?.click();

      expect(document.body.classList.contains('pmf-admin-sidenav-toggled')).toBe(true);
      expect(mockSetItem).toHaveBeenCalledWith('sb|sidebar-toggle', 'true');

      toggleButton?.click();

      expect(document.body.classList.contains('pmf-admin-sidenav-toggled')).toBe(false);
      expect(mockSetItem).toHaveBeenCalledWith('sb|sidebar-toggle', 'false');
    });

    it('should not throw an error if sidebarToggle button is not found', () => {
      document.body.innerHTML = ``;

      expect(() => sidebarToggle()).not.toThrow();
    });
  });
});
