/**
 * Fetch wrapper with automatic 401 handling
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
 * @since     2026-01-17
 */

/**
 * Wrapper around fetch that handles 401 responses globally
 * When a 401 is encountered (session timeout), the user is redirected to log in
 *
 * @param url The URL to fetch
 * @param options Fetch options
 * @returns Promise<Response>
 */
export const fetchWrapper = async (url: string, options?: RequestInit): Promise<Response> => {
  const response = await fetch(url, options);

  // Handle 401 Unauthorized - session timeout
  if (response.status === 401) {
    // Store a flash message in sessionStorage to show after redirect
    sessionStorage.setItem('loginMessage', 'Your session has expired. Please log in again.');

    // Redirect to the login page
    window.location.href = './admin/login';

    // Throw error to stop further processing
    throw new Error('Session expired');
  }

  return response;
};

/**
 * JSON wrapper that uses fetchWrapper and returns parsed JSON
 *
 * @param url The URL to fetch
 * @param options Fetch options
 * @returns Promise with parsed JSON
 */
export const fetchJson = async (url: string, options?: RequestInit): Promise<unknown> => {
  const response = await fetchWrapper(url, options);
  return await response.json();
};
