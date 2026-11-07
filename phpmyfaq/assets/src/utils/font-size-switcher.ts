/**
 * Font size switcher: lets visitors scale the base font size in fixed steps
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-11-07
 */

export class FontSizeSwitcher {
  public static readonly STORAGE_KEY = 'pmf-font-size';
  public static readonly FONT_SIZE_ATTRIBUTE = 'data-pmf-font-size';
  /** Allowed base font sizes in percent, smallest first. */
  public static readonly STEPS: readonly number[] = [80, 90, 100, 110, 120];
  public static readonly DEFAULT_SIZE = 100;

  private decreaseButton: HTMLButtonElement | null = null;
  private increaseButton: HTMLButtonElement | null = null;

  constructor() {
    this.init();
  }

  /**
   * Normalize an arbitrary stored value to one of the allowed steps; unknown values fall back to the default.
   */
  public static sanitize(value: string | number | null): number {
    const parsed = typeof value === 'number' ? value : Number.parseInt(value ?? '', 10);

    return FontSizeSwitcher.STEPS.includes(parsed) ? parsed : FontSizeSwitcher.DEFAULT_SIZE;
  }

  private init(): void {
    this.setupEventListeners();
    this.applyInitialFontSize();
    this.updateButtonState();
  }

  private setupEventListeners(): void {
    this.decreaseButton = document.getElementById('font-size-decrease') as HTMLButtonElement | null;
    this.increaseButton = document.getElementById('font-size-increase') as HTMLButtonElement | null;

    if (this.decreaseButton) {
      this.decreaseButton.addEventListener('click', () => {
        this.step(-1);
      });
    }

    if (this.increaseButton) {
      this.increaseButton.addEventListener('click', () => {
        this.step(1);
      });
    }
  }

  /**
   * Move one step up or down the scale; at either end the size stays put.
   */
  private step(direction: -1 | 1): void {
    const steps = FontSizeSwitcher.STEPS;
    const index = steps.indexOf(this.getCurrentFontSize());
    const nextIndex = Math.min(steps.length - 1, Math.max(0, index + direction));

    this.setFontSize(steps[nextIndex]);
  }

  private setFontSize(size: number): void {
    const value = String(size);

    if (document.documentElement.getAttribute(FontSizeSwitcher.FONT_SIZE_ATTRIBUTE) !== value) {
      document.documentElement.setAttribute(FontSizeSwitcher.FONT_SIZE_ATTRIBUTE, value);
    }

    localStorage.setItem(FontSizeSwitcher.STORAGE_KEY, value);
    this.updateButtonState();
  }

  private getCurrentFontSize(): number {
    return FontSizeSwitcher.sanitize(document.documentElement.getAttribute(FontSizeSwitcher.FONT_SIZE_ATTRIBUTE));
  }

  /**
   * Apply the stored preference; the inline head script normally did this already, so this only
   * covers the case where that script did not run.
   */
  private applyInitialFontSize(): void {
    this.setFontSize(FontSizeSwitcher.sanitize(localStorage.getItem(FontSizeSwitcher.STORAGE_KEY)));
  }

  /**
   * Disable the button that cannot move any further.
   */
  private updateButtonState(): void {
    const current = this.getCurrentFontSize();
    const steps = FontSizeSwitcher.STEPS;

    if (this.decreaseButton) {
      this.decreaseButton.disabled = current <= steps[0];
    }

    if (this.increaseButton) {
      this.increaseButton.disabled = current >= steps[steps.length - 1];
    }
  }
}

/**
 * Start the font size switcher once the DOM is ready.
 */
export const initFontSizeSwitcher = (): FontSizeSwitcher => new FontSizeSwitcher();
