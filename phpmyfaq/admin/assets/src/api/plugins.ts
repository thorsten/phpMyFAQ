/**
 * Plugin API calls
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-07
 */

import { Response } from '../interfaces';

/**
 * Toggle plugin status
 *
 * @param name
 * @param active
 * @param csrfToken
 */
export const togglePluginStatus = async (name: string, active: boolean, csrfToken: string): Promise<Response> => {
    const response = await fetch('api/plugin/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
        },
        body: JSON.stringify({
            name,
            active,
        }),
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP error! status: ${response.status} ${response.statusText}`);
    }

    return (await response.json()) as Response;
};

/**
 * Save plugin configuration
 *
 * @param name
 * @param config
 * @param csrfToken
 */
export const savePluginConfig = async (
    name: string,
    config: Record<string, any>,
    csrfToken: string
): Promise<Response> => {
    const response = await fetch('api/plugin/config', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
        },
        body: JSON.stringify({
            name,
            config,
            csrf: csrfToken, // Also including in body as requested for backend validation
        }),
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP error! status: ${response.status} ${response.statusText}`);
    }

    return (await response.json()) as Response;
};
