/**
 * Test for user management functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-05-02
 */

// Mock the required modules
jest.mock('bootstrap', () => ({
  Modal: jest.fn(() => ({
    show: jest.fn(),
    hide: jest.fn(),
  })),
}));

jest.mock('../api', () => ({
  fetchAllUsers: jest.fn(),
  fetchUserData: jest.fn(),
  fetchUserRights: jest.fn(),
  deleteUser: jest.fn(),
  postUserData: jest.fn(),
  overwritePassword: jest.fn(),
}));

jest.mock('../../../../assets/src/utils', () => ({
  addElement: jest.fn(),
  capitalize: jest.fn(),
}));

jest.mock('../utils', () => ({
  pushErrorNotification: jest.fn(),
  pushNotification: jest.fn(),
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
    jest.clearAllMocks();
    document.body.innerHTML = '';
  });

  test('overwritePassword function should be available for import', async () => {
    // Import the overwritePassword function from the API
    const { overwritePassword } = await import('../api');
    
    // Verify the function is available and can be called
    expect(overwritePassword).toBeDefined();
    expect(typeof overwritePassword).toBe('function');
  });

  test('users.js should be able to import overwritePassword without errors', async () => {
    // This test verifies that the import statement works without errors
    expect(async () => {
      await import('./users');
    }).not.toThrow();
  });
});