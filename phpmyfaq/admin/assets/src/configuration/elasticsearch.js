/**
 * Admin Elasticsearch configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-20
 */

import { addElement } from '../../../../assets/src/utils';
import { formatBytes } from '../utils';

export const handleElasticsearch = () => {
  const buttons = document.querySelectorAll('button.pmf-elasticsearch');

  if (buttons) {
    buttons.forEach((element) => {
      element.addEventListener('click', (event) => {
        event.preventDefault();

        const action = event.target.getAttribute('data-action');

        fetch(`index.php?action=ajax&ajax=elasticsearch&ajaxaction=${action}`)
          .then(async (response) => {
            if (response.ok) {
              return response.json();
            }
            throw new Error('Network response was not ok: ', { cause: { response } });
          })
          .then((response) => {
            const stats = document.getElementById('pmf-elasticsearch-result');
            stats.insertAdjacentElement(
              'afterend',
              addElement('div', {
                classList: 'alert alert-success',
                innerText: response.success,
              })
            );

            setInterval(elasticsearchStats, 5000);
          })
          .catch(async (error) => {
            const result = document.getElementById('pmf-elasticsearch-result');
            const errorMessage = await error.cause.response.json();
            result.insertAdjacentElement(
              'afterend',
              addElement('div', { classList: 'alert alert-danger', innerText: errorMessage.error })
            );
          });
      });
    });

    const elasticsearchStats = () => {
      const div = document.getElementById('pmf-elasticsearch-stats');
      if (div) {
        div.innerHTML = '';
        fetch(`index.php?action=ajax&ajax=elasticsearch&ajaxaction=stats`)
          .then(async (response) => {
            if (response.ok) {
              return response.json();
            }
            throw new Error('Network response was not ok: ', { cause: { response } });
          })
          .then((response) => {
            const indexName = response.index;
            const stats = response.stats;
            const count = stats['indices'][indexName]['total']['docs'].count;
            const sizeInBytes = stats['indices'][indexName]['total']['store'].size_in_bytes;
            let html = '<dl class="row">';
            html += `<dt class="col-sm-3">Documents</dt><dd class="col-sm-9">${count ?? 0}</dd>`;
            html += `<dt class="col-sm-3">Storage size</dt><dd class="col-sm-9">${formatBytes(sizeInBytes ?? 0)}</dd>`;
            html += '</dl>';
            div.innerHTML = html;
          })
          .catch(async (error) => {
            const result = document.getElementById('pmf-elasticsearch-result');
            const errorMessage = await error.cause.response.json();
            result.insertAdjacentElement(
              'afterend',
              addElement('div', { classList: 'alert alert-danger', innerText: errorMessage.error })
            );
          });
      }
    };

    elasticsearchStats();
  }
};
