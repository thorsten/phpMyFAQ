import { describe, it, expect, vi, beforeEach } from 'vitest';
import { handleCategoryOverview } from './category-overview';
import { categorySortables } from './category';
import type Sortable from 'sortablejs';

const { popoverInit } = vi.hoisted(() => ({ popoverInit: vi.fn() }));
vi.mock('bootstrap', () => ({
  Modal: class {
    show = vi.fn();
    hide = vi.fn();
  },
  Popover: class {
    static getOrCreateInstance = popoverInit;
  },
}));
vi.mock('sortablejs', () => ({
  // Regular function so `new Sortable(...)` works — arrow functions are not constructible
  default: vi.fn().mockImplementation(function () {
    return { option: vi.fn() };
  }),
}));
vi.mock('../api');
// Transitively imported via './category'; mock to avoid loading the real translator
vi.mock('../translation/translator', () => ({ Translator: vi.fn() }));

const setupTree = (): void => {
  document.body.innerHTML = `
    <input id="pmf-category-filter" type="search" />
    <button id="pmf-category-expand-all" type="button"></button>
    <button id="pmf-category-collapse-all" type="button"></button>
    <div id="pmf-category-tree" class="list-group nested-sortable">
      <div class="list-group-item nested-1" id="pmf-category-1" data-pmf-catid="1" data-pmf-category-name="hardware">
        <button class="pmf-category-toggle" data-pmf-collapse-id="1" aria-expanded="true">
          <i class="bi bi-chevron-down"></i>
        </button>
        <button class="badge" data-bs-toggle="popover" data-bs-content="&lt;ul&gt;&lt;/ul&gt;">1/2</button>
        <div class="list-group nested-sortable" data-pmf-children-of="1">
          <div class="list-group-item nested-2" id="pmf-category-2" data-pmf-catid="2"
               data-pmf-category-name="printers">
            <div class="list-group nested-sortable" data-pmf-children-of="2"></div>
          </div>
        </div>
      </div>
      <div class="list-group-item nested-1" id="pmf-category-3" data-pmf-catid="3" data-pmf-category-name="software">
        <div class="list-group nested-sortable" data-pmf-children-of="3"></div>
      </div>
    </div>
  `;
};

