/**
 * Admin Elasticsearch configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2022-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-20
 */

import { formatBytes, pushErrorNotification, pushNotification } from '../utils';

export const handleElasticsearch = async () => {
  const buttons = document.querySelectorAll('button.pmf-elasticsearch');

  if (buttons) {
    buttons.forEach((element) => {
      element.addEventListener('click', async (event) => {
        event.preventDefault();

        const action = event.target.getAttribute('data-action');

        try {
          const response = await fetch(`./api/elasticsearch/${action}`);

          if (response.ok) {
            const result = await response.json();
            pushNotification(result.success);

            setInterval(elasticsearchStats, 5000);
          } else {
            const errorMessage = await response.json();
            pushErrorNotification(errorMessage.error);
          }
        } catch (error) {
          const errorMessage = error.cause && error.cause.response ? await error.cause.response.json() : null;
          pushErrorNotification(errorMessage?.error || error.message);
        }
      });

      const elasticsearchStats = async () => {
        const div = document.getElementById('pmf-elasticsearch-stats');
        if (div) {
          div.innerHTML = '';

          try {
            const response = await fetch('./api/elasticsearch/statistics');

            if (response.ok) {
              const result = await response.json();
              const indexName = result.index;
              const stats = result.stats;
              const count = stats['indices'][indexName]['total']['docs'].count;
              const sizeInBytes = stats['indices'][indexName]['total']['store'].size_in_bytes;
              let html = '<dl class="row">';
              html += `<dt class="col-sm-3">Documents</dt><dd class="col-sm-9">${count ?? 0}</dd>`;
              html += `<dt class="col-sm-3">Storage size</dt><dd class="col-sm-9">${formatBytes(
                sizeInBytes ?? 0
              )}</dd>`;
              html += '</dl>';
              div.innerHTML = html;
            } else {
              const errorMessage = await response.json();
              pushErrorNotification(errorMessage.error);
            }
          } catch (error) {
            const errorMessage = error.cause && error.cause.response ? await error.cause.response.json() : null;
            pushErrorNotification(errorMessage?.error || error.message);
          }
        }
      };

      elasticsearchStats();
    });
  }
};
