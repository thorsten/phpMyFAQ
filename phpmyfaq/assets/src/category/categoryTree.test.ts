import { beforeEach, describe, expect, it } from 'vitest';
import { handleCategoryTree } from './categoryTree';

const buildDom = (): void => {
  document.body.innerHTML = `
    <button data-pmf-expand-all>Expand</button>
    <button data-pmf-collapse-all>Collapse</button>
    <ul class="pmf-category-tree pmf-category-tree--root">
      <li>
        <div class="pmf-category-tree__row">
          <button class="pmf-category-tree__toggle" aria-expanded="true" aria-controls="r1"></button>
        </div>
        <div id="r1">
          <ul class="pmf-category-tree pmf-category-tree__children">
            <li><div class="pmf-category-tree__row">
              <button class="pmf-category-tree__toggle" aria-expanded="true" aria-controls="r2"></button>
            </div><div id="r2">child</div></li>
          </ul>
        </div>
      </li>
    </ul>`;
};

const toggle = (controls: string): HTMLButtonElement =>
  document.querySelector(`[aria-controls="${controls}"]`) as HTMLButtonElement;

describe('handleCategoryTree', () => {
  beforeEach(buildDom);

  it('does nothing when there is no tree', () => {
    document.body.innerHTML = '<div></div>';
    expect(() => handleCategoryTree()).not.toThrow();
  });

  it('collapses every branch on init', () => {
    handleCategoryTree();
    expect(toggle('r1').getAttribute('aria-expanded')).toBe('false');
    expect(toggle('r2').getAttribute('aria-expanded')).toBe('false');
    expect((document.getElementById('r1') as HTMLElement).hidden).toBe(true);
    expect((document.getElementById('r2') as HTMLElement).hidden).toBe(true);
  });

  it('expands a branch when its toggle is clicked', () => {
    handleCategoryTree();
    toggle('r1').click();
    expect(toggle('r1').getAttribute('aria-expanded')).toBe('true');
    expect((document.getElementById('r1') as HTMLElement).hidden).toBe(false);
  });

  it('expands all and collapses all via the toolbar', () => {
    handleCategoryTree();
    (document.querySelector('[data-pmf-expand-all]') as HTMLButtonElement).click();
    expect(toggle('r1').getAttribute('aria-expanded')).toBe('true');
    expect(toggle('r2').getAttribute('aria-expanded')).toBe('true');

    (document.querySelector('[data-pmf-collapse-all]') as HTMLButtonElement).click();
    expect(toggle('r1').getAttribute('aria-expanded')).toBe('false');
    expect((document.getElementById('r2') as HTMLElement).hidden).toBe(true);
  });
});
