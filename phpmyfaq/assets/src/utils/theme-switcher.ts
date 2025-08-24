/**
 * Theme switcher functionality for dark/light mode
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

  private toggleButton: HTMLButtonElement | null = null;
  private lightIcon: HTMLElement | null = null;
  private darkIcon: HTMLElement | null = null;

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
   * Set up event listeners for the theme toggle button
   */
  private setupEventListeners(): void {
    this.toggleButton = document.getElementById('theme-toggle') as HTMLButtonElement;
    this.lightIcon = document.getElementById('theme-icon-light');
    this.darkIcon = document.getElementById('theme-icon-dark');

    if (this.toggleButton) {
      this.toggleButton.addEventListener('click', () => {
        this.toggleTheme();
      });
    }
  }

  /**
   * Toggle between light and dark theme
   */
  private toggleTheme(): void {
    const currentTheme = this.getCurrentTheme();
    const newTheme = currentTheme === ThemeSwitcher.LIGHT_THEME ? ThemeSwitcher.DARK_THEME : ThemeSwitcher.LIGHT_THEME;

    this.setTheme(newTheme);
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
   * Update button state and icons
   */
  private updateButtonState(): void {
    const currentTheme = this.getCurrentTheme();
    const isDark = currentTheme === ThemeSwitcher.DARK_THEME;

    if (this.lightIcon && this.darkIcon) {
      if (isDark) {
        this.lightIcon.style.display = 'inline-block';
        this.darkIcon.style.display = 'none';
      } else {
        this.lightIcon.style.display = 'none';
        this.darkIcon.style.display = 'inline-block';
      }
    }

    if (this.toggleButton) {
      this.toggleButton.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
      this.toggleButton.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');
    }
  }

  /**
   * Listen for system theme changes
   */
  public watchSystemTheme(): void {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
      if (!localStorage.getItem(ThemeSwitcher.STORAGE_KEY)) {
        const newTheme = e.matches ? ThemeSwitcher.DARK_THEME : ThemeSwitcher.LIGHT_THEME;
        this.setTheme(newTheme);
      }
    });
  }
}

// Initialize the theme switcher when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  const themeSwitcher = new ThemeSwitcher();
  themeSwitcher.watchSystemTheme();
});
