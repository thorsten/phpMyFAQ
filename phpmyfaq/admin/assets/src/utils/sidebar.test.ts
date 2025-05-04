import { describe, it, expect, vi, afterEach } from 'vitest';
import { sidebarToggle } from './sidebar';

describe('Sidebar Utils', (): void => {
  afterEach((): void => {
    vi.restoreAllMocks();
  });

  describe('sidebarToggle', (): void => {
    it('should toggle sidebar class and update localStorage on button click', (): void => {
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

      const toggleButton: HTMLElement | null = document.getElementById('sidebarToggle');
      toggleButton?.click();

      expect(document.body.classList.contains('pmf-admin-sidenav-toggled')).toBe(true);
      expect(mockSetItem).toHaveBeenCalledWith('pmf-admin|sidebar-toggle', 'true');

      toggleButton?.click();

      expect(document.body.classList.contains('pmf-admin-sidenav-toggled')).toBe(false);
      expect(mockSetItem).toHaveBeenCalledWith('pmf-admin|sidebar-toggle', 'false');
    });

    it('should not throw an error if sidebarToggle button is not found', (): void => {
      document.body.innerHTML = ``;

      expect((): void => sidebarToggle()).not.toThrow();
    });
  });
});
