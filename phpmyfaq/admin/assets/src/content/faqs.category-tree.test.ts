import { describe, it, expect, vi, beforeEach } from 'vitest';
import { handleFaqCategoryTree } from './faqs.category-tree';
import { getCategoryPermissions } from './faqs';

vi.mock('./faqs', () => ({
  getCategoryPermissions: vi.fn(),
}));

const treeMarkup = `
  <input type="search" id="pmf-faq-category-filter" />
  <div id="pmf-faq-category-tree">
    <div class="form-check pmf-category-tree-item" data-pmf-category-name="general">
      <input class="form-check-input" type="checkbox" name="categories[]" value="1" id="faq-category-1" checked>
      <label for="faq-category-1">General</label>
    </div>
    <div class="form-check pmf-category-tree-item" data-pmf-category-name="installation">
      <input class="form-check-input" type="checkbox" name="categories[]" value="2" id="faq-category-2">
      <label for="faq-category-2">Installation</label>
    </div>
  </div>
`;

describe('faqs.category-tree', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should do nothing when the tree is missing', () => {
    document.body.innerHTML = '<input type="search" id="pmf-faq-category-filter" />';

    handleFaqCategoryTree();

    const filter = document.getElementById('pmf-faq-category-filter') as HTMLInputElement;
    filter.value = 'general';
    filter.dispatchEvent(new Event('input'));

    expect(getCategoryPermissions).not.toHaveBeenCalled();
  });

  it('should filter categories by name and restore them on empty query', () => {
    document.body.innerHTML = treeMarkup;

    handleFaqCategoryTree();

    const filter = document.getElementById('pmf-faq-category-filter') as HTMLInputElement;
    const items = document.querySelectorAll<HTMLElement>('.pmf-category-tree-item');

    filter.value = 'instal';
    filter.dispatchEvent(new Event('input'));

    expect(items[0].classList.contains('d-none')).toBe(true);
    expect(items[1].classList.contains('d-none')).toBe(false);

    filter.value = '';
    filter.dispatchEvent(new Event('input'));

    expect(items[0].classList.contains('d-none')).toBe(false);
    expect(items[1].classList.contains('d-none')).toBe(false);
  });

  it('should swallow Enter in the filter box so it cannot submit the FAQ form', () => {
    document.body.innerHTML = treeMarkup;

    handleFaqCategoryTree();

    const filter = document.getElementById('pmf-faq-category-filter') as HTMLInputElement;
    const enterEvent = new KeyboardEvent('keydown', { key: 'Enter', cancelable: true });
    filter.dispatchEvent(enterEvent);

    expect(enterEvent.defaultPrevented).toBe(true);

    const otherKey = new KeyboardEvent('keydown', { key: 'a', cancelable: true });
    filter.dispatchEvent(otherKey);
    expect(otherKey.defaultPrevented).toBe(false);
  });

  it('should sync category permissions with the checked categories', () => {
    document.body.innerHTML = treeMarkup;

    handleFaqCategoryTree();

    const checkbox = document.getElementById('faq-category-2') as HTMLInputElement;
    checkbox.checked = true;
    checkbox.dispatchEvent(new Event('change', { bubbles: true }));

    expect(getCategoryPermissions).toHaveBeenCalledWith(['1', '2']);
  });

  it('should not fetch permissions when nothing is checked', () => {
    document.body.innerHTML = treeMarkup;

    handleFaqCategoryTree();

    const first = document.getElementById('faq-category-1') as HTMLInputElement;
    first.checked = false;
    first.dispatchEvent(new Event('change', { bubbles: true }));

    expect(getCategoryPermissions).not.toHaveBeenCalled();
  });
});
