/**
 * Theme switcher functionality for dark/light/high-contrast mode
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-08-24
 */

export class ThemeSwitcher {
  private static readonly STORAGE_KEY = 'pmf-theme';
  private static readonly THEME_ATTRIBUTE = 'data-bs-theme';
  private static readonly LIGHT_THEME = 'light';
  private static readonly DARK_THEME = 'dark';
  private static readonly HIGH_CONTRAST_THEME = 'high-contrast';

  private lightButton: HTMLButtonElement | null = null;
  private darkButton: HTMLButtonElement | null = null;
  private highContrastButton: HTMLButtonElement | null = null;

  constructor() {
    this.init();
  }

  /**
   * Initialize the theme switcher
   */
  private init(): void {
    this.setupEventListeners();
    this.applyStoredTheme();
    this.updateButtonState();
  }

  /**
   * Set up event listeners for the theme buttons
   */
  private setupEventListeners(): void {
    this.lightButton = document.getElementById('theme-light') as HTMLButtonElement;
    this.darkButton = document.getElementById('theme-dark') as HTMLButtonElement;
    this.highContrastButton = document.getElementById('theme-high-contrast') as HTMLButtonElement;

    if (this.lightButton) {
      this.lightButton.addEventListener('click', () => {
        this.setTheme(ThemeSwitcher.LIGHT_THEME);
      });
    }

    if (this.darkButton) {
      this.darkButton.addEventListener('click', () => {
        this.setTheme(ThemeSwitcher.DARK_THEME);
      });
    }

    if (this.highContrastButton) {
      this.highContrastButton.addEventListener('click', () => {
        this.setTheme(ThemeSwitcher.HIGH_CONTRAST_THEME);
      });
    }
  }

  /**
   * Set the theme
   */
  private setTheme(theme: string): void {
    document.documentElement.setAttribute(ThemeSwitcher.THEME_ATTRIBUTE, theme);
    localStorage.setItem(ThemeSwitcher.STORAGE_KEY, theme);
    this.updateButtonState();
  }

  /**
   * Get the current theme
   */
  private getCurrentTheme(): string {
    return document.documentElement.getAttribute(ThemeSwitcher.THEME_ATTRIBUTE) || ThemeSwitcher.LIGHT_THEME;
  }

  /**
   * Apply stored theme from localStorage
   */
  private applyStoredTheme(): void {
    const storedTheme = localStorage.getItem(ThemeSwitcher.STORAGE_KEY);

    if (storedTheme) {
      this.setTheme(storedTheme);
    } else {
      // Check for system preference
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      const defaultTheme = prefersDark ? ThemeSwitcher.DARK_THEME : ThemeSwitcher.LIGHT_THEME;
      this.setTheme(defaultTheme);
    }
  }

  /**
   * Update button state to show active theme
   */
  private updateButtonState(): void {
    const currentTheme = this.getCurrentTheme();

    // Remove active class from all buttons
    if (this.lightButton) {
      this.lightButton.classList.remove('active');
    }
    if (this.darkButton) {
      this.darkButton.classList.remove('active');
    }
    if (this.highContrastButton) {
      this.highContrastButton.classList.remove('active');
    }

    // Add active class to the current theme button
    if (currentTheme === ThemeSwitcher.LIGHT_THEME && this.lightButton) {
      this.lightButton.classList.add('active');
    } else if (currentTheme === ThemeSwitcher.DARK_THEME && this.darkButton) {
      this.darkButton.classList.add('active');
    } else if (currentTheme === ThemeSwitcher.HIGH_CONTRAST_THEME && this.highContrastButton) {
      this.highContrastButton.classList.add('active');
    }
  }

  /**
   * Listen for system theme changes
   */
  public watchSystemTheme(): void {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event: MediaQueryListEvent): void => {
      if (!localStorage.getItem(ThemeSwitcher.STORAGE_KEY)) {
        const newTheme = event.matches ? ThemeSwitcher.DARK_THEME : ThemeSwitcher.LIGHT_THEME;
        this.setTheme(newTheme);
      }
    });
  }
}

// Initialize the theme switcher when DOM is loaded
document.addEventListener('DOMContentLoaded', (): void => {
  const themeSwitcher = new ThemeSwitcher();
  themeSwitcher.watchSystemTheme();
});
