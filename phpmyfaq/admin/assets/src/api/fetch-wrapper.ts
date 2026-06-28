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

  // Only redirect on RFC 7807 ProblemDetails 401 — that's the kernel's
  // exception listener signalling an actual auth failure. Manual 401s from
  // controllers (e.g. CSRF mismatches) come back as application/json and
  // should surface to callers as normal errors.
  if (response.status === 401 && (response.headers.get('content-type') ?? '').includes('application/problem+json')) {
    sessionStorage.setItem('loginMessage', 'Your session has expired. Please log in again.');
    window.location.href = './login';
    throw new Error('Session expired');
  }

  return response;
};

/**
 * JSON wrapper that uses fetchWrapper and returns the parsed JSON body.
 *
 * The body is returned as the caller-supplied type `T` (defaulting to `unknown`),
 * so call sites can declare the expected envelope shape instead of casting the
 * result afterwards, e.g. `fetchJson<ApiResponse>(url, options)`.
 *
 * If the body is empty or not valid JSON — for example an HTML error page from
 * the web server rather than the application — a typed `{ error }` envelope is
 * returned instead of letting an unhandled `SyntaxError` escape to the caller.
 *
 * @param url The URL to fetch
 * @param options Fetch options
 * @returns Promise resolving to the parsed JSON body typed as `T`
 */
export const fetchJson = async <T = unknown>(url: string, options?: RequestInit): Promise<T> => {
  const response = await fetchWrapper(url, options);

  try {
    return (await response.json()) as T;
  } catch {
    return { error: response.statusText || 'Invalid server response' } as T;
  }
};
