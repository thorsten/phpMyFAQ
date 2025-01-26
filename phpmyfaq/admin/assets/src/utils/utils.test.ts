import { describe, it, expect, vi, afterEach } from 'vitest';
import { selectAll, unSelectAll, formatBytes, initializeTooltips } from './utils';
import { Tooltip } from 'bootstrap';

vi.mock('bootstrap', () => ({
  Tooltip: vi.fn(),
}));

describe('Utils', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('selectAll', () => {
    it('should select all options in the select element', () => {
      document.body.innerHTML = `
        <select id="testSelect" multiple>
          <option value="1">Option 1</option>
          <option value="2">Option 2</option>
        </select>
      `;

      selectAll('testSelect');

      const selectElement = document.getElementById('testSelect') as HTMLSelectElement;
      for (const option of selectElement.options) {
        expect(option.selected).toBe(true);
      }
    });
  });

  describe('unSelectAll', () => {
    it('should unselect all options in the select element', () => {
      document.body.innerHTML = `
        <select id="testSelect" multiple>
          <option value="1" selected>Option 1</option>
          <option value="2" selected>Option 2</option>
        </select>
      `;

      unSelectAll('testSelect');

      const selectElement = document.getElementById('testSelect') as HTMLSelectElement;
      for (const option of selectElement.options) {
        expect(option.selected).toBe(false);
      }
    });
  });

  describe('formatBytes', () => {
    it('should format bytes correctly', () => {
      expect(formatBytes(1024)).toBe('1 KiB');
      expect(formatBytes(1048576)).toBe('1 MiB');
      expect(formatBytes(0)).toBe('0 Bytes');
    });
  });

  describe('initializeTooltips', () => {
    it('should initialize tooltips', () => {
      document.body.innerHTML = `
        <div data-bs-toggle="tooltip" title="Tooltip text"></div>
      `;

      initializeTooltips();

      expect(vi.mocked(Tooltip)).toHaveBeenCalled();
    });
  });
});
