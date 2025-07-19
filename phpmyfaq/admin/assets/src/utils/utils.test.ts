import { describe, it, expect, vi, afterEach } from 'vitest';
import { selectAll, unSelectAll, formatBytes, initializeTooltips, normalizeLanguageCode } from './utils';
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

  describe('normalizeLanguageCode', () => {
    it('should return the same value for empty input', () => {
      expect(normalizeLanguageCode('')).toBe('');
      expect(normalizeLanguageCode(undefined as any)).toBe(undefined);
      expect(normalizeLanguageCode(null as any)).toBe(null);
    });

    it('should replace underscores with hyphens', () => {
      expect(normalizeLanguageCode('pt_br')).toBe('pt-BR');
      expect(normalizeLanguageCode('en_us')).toBe('en-US');
    });

    it('should capitalize region part', () => {
      expect(normalizeLanguageCode('de_at')).toBe('de-AT');
      expect(normalizeLanguageCode('fr_ca')).toBe('fr-CA');
    });

    it('should handle already normalized codes', () => {
      expect(normalizeLanguageCode('pt-BR')).toBe('pt-BR');
      expect(normalizeLanguageCode('en-US')).toBe('en-US');
    });

    it('should not change codes without region', () => {
      expect(normalizeLanguageCode('de')).toBe('de');
      expect(normalizeLanguageCode('fr')).toBe('fr');
    });

    it('should handle mixed case input', () => {
      expect(normalizeLanguageCode('pt_br')).toBe('pt-BR');
      expect(normalizeLanguageCode('pt_br')).toBe('pt-BR');
      expect(normalizeLanguageCode('pt_br')).toBe('pt-BR');
    });
  });
});
