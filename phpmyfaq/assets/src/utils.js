/**
 * One liner utility functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2020-12-13
 */

/**
 * Capitalize the first letter of a string
 * @param str
 * @returns {string}
 */
export const capitalize = (str) => `${str.charAt(0).toUpperCase()}${str.slice(1)}`;

/**
 * Convert a string to a number explicitly
 * @param str
 * @returns {number}
 */
export const toNumber = (str) => Number(str);

/**
 * Check if an array contains any items
 * @param arr
 * @returns {boolean}
 */
export const isNotEmpty = (arr) => Array.isArray(arr) && arr.length > 0;

/**
 * Sort an array containing numbers
 * @param arr
 * @returns {*}
 */
export const sort = (arr) => arr.sort((a, b) => a - b);

/**
 * Get the value of a specified cookie
 * @param name
 * @returns {string}
 */
export const cookie = (name) => `; ${document.cookie}`.split(`; ${name}=`).pop().split(';').shift();

/**
 * Get the text that the user has selected
 * @returns {string}
 */
export const getSelectedText = () => window.getSelection().toString();

/**
 * Check if the current tab is in view / focus
 * @returns {boolean}
 */
export const isBrowserTabInView = () => document.hidden;
