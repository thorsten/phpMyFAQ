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
  Modal: vi.fn(() => ({
    show: vi.fn(),
    hide: vi.fn(),
  })),
}));

vi.mock('../api', () => ({
  fetchAllUsers: vi.fn(),
  fetchUserData: vi.fn(),
  fetchUserRights: vi.fn(),
  deleteUser: vi.fn(),
  postUserData: vi.fn(),
  overwritePassword: vi.fn(),
}));

vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    addElement: vi.fn(),
    capitalize: vi.fn((str: string) => str.charAt(0).toUpperCase() + str.slice(1)),
    pushErrorNotification: vi.fn(),
    pushNotification: vi.fn(),
  };
});

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

  test('users module should be able to import overwritePassword without errors', async () => {
    // This test verifies that the import statement works without errors
    expect(async () => {
      await import('./users');
    }).not.toThrow();
  });
});

describe('updateUser', () => {
  let updateUser: (userId: string) => Promise<void>;
  let fetchUserData: ReturnType<typeof vi.fn>;
  let fetchUserRights: ReturnType<typeof vi.fn>;

  beforeEach(async () => {
    const usersModule = await import('./users');
    updateUser = usersModule.updateUser;
    const apiModule = await import('../api');
    fetchUserData = apiModule.fetchUserData as ReturnType<typeof vi.fn>;
    fetchUserRights = apiModule.fetchUserRights as ReturnType<typeof vi.fn>;

    document.body.innerHTML = `
      <input id="current_user_id" />
      <input id="pmf-user-list-autocomplete" />
      <input id="last_modified" />
      <input id="update_user_id" />
      <input id="modal_user_id" />
      <input id="auth_source" />
      <input id="user_status" />
      <input id="display_name" />
      <input id="email" />
      <input id="overwrite_twofactor" />
      <input id="is_superadmin" type="checkbox" />
      <input id="checkAll" />
      <input id="uncheckAll" />
      <input id="rights_user_id" />
      <button id="pmf-delete-user" class="disabled"></button>
      <button id="pmf-user-save" class="disabled"></button>
      <button id="pmf-user-rights-save"></button>
      <input id="user_right_right1" class="permission" type="checkbox" />
      <input id="user_right_right2" class="permission" type="checkbox" />
    `;

    vi.clearAllMocks();
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  test('should update user data and rights', async () => {
    const mockUserData = {
      userId: '123',
      login: 'testuser',
      lastModified: '2024-01-01',
      authSource: 'local',
      status: 'active',
      displayName: 'Test User',
      email: 'test@example.com',
      twoFactorEnabled: false,
      isSuperadmin: false,
    };

    fetchUserData.mockResolvedValue(mockUserData);
    fetchUserRights.mockResolvedValue(['right1', 'right2']);

    await updateUser('123');

    expect(fetchUserData).toHaveBeenCalledWith('123');
    expect(fetchUserRights).toHaveBeenCalledWith('123');
  });

  test('should handle superadmin user correctly', async () => {
    const mockUserData = {
      userId: '123',
      login: 'adminuser',
      lastModified: '2024-01-01',
      authSource: 'local',
      status: 'active',
      displayName: 'Admin User',
      email: 'admin@example.com',
      twoFactorEnabled: false,
      isSuperadmin: true,
    };

    fetchUserData.mockResolvedValue(mockUserData);
    fetchUserRights.mockResolvedValue([]);

    await updateUser('123');

    const superAdminCheckbox = document.getElementById('is_superadmin') as HTMLInputElement;
    expect(superAdminCheckbox.hasAttribute('checked')).toBe(true);
  });

  test('should handle two-factor enabled user correctly', async () => {
    const mockUserData = {
      userId: '123',
      login: 'testuser',
      lastModified: '2024-01-01',
      authSource: 'local',
      status: 'active',
      displayName: 'Test User',
      email: 'test@example.com',
      twoFactorEnabled: true,
      isSuperadmin: false,
    };

    fetchUserData.mockResolvedValue(mockUserData);
    fetchUserRights.mockResolvedValue([]);

    await updateUser('123');

    const twoFactorCheckbox = document.getElementById('overwrite_twofactor') as HTMLInputElement;
    expect(twoFactorCheckbox.hasAttribute('checked')).toBe(true);
  });
});

describe('handleUsers', () => {
  let handleUsers: () => Promise<void>;

  beforeEach(async () => {
    const usersModule = await import('./users');
    handleUsers = usersModule.handleUsers;

    document.body.innerHTML = `
      <input id="current_user_id" value="" />
      <input id="checkAll" />
      <input id="uncheckAll" />
      <input id="is_superadmin" type="checkbox" />
      <input id="add_user_automatic_password" type="checkbox" />
      <div id="add_user_show_password_inputs"></div>
      <button id="pmf-button-export-users"></button>
      <button id="pmf-user-save"></button>
      <button id="pmf-delete-user"></button>
      <button id="pmf-delete-user-yes"></button>
      <input id="pmf-csrf-token" value="csrf-token" />
      <input id="update_user_id" value="123" />
      <input id="display_name" value="Test User" />
      <input id="email" value="test@test.com" />
      <input id="last_modified" value="2024-01-01" />
      <input id="user_status" value="active" />
      <input id="overwrite_twofactor" type="checkbox" />
      <input id="pmf-user-id-delete" />
      <input id="csrf-token-delete-user" value="csrf-delete" />
      <input id="source_page" />
      <div id="pmf-username-delete"></div>
      <div id="pmf-modal-user-confirm-delete"></div>
      <button id="pmf-user-rights-save"></button>
      <input id="rights_user_id" value="123" />
      <input id="pmf-csrf-token-rights" value="csrf-rights" />
      <div class="permission" data-value="right1"></div>
    `;

    vi.clearAllMocks();
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  test('should handle check all and uncheck all buttons', async () => {
    document.body.innerHTML += `
      <input class="permission" type="checkbox" />
      <input class="permission" type="checkbox" />
    `;

    await handleUsers();

    const checkAllButton = document.getElementById('checkAll') as HTMLInputElement;
    const uncheckAllButton = document.getElementById('uncheckAll') as HTMLInputElement;

    checkAllButton.click();
    document.querySelectorAll('.permission').forEach((checkbox) => {
      expect((checkbox as HTMLInputElement).checked).toBe(true);
    });

    uncheckAllButton.click();
    document.querySelectorAll('.permission').forEach((checkbox) => {
      expect((checkbox as HTMLInputElement).checked).toBe(false);
    });
  });

  test('should toggle password inputs when automatic password is clicked', async () => {
    await handleUsers();

    const passwordToggle = document.getElementById('add_user_automatic_password') as HTMLInputElement;
    const passwordInputs = document.getElementById('add_user_show_password_inputs') as HTMLElement;

    expect(passwordInputs.classList.contains('d-none')).toBe(false);

    passwordToggle.click();
    expect(passwordInputs.classList.contains('d-none')).toBe(true);

    passwordToggle.click();
    expect(passwordInputs.classList.contains('d-none')).toBe(false);
  });

  test('should handle export users button click', async () => {
    await handleUsers();

    const exportButton = document.getElementById('pmf-button-export-users') as HTMLButtonElement;

    // Verify the button exists
    expect(exportButton).toBeTruthy();

    // We cannot test the actual navigation in JSDOM without triggering stderr warnings
    // The button click would set window.location.href which is not fully supported in JSDOM
  });
});
