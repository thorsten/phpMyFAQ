/**
 * Fetch data for OpenSearch configuration
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
 * @since     2025-05-12
 */

import { ElasticsearchResponse, Response } from '../interfaces';

export const fetchOpenSearchAction = async (action: string): Promise<Response> => {
  const response = await fetch(`./api/opensearch/${action}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  return await response.json();
};

export const fetchOpenSearchStatistics = async (): Promise<ElasticsearchResponse> => {
  const response = await fetch('./api/opensearch/statistics', {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  return await response.json();
};

export const fetchOpenSearchHealthcheck = async (): Promise<Response> => {
  const response = await fetch('./api/opensearch/healthcheck', {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  if (!response.ok) {
    const errorData = await response.json();
    throw new Error(errorData.error || 'OpenSearch is unavailable');
  }

  return await response.json();
};
