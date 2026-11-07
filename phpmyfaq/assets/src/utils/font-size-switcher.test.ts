import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { FontSizeSwitcher, initFontSizeSwitcher } from './font-size-switcher';

describe('FontSizeSwitcher', (): void => {
  let decreaseButton: HTMLButtonElement;
  let increaseButton: HTMLButtonElement;

  beforeEach((): void => {
    localStorage.clear();
    document.documentElement.removeAttribute('data-pmf-font-size');
    document.body.innerHTML = `
      <button type="button" id="font-size-decrease"></button>
      <button type="button" id="font-size-increase"></button>
    `;
    decreaseButton = document.getElementById('font-size-decrease') as HTMLButtonElement;
    increaseButton = document.getElementById('font-size-increase') as HTMLButtonElement;
  });

  afterEach((): void => {
    localStorage.clear();
    document.documentElement.removeAttribute('data-pmf-font-size');
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  test('applies the default size when nothing is stored', (): void => {
    new FontSizeSwitcher();

    expect(document.documentElement.getAttribute('data-pmf-font-size')).toBe('100');
    expect(localStorage.getItem('pmf-font-size')).toBe('100');
    expect(decreaseButton.disabled).toBe(false);
    expect(increaseButton.disabled).toBe(false);
  });

  test('applies a stored size', (): void => {
    localStorage.setItem('pmf-font-size', '110');

    new FontSizeSwitcher();

    expect(document.documentElement.getAttribute('data-pmf-font-size')).toBe('110');
  });

  test('falls back to the default for a stored value outside the allowed steps', (): void => {
    localStorage.setItem('pmf-font-size', '250');

    new FontSizeSwitcher();

    expect(document.documentElement.getAttribute('data-pmf-font-size')).toBe('100');
    expect(localStorage.getItem('pmf-font-size')).toBe('100');
  });

  test('increases one step per click and stops at 120', (): void => {
    new FontSizeSwitcher();

    increaseButton.click();
    expect(document.documentElement.getAttribute('data-pmf-font-size')).toBe('110');
    expect(localStorage.getItem('pmf-font-size')).toBe('110');

    increaseButton.click();
    expect(document.documentElement.getAttribute('data-pmf-font-size')).toBe('120');
    expect(increaseButton.disabled).toBe(true);
    expect(decreaseButton.disabled).toBe(false);

    increaseButton.click();
    expect(document.documentElement.getAttribute('data-pmf-font-size')).toBe('120');
  });

  test('decreases one step per click and stops at 80', (): void => {
    new FontSizeSwitcher();

    decreaseButton.click();
    expect(document.documentElement.getAttribute('data-pmf-font-size')).toBe('90');

    decreaseButton.click();
    expect(document.documentElement.getAttribute('data-pmf-font-size')).toBe('80');
    expect(decreaseButton.disabled).toBe(true);
    expect(increaseButton.disabled).toBe(false);

    decreaseButton.click();
    expect(document.documentElement.getAttribute('data-pmf-font-size')).toBe('80');
    expect(localStorage.getItem('pmf-font-size')).toBe('80');
  });

  test('keeps the attribute set by the inline head script when it matches the stored value', (): void => {
    localStorage.setItem('pmf-font-size', '90');
    document.documentElement.setAttribute('data-pmf-font-size', '90');
    const setAttribute = vi.spyOn(document.documentElement, 'setAttribute');

    new FontSizeSwitcher();

    expect(setAttribute).not.toHaveBeenCalledWith('data-pmf-font-size', expect.anything());
  });

  test('works without buttons in the DOM', (): void => {
    document.body.innerHTML = '';

    expect(() => new FontSizeSwitcher()).not.toThrow();
    expect(document.documentElement.getAttribute('data-pmf-font-size')).toBe('100');
  });

  test('sanitize accepts only the allowed steps', (): void => {
    expect(FontSizeSwitcher.sanitize('80')).toBe(80);
    expect(FontSizeSwitcher.sanitize(120)).toBe(120);
    expect(FontSizeSwitcher.sanitize('95')).toBe(100);
    expect(FontSizeSwitcher.sanitize('abc')).toBe(100);
    expect(FontSizeSwitcher.sanitize(null)).toBe(100);
  });

  test('initFontSizeSwitcher returns an instance', (): void => {
    expect(initFontSizeSwitcher()).toBeInstanceOf(FontSizeSwitcher);
  });
});
