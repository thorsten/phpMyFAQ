/**
 * Theme switcher functionality for dark/light/high-contrast mode
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-08-24
 */

export class ThemeSwitcher {
  private static readonly STORAGE_KEY = 'pmf-theme';
  private static readonly THEME_ATTRIBUTE = 'data-bs-theme';
  private static readonly DEFAULT_THEME_ATTRIBUTE = 'data-pmf-default-theme';
  private static readonly ALLOW_USER_ATTRIBUTE = 'data-pmf-allow-user-theme';
  private static readonly LIGHT_THEME = 'light';
  private static readonly DARK_THEME = 'dark';
  private static readonly HIGH_CONTRAST_THEME = 'high-contrast';
  private static readonly AUTO_MODE = 'auto';

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
    this.applyInitialTheme();
    this.updateButtonState();
  }

  /**
   * Whether visitors are allowed to change the layout mode (admin configurable)
   */
  private isUserThemeAllowed(): boolean {
    return document.documentElement.getAttribute(ThemeSwitcher.ALLOW_USER_ATTRIBUTE) !== 'false';
  }

  /**
   * The admin-configured default layout mode ('auto', 'light', 'dark', 'high-contrast')
   */
  private getConfiguredDefaultMode(): string {
    return document.documentElement.getAttribute(ThemeSwitcher.DEFAULT_THEME_ATTRIBUTE) || ThemeSwitcher.AUTO_MODE;
  }

  /**
   * Resolve a layout mode to a concrete theme, expanding 'auto' to the system preference
   */
  private resolveMode(mode: string): string {
    if (mode === ThemeSwitcher.AUTO_MODE) {
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      return prefersDark ? ThemeSwitcher.DARK_THEME : ThemeSwitcher.LIGHT_THEME;
    }

    return mode;
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
   * Set the theme. The choice is only persisted when visitors are allowed to change the mode.
   */
  private setTheme(theme: string): void {
    document.documentElement.setAttribute(ThemeSwitcher.THEME_ATTRIBUTE, theme);

    if (this.isUserThemeAllowed()) {
      localStorage.setItem(ThemeSwitcher.STORAGE_KEY, theme);
    }

    this.updateButtonState();
  }

  /**
   * Get the current theme
   */
  private getCurrentTheme(): string {
    return document.documentElement.getAttribute(ThemeSwitcher.THEME_ATTRIBUTE) || ThemeSwitcher.LIGHT_THEME;
  }

  /**
   * Apply the initial theme based on the admin configuration and the visitor preference.
   *
   * When visitors may change the mode, a stored preference wins over the admin default.
   * When they may not, the admin-configured default is enforced and any stored value is cleared.
   */
  private applyInitialTheme(): void {
    if (!this.isUserThemeAllowed()) {
      localStorage.removeItem(ThemeSwitcher.STORAGE_KEY);
      this.setTheme(this.resolveMode(this.getConfiguredDefaultMode()));
      return;
    }

    const storedTheme = localStorage.getItem(ThemeSwitcher.STORAGE_KEY);

    if (storedTheme) {
      this.setTheme(storedTheme);
    } else {
      this.setTheme(this.resolveMode(this.getConfiguredDefaultMode()));
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
   * Listen for system theme changes.
   *
   * System changes are only applied while the effective mode is 'auto' and the visitor
   * has not explicitly chosen a theme.
   */
  public watchSystemTheme(): void {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event: MediaQueryListEvent): void => {
      if (this.getConfiguredDefaultMode() !== ThemeSwitcher.AUTO_MODE) {
        return;
      }

      if (this.isUserThemeAllowed() && localStorage.getItem(ThemeSwitcher.STORAGE_KEY)) {
        return;
      }

      const newTheme = event.matches ? ThemeSwitcher.DARK_THEME : ThemeSwitcher.LIGHT_THEME;
      document.documentElement.setAttribute(ThemeSwitcher.THEME_ATTRIBUTE, newTheme);
      this.updateButtonState();
    });
  }
}

// Initialize the theme switcher when DOM is loaded
document.addEventListener('DOMContentLoaded', (): void => {
  const themeSwitcher = new ThemeSwitcher();
  themeSwitcher.watchSystemTheme();
});
