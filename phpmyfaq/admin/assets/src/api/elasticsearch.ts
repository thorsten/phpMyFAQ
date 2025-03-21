/**
 * Fetch data for Elasticsearch configuration
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

import { ElasticsearchResponse, Response } from '../interfaces';

export const fetchElasticsearchAction = async (action: string): Promise<Response> => {
  try {
    const response = await fetch(`./api/elasticsearch/${action}`, {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const fetchElasticsearchStatistics = async (): Promise<ElasticsearchResponse> => {
  try {
    const response = await fetch('./api/elasticsearch/statistics', {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};
