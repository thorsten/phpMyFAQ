/**
 * Fetch data for attachment management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-05-01
 */

import { Response } from '../interfaces';

export const deleteAttachments = async (attachmentId: string, csrfToken: string): Promise<Response> => {
  try {
    const response = await fetch('./api/content/attachments', {
      method: 'DELETE',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ attId: attachmentId, csrf: csrfToken }),
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const refreshAttachments = async (attachmentId: string, csrfToken: string): Promise<Response> => {
  try {
    const response = await fetch('./api/content/attachments/refresh', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ attId: attachmentId, csrf: csrfToken }),
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const uploadAttachments = async (formData: FormData): Promise<Response> => {
  try {
    const response = await fetch('./api/content/attachments/upload', {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: formData,
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};
