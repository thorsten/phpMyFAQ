/**
 * Unit tests for the FAQ Sidebar toggle functionality
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
 * @since     2026-01-24
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import { handleSidebarToggle } from './sidebar';

const STORAGE_KEY = 'pmf-sidebar-collapsed';

const createSidebarHTML = (): string => `
  <button type="button" id="pmf-sidebar-toggle">
    <i class="bi bi-chevron-right"></i>
  </button>
  <div class="row">
    <div class="col-md-8" id="pmf-content-column">
      <article>Content</article>
    </div>
    <div class="col-md-4" id="pmf-sidebar">
      <div>Sidebar content</div>
    </div>
  </div>
`;

describe('handleSidebarToggle', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    localStorage.clear();
    vi.clearAllMocks();
  });

  it('should return early when toggle button is missing', () => {
    document.body.innerHTML = `
      <div id="pmf-sidebar"></div>
      <div id="pmf-content-column"></div>
    `;

    handleSidebarToggle();

    const sidebar = document.getElementById('pmf-sidebar');
    expect(sidebar?.classList.contains('pmf-sidebar-collapsed')).toBe(false);
  });

  it('should return early when sidebar is missing', () => {
    document.body.innerHTML = `
      <button id="pmf-sidebar-toggle"></button>
      <div id="pmf-content-column"></div>
    `;

    handleSidebarToggle();

    const button = document.getElementById('pmf-sidebar-toggle');
    expect(button?.classList.contains('collapsed')).toBe(false);
  });

  it('should return early when content column is missing', () => {
    document.body.innerHTML = `
      <button id="pmf-sidebar-toggle"></button>
      <div id="pmf-sidebar"></div>
    `;

    handleSidebarToggle();

    const sidebar = document.getElementById('pmf-sidebar');
    expect(sidebar?.classList.contains('pmf-sidebar-collapsed')).toBe(false);
  });

  it('should not collapse sidebar when localStorage is empty', () => {
    document.body.innerHTML = createSidebarHTML();

    handleSidebarToggle();

    const sidebar = document.getElementById('pmf-sidebar');
    const contentColumn = document.getElementById('pmf-content-column');
    const toggleButton = document.getElementById('pmf-sidebar-toggle');

    expect(sidebar?.classList.contains('pmf-sidebar-collapsed')).toBe(false);
    expect(contentColumn?.classList.contains('pmf-content-expanded')).toBe(false);
    expect(toggleButton?.classList.contains('collapsed')).toBe(false);
  });

  it('should collapse sidebar when localStorage has true', () => {
    document.body.innerHTML = createSidebarHTML();
    localStorage.setItem(STORAGE_KEY, 'true');

    handleSidebarToggle();

    const sidebar = document.getElementById('pmf-sidebar');
    const contentColumn = document.getElementById('pmf-content-column');
    const toggleButton = document.getElementById('pmf-sidebar-toggle');

    expect(sidebar?.classList.contains('pmf-sidebar-collapsed')).toBe(true);
    expect(contentColumn?.classList.contains('pmf-content-expanded')).toBe(true);
    expect(toggleButton?.classList.contains('collapsed')).toBe(true);
  });

  it('should update icon to chevron-left when collapsed from localStorage', () => {
    document.body.innerHTML = createSidebarHTML();
    localStorage.setItem(STORAGE_KEY, 'true');

    handleSidebarToggle();

    const icon = document.querySelector('#pmf-sidebar-toggle i');
    expect(icon?.classList.contains('bi-chevron-left')).toBe(true);
    expect(icon?.classList.contains('bi-chevron-right')).toBe(false);
  });

  it('should toggle sidebar collapsed state on button click', () => {
    document.body.innerHTML = createSidebarHTML();

    handleSidebarToggle();

    const toggleButton = document.getElementById('pmf-sidebar-toggle') as HTMLButtonElement;
    const sidebar = document.getElementById('pmf-sidebar');
    const contentColumn = document.getElementById('pmf-content-column');

    // Click to collapse
    toggleButton.click();

    expect(sidebar?.classList.contains('pmf-sidebar-collapsed')).toBe(true);
    expect(contentColumn?.classList.contains('pmf-content-expanded')).toBe(true);
    expect(toggleButton.classList.contains('collapsed')).toBe(true);
    expect(localStorage.getItem(STORAGE_KEY)).toBe('true');

    // Click to expand
    toggleButton.click();

    expect(sidebar?.classList.contains('pmf-sidebar-collapsed')).toBe(false);
    expect(contentColumn?.classList.contains('pmf-content-expanded')).toBe(false);
    expect(toggleButton.classList.contains('collapsed')).toBe(false);
    expect(localStorage.getItem(STORAGE_KEY)).toBe('false');
  });

  it('should update icon on toggle', () => {
    document.body.innerHTML = createSidebarHTML();

    handleSidebarToggle();

    const toggleButton = document.getElementById('pmf-sidebar-toggle') as HTMLButtonElement;
    const icon = document.querySelector('#pmf-sidebar-toggle i');

    // Initial state - chevron-right
    expect(icon?.classList.contains('bi-chevron-right')).toBe(true);

    // Click to collapse - should show chevron-left
    toggleButton.click();
    expect(icon?.classList.contains('bi-chevron-left')).toBe(true);
    expect(icon?.classList.contains('bi-chevron-right')).toBe(false);

    // Click to expand - should show chevron-right
    toggleButton.click();
    expect(icon?.classList.contains('bi-chevron-right')).toBe(true);
    expect(icon?.classList.contains('bi-chevron-left')).toBe(false);
  });

  it('should handle button without icon gracefully', () => {
    document.body.innerHTML = `
      <button type="button" id="pmf-sidebar-toggle"></button>
      <div class="row">
        <div class="col-md-8" id="pmf-content-column"></div>
        <div class="col-md-4" id="pmf-sidebar"></div>
      </div>
    `;

    handleSidebarToggle();

    const toggleButton = document.getElementById('pmf-sidebar-toggle') as HTMLButtonElement;

    // Should not throw when clicking without icon
    expect(() => toggleButton.click()).not.toThrow();
  });

  it('should prevent default event behavior on click', () => {
    document.body.innerHTML = createSidebarHTML();

    handleSidebarToggle();

    const toggleButton = document.getElementById('pmf-sidebar-toggle') as HTMLButtonElement;
    const clickEvent = new MouseEvent('click', { bubbles: true, cancelable: true });
    const preventDefaultSpy = vi.spyOn(clickEvent, 'preventDefault');

    toggleButton.dispatchEvent(clickEvent);

    expect(preventDefaultSpy).toHaveBeenCalled();
  });

  it('should persist collapsed state across multiple toggles', () => {
    document.body.innerHTML = createSidebarHTML();

    handleSidebarToggle();

    const toggleButton = document.getElementById('pmf-sidebar-toggle') as HTMLButtonElement;

    // Toggle multiple times
    toggleButton.click(); // collapse
    expect(localStorage.getItem(STORAGE_KEY)).toBe('true');

    toggleButton.click(); // expand
    expect(localStorage.getItem(STORAGE_KEY)).toBe('false');

    toggleButton.click(); // collapse
    expect(localStorage.getItem(STORAGE_KEY)).toBe('true');
  });
});
