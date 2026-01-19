/**
 * Fetch data for Elasticsearch configuration
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
 * @since     2025-01-26
 */

import { ElasticsearchResponse, Response } from '../interfaces';
import { fetchWrapper, fetchJson } from './fetch-wrapper';

export const fetchElasticsearchAction = async (action: string): Promise<Response> => {
  return (await fetchJson(`./api/elasticsearch/${action}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  })) as Response;
};

export const fetchElasticsearchStatistics = async (): Promise<ElasticsearchResponse> => {
  return (await fetchJson('./api/elasticsearch/statistics', {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  })) as ElasticsearchResponse;
};

export const fetchElasticsearchHealthcheck = async (timeoutMs: number = 5000): Promise<Response> => {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const response = await fetchWrapper('./api/elasticsearch/healthcheck', {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
      signal: controller.signal,
    });

    clearTimeout(timeoutId);

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.error || 'Elasticsearch is unavailable');
    }

    return await response.json();
  } catch (error) {
    clearTimeout(timeoutId);
    if (error instanceof Error && error.name === 'AbortError') {
      throw new Error('Elasticsearch health check timed out. Service may be down.');
    }
    throw error;
  }
};
