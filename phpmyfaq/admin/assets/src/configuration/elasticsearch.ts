/**
 * Admin Elasticsearch configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2022-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-20
 */

import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { fetchElasticsearchAction, fetchElasticsearchStatistics } from '../api/elasticsearch';
import { ElasticsearchResponse, Response } from '../interfaces';
import { formatBytes } from '../utils';

export const handleElasticsearch = async (): Promise<void> => {
  const buttons: NodeListOf<HTMLButtonElement> = document.querySelectorAll('button.pmf-elasticsearch');

  if (buttons) {
    buttons.forEach((element: HTMLButtonElement): void => {
      element.addEventListener('click', async (event: Event): Promise<void> => {
        event.preventDefault();

        const action = (event.target as HTMLButtonElement).getAttribute('data-action') as string;

        try {
          const response = (await fetchElasticsearchAction(action)) as unknown as Response;

          if (typeof response.success !== 'undefined') {
            pushNotification(response.success);
            setInterval(elasticsearchStats, 5000);
          } else {
            pushErrorNotification(response.error as string);
          }
        } catch (error) {
          pushErrorNotification(error as string);
        }
      });

      const elasticsearchStats = async (): Promise<void> => {
        const div = document.getElementById('pmf-elasticsearch-stats') as HTMLElement;
        if (div) {
          div.innerHTML = '';

          try {
            const response = (await fetchElasticsearchStatistics()) as unknown as ElasticsearchResponse;

            if (response.index) {
              const indexName = response.index as string;
              const stats = response.stats;
              const count: number = stats.indices[indexName].total.docs.count ?? 0;
              const sizeInBytes: number = stats.indices[indexName].total.store.size_in_bytes ?? 0;
              let html: string = '<dl class="row">';
              html += `<dt class="col-sm-3">Documents</dt><dd class="col-sm-9">${count ?? 0}</dd>`;
              html += `<dt class="col-sm-3">Storage size</dt><dd class="col-sm-9">${formatBytes(sizeInBytes ?? 0)}</dd>`;
              html += '</dl>';
              div.innerHTML = html;
            }
          } catch (error) {
            pushErrorNotification(error as string);
          }
        }
      };

      elasticsearchStats();
    });
  }
};
