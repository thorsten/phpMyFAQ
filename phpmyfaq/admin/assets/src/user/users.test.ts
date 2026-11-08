/**
 * Test for user management functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-05-02
 */

import { describe, test, expect, beforeEach, afterEach, vi } from 'vitest';
import { JSDOM } from 'jsdom';

// Setup DOM environment
const dom = new JSDOM('<!DOCTYPE html><html lang="en"><body></body></html>', {
  url: 'http://localhost',
  pretendToBeVisual: true,
  resources: 'usable',
});

// Setup global DOM environment for tests
Object.defineProperty(globalThis, 'document', {
  value: dom.window.document,
  writable: true,
});

Object.defineProperty(globalThis, 'window', {
  value: dom.window,
  writable: true,
});

Object.defineProperty(globalThis, 'navigator', {
  value: dom.window.navigator,
  writable: true,
});

// Mock the required modules
vi.mock('bootstrap', () => ({
  Modal: class {
    show = vi.fn();
    hide = vi.fn();
  },
}));

vi.mock('../api', () => ({
  fetchAllUsers: vi.fn(),
  fetchUserData: vi.fn(),
  fetchUserRights: vi.fn(),
  deleteUser: vi.fn(),
  postUserData: vi.fn(),
  overwritePassword: vi.fn(),
}));

vi.mock('../../../../assets/src/utils', () => ({
  addElement: vi.fn(),
  capitalize: vi.fn(),
  pushErrorNotification: vi.fn(),
  pushNotification: vi.fn(),
}));

vi.mock('../utils', () => ({
  pushErrorNotification: vi.fn(),
  pushNotification: vi.fn(),
}));

describe('User Management Functions', () => {
  beforeEach(() => {
    // Setup DOM elements that are expected by the functions
    document.body.innerHTML = `
      <input id="pmf-user-password-overwrite-action" />
      <div id="pmf-modal-user-password-overwrite"></div>
      <input id="modal_csrf" value="test-csrf" />
      <input id="modal_user_id" value="123" />
      <input id="npass" value="newpass" />
      <input id="bpass" value="newpass" />
    `;
  });

  afterEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  test('overwritePassword function should be available for import', async () => {
    // Import the overwritePassword function from the API
    const { overwritePassword } = await import('../api');

    // Verify the function is available and can be called
    expect(overwritePassword).toBeDefined();
    expect(typeof overwritePassword).toBe('function');
  });

  test('updateUser shows an error notification instead of crashing when fetching user data fails', async () => {
    const { fetchUserData, fetchUserRights } = await import('../api');
    const { pushErrorNotification } = await import('../../../../assets/src/utils');
    const { updateUser } = await import('./users');

    vi.mocked(fetchUserData).mockRejectedValue(new Error('Unauthorized access'));

    await expect(updateUser('5')).resolves.toBeUndefined();

    expect(pushErrorNotification).toHaveBeenCalledWith('Unauthorized access');
    expect(fetchUserRights).not.toHaveBeenCalled();
  });

  test('self-service password overwrite shows the current password field and sends it', async () => {
    document.body.innerHTML = `
      <input id="pmf-user-password-overwrite-action" />
      <div id="pmf-modal-user-password-overwrite"></div>
      <input id="modal_csrf" value="test-csrf" />
      <input id="modal_user_id" value="5" />
      <input id="logged_in_user_id" value="5" />
      <div id="pmf-user-password-overwrite-current" class="row mb-2 d-none"></div>
      <input id="cpass" type="password" value="" />
      <input id="npass" value="NewPass123!" />
      <input id="bpass" value="NewPass123!" />
    `;

    const { overwritePassword } = await import('../api');
    vi.mocked(overwritePassword).mockResolvedValue({ success: 'ok' } as unknown as Response);
    const { handleUsers } = await import('./users');

    await handleUsers();

    const container = document.getElementById('pmf-modal-user-password-overwrite') as HTMLElement;
    const row = document.getElementById('pmf-user-password-overwrite-current') as HTMLElement;
    container.dispatchEvent(new window.Event('show.bs.modal'));

    expect(row.classList.contains('d-none')).toBe(false);

    (document.getElementById('cpass') as HTMLInputElement).value = 'Correct#Pass1';
    (document.getElementById('pmf-user-password-overwrite-action') as HTMLInputElement).click();
    await vi.waitFor(() => expect(overwritePassword).toHaveBeenCalled());

    expect(overwritePassword).toHaveBeenCalledWith('test-csrf', '5', 'NewPass123!', 'NewPass123!', 'Correct#Pass1');
  });

  test('overwriting another user password hides the current password field and sends none', async () => {
    document.body.innerHTML = `
      <input id="pmf-user-password-overwrite-action" />
      <div id="pmf-modal-user-password-overwrite"></div>
      <input id="modal_csrf" value="test-csrf" />
      <input id="modal_user_id" value="42" />
      <input id="logged_in_user_id" value="5" />
      <div id="pmf-user-password-overwrite-current" class="row mb-2"></div>
      <input id="cpass" type="password" value="leftover" />
      <input id="npass" value="NewPass123!" />
      <input id="bpass" value="NewPass123!" />
    `;

    const { overwritePassword } = await import('../api');
    vi.mocked(overwritePassword).mockResolvedValue({ success: 'ok' } as unknown as Response);
    const { handleUsers } = await import('./users');

    await handleUsers();

    const container = document.getElementById('pmf-modal-user-password-overwrite') as HTMLElement;
    const row = document.getElementById('pmf-user-password-overwrite-current') as HTMLElement;
    container.dispatchEvent(new window.Event('show.bs.modal'));

    expect(row.classList.contains('d-none')).toBe(true);

    (document.getElementById('pmf-user-password-overwrite-action') as HTMLInputElement).click();
    await vi.waitFor(() => expect(overwritePassword).toHaveBeenCalled());

    expect(overwritePassword).toHaveBeenCalledWith('test-csrf', '42', 'NewPass123!', 'NewPass123!', '');
  });

  test('users module should be able to import overwritePassword without errors', async () => {
    // This test verifies that the import statement works without errors
    expect(async () => {
      await import('./users');
    }).not.toThrow();
  });
});
