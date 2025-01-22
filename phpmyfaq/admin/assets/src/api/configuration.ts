/**
 * Fetch data for configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-21
 */

import { Response } from '../interfaces';

export const saveConfiguration = async (data: FormData): Promise<void> => {
  try {
    const response = (await fetch('api/configuration', {
      method: 'POST',
      body: data,
    })) as unknown as Response;

    if (response.success) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    console.error('Error updating configuration: ', error);
    throw error;
  }
};
