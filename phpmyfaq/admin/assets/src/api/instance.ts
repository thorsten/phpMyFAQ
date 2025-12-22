/**
 * Fetch data for instance configuration
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
 * @since     2025-01-26
 */

import { InstanceResponse } from '../interfaces';

export const addInstance = async (
  csrf: string,
  url: string,
  instance: string,
  comment: string,
  email: string,
  admin: string,
  password: string
): Promise<InstanceResponse> => {
  const response = await fetch(`./api/faq/search`, {
    method: 'POST',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrf: csrf,
      url: url,
      instance: instance,
      comment: comment,
      email: email,
      admin: admin,
      password: password,
    }),
  });

  return await response.json();
};

export const deleteInstance = async (csrf: string, instanceId: string): Promise<InstanceResponse> => {
  const response = await fetch('./api/instance/delete', {
    method: 'DELETE',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrf: csrf,
      instanceId: instanceId,
    }),
  });

  return await response.json();
};