describe('handleCategoryOverview', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    localStorage.clear();
    categorySortables.length = 0;
  });

  it('should do nothing when the overview toolbar is absent', () => {
    document.body.innerHTML = '<div id="pmf-category-tree"></div>';

    expect(() => handleCategoryOverview()).not.toThrow();
    expect(popoverInit).not.toHaveBeenCalled();
  });

  it('should initialize popovers on translation badges', () => {
    setupTree();

    handleCategoryOverview();

    expect(popoverInit).toHaveBeenCalledTimes(1);
  });

  it('should collapse a node via its toggle and persist the state', () => {
    setupTree();
    handleCategoryOverview();

    (document.querySelector('.pmf-category-toggle') as HTMLButtonElement).click();

    const container = document.querySelector('[data-pmf-children-of="1"]') as HTMLElement;
    expect(container.classList.contains('d-none')).toBe(true);
    expect(JSON.parse(localStorage.getItem('pmf-admin-category-collapsed') || '[]')).toEqual(['1']);

    (document.querySelector('.pmf-category-toggle') as HTMLButtonElement).click();
    expect(container.classList.contains('d-none')).toBe(false);
    expect(JSON.parse(localStorage.getItem('pmf-admin-category-collapsed') || '[]')).toEqual([]);
  });

  it('should re-apply the persisted collapsed state on init', () => {
    localStorage.setItem('pmf-admin-category-collapsed', JSON.stringify(['1']));
    setupTree();

    handleCategoryOverview();

    expect((document.querySelector('[data-pmf-children-of="1"]') as HTMLElement).classList.contains('d-none')).toBe(
      true
    );

    const toggle = document.querySelector('[data-pmf-collapse-id="1"]') as HTMLElement;
    expect(toggle.getAttribute('aria-expanded')).toBe('false');
    expect(toggle.querySelector('i')?.classList.contains('bi-chevron-right')).toBe(true);
  });

  it('should expand and collapse all nodes via the toolbar buttons', () => {
    setupTree();
    handleCategoryOverview();

    (document.getElementById('pmf-category-collapse-all') as HTMLButtonElement).click();
    expect((document.querySelector('[data-pmf-children-of="1"]') as HTMLElement).classList.contains('d-none')).toBe(
      true
    );

    (document.getElementById('pmf-category-expand-all') as HTMLButtonElement).click();
    expect((document.querySelector('[data-pmf-children-of="1"]') as HTMLElement).classList.contains('d-none')).toBe(
      false
    );
    expect(JSON.parse(localStorage.getItem('pmf-admin-category-collapsed') || '[]')).toEqual([]);
  });

  it('should filter rows, revealing matches with their ancestors and descendants', () => {
    setupTree();
    handleCategoryOverview();

    const filter = document.getElementById('pmf-category-filter') as HTMLInputElement;
    filter.value = 'printers';
    filter.dispatchEvent(new Event('input'));

    expect(document.getElementById('pmf-category-2')?.classList.contains('d-none')).toBe(false);
    expect(document.getElementById('pmf-category-1')?.classList.contains('d-none')).toBe(false); // ancestor
    expect(document.getElementById('pmf-category-3')?.classList.contains('d-none')).toBe(true); // non-match
  });

  it('should restore all rows and the persisted collapse state when the filter is cleared', () => {
    localStorage.setItem('pmf-admin-category-collapsed', JSON.stringify(['1']));
    setupTree();
    handleCategoryOverview();

    const filter = document.getElementById('pmf-category-filter') as HTMLInputElement;
    filter.value = 'printers';
    filter.dispatchEvent(new Event('input'));
    // filtering force-expands the collapsed ancestor
    expect((document.querySelector('[data-pmf-children-of="1"]') as HTMLElement).classList.contains('d-none')).toBe(
      false
    );

    filter.value = '';
    filter.dispatchEvent(new Event('input'));

    expect(document.getElementById('pmf-category-3')?.classList.contains('d-none')).toBe(false);
    expect((document.querySelector('[data-pmf-children-of="1"]') as HTMLElement).classList.contains('d-none')).toBe(
      true
    ); // persisted collapse restored
  });

  it('should disable sorting while a filter query is active and re-enable it after', () => {
    setupTree();
    const optionSpy = vi.fn();
    categorySortables.push({ option: optionSpy } as unknown as Sortable);
    handleCategoryOverview();

    const filter = document.getElementById('pmf-category-filter') as HTMLInputElement;
    filter.value = 'hard';
    filter.dispatchEvent(new Event('input'));
    expect(optionSpy).toHaveBeenCalledWith('disabled', true);

    filter.value = '';
    filter.dispatchEvent(new Event('input'));
    expect(optionSpy).toHaveBeenCalledWith('disabled', false);
  });

  it('should ignore collapse-all while a filter query is active', () => {
    setupTree();
    handleCategoryOverview();

    const filter = document.getElementById('pmf-category-filter') as HTMLInputElement;
    filter.value = 'printers';
    filter.dispatchEvent(new Event('input'));

    (document.getElementById('pmf-category-collapse-all') as HTMLButtonElement).click();

    expect((document.querySelector('[data-pmf-children-of="1"]') as HTMLElement).classList.contains('d-none')).toBe(
      false
    );
    expect(JSON.parse(localStorage.getItem('pmf-admin-category-collapsed') || '[]')).toEqual([]);
  });

  it('should prune persisted ids of categories that no longer exist', () => {
    localStorage.setItem('pmf-admin-category-collapsed', JSON.stringify(['1', '999']));
    setupTree();

    handleCategoryOverview();

    expect(JSON.parse(localStorage.getItem('pmf-admin-category-collapsed') || '[]')).toEqual(['1']);
  });
});
