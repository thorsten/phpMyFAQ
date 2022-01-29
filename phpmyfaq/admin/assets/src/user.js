/**
 * JavaScript functions for user frontend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-05-02
 */

/**
 * Returns the user data
 *
 * @param {string} userId
 * @return {object}
 */
const getUserRights = (userId) => {};

/**
 *
 * @param {string} userId
 */
const getUserData = (userId) => {};

/**
 *
 * @param {string} userId
 */
export const updateUser = (userId) => {
  getUserData(userId);
  getUserRights(userId);
};
