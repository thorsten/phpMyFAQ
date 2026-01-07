/**
 * Plugin API calls
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
 * @since     2025-01-07
 */

import { Response } from '../interfaces';

/**
 * Toggle plugin status
 *
 * @param name
 * @param active
 */
export const togglePluginStatus = async (name: string, active: boolean): Promise<Response> => {
    const response = await fetch('api/plugin/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            name,
            active,
        }),
    });

    return (await response.json()) as Response;
};

/**
 * Save plugin configuration
 *
 * @param name
 * @param config
 */
export const savePluginConfig = async (name: string, config: Record<string, any>): Promise<Response> => {
    const response = await fetch('api/plugin/config', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            name,
            config,
        }),
    });

    return (await response.json()) as Response;
};
