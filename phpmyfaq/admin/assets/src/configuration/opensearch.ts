/**
 * Admin OpenSearch configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-05-12
 */

import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { fetchOpenSearchAction, fetchOpenSearchHealthcheck, fetchOpenSearchStatistics } from '../api';
import { ElasticsearchResponse, Response } from '../interfaces';
import { formatBytes } from '../utils';

export const handleOpenSearch = async (): Promise<void> => {
  const buttons: NodeListOf<HTMLButtonElement> = document.querySelectorAll('button.pmf-opensearch');

  // Check health status on page load
  const healthCheckAlert = async (): Promise<boolean> => {
    const alertDiv = document.getElementById('pmf-opensearch-healthcheck-alert') as HTMLElement;
    if (alertDiv) {
      try {
        await fetchOpenSearchHealthcheck();
        alertDiv.style.display = 'none';
        return true;
      } catch (error) {
        alertDiv.style.display = 'block';
        const alertMessage = alertDiv.querySelector('.alert-message');
        if (alertMessage) {
          alertMessage.textContent = error instanceof Error ? error.message : 'OpenSearch is unavailable';
        }
        return false;
      }
    }
    return false;
  };

  // Run health check on page load
  const isHealthy = await healthCheckAlert();

  if (buttons) {
    buttons.forEach((element: HTMLButtonElement): void => {
      element.addEventListener('click', async (event: Event): Promise<void> => {
        event.preventDefault();

        const action = (event.target as HTMLButtonElement).getAttribute('data-action') as string;

        try {
          const response = (await fetchOpenSearchAction(action)) as unknown as Response;

          if (typeof response.success !== 'undefined') {
            pushNotification(response.success);
            setInterval(openSearchStats, 5000);
          } else {
            pushErrorNotification(response.error as string);
          }
        } catch (error) {
          pushErrorNotification(error as string);
        }
      });

      const openSearchStats = async (): Promise<void> => {
        const div = document.getElementById('pmf-opensearch-stats') as HTMLElement;
        if (div) {
          div.innerHTML = '';

          try {
            const response = (await fetchOpenSearchStatistics()) as unknown as ElasticsearchResponse;

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

      // Only fetch stats if OpenSearch is healthy
      if (isHealthy) {
        openSearchStats();
      }
    });
  }
};
