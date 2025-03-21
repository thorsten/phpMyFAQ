/**
 * Private User API functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-03
 */

import { serialize } from '../utils';
import { ApiResponse } from '../interfaces';

export const updateUserControlPanelData = async (data: FormData): Promise<ApiResponse | undefined> => {
  try {
    const response: Response = await fetch('api/user/data/update', {
      method: 'PUT',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(serialize(data)),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    return await response.json();
  } catch (error) {
    console.error(error);
  }
};

export const updateUserPassword = async (data: FormData): Promise<ApiResponse | undefined> => {
  try {
    const response: Response = await fetch('api/user/password/update', {
      method: 'PUT',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(serialize(data)),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    return await response.json();
  } catch (error) {
    console.error(error);
  }
};

export const requestUserRemoval = async (data: FormData): Promise<ApiResponse | undefined> => {
  try {
    const response: Response = await fetch('api/user/request-removal', {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(serialize(data)),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    return await response.json();
  } catch (error) {
    console.error(error);
  }
};

export const removeTwofactorConfig = async (csrfToken: string): Promise<ApiResponse | undefined> => {
  try {
    const response: Response = await fetch('api/user/remove-twofactor', {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrfToken: csrfToken,
      }),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    return await response.json();
  } catch (error) {
    console.error(error);
  }
};
