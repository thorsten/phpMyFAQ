/**
 * JavaScript functions for search relevant administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2015-12-26
 */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  $('button.pmf-elasticsearch').on('click', function () {
    const action = $(this).data('action');
    $.ajax({
      url: 'index.php?action=ajax&ajax=elasticsearch&ajaxaction=' + action,
      type: 'POST',
      dataType: 'json',
    }).done((message) => {
      const result = $('.result'),
        indicator = $('#pmf-admin-saving-data-indicator');

      indicator.html('<i class="fa fa-cog fa-spin fa-fw"></i><span class="sr-only">Saving ...</span>');
      result.empty();
      if (message.error) {
        result.append('<p class="alert alert-danger">✗ ' + message.error + '</p>');
      } else {
        result.append('<p class="alert alert-success">✓ ' + message.success + '</p>');
      }
      indicator.fadeOut();

      window.setTimeout(() => elasticsearchStats(), 1000);
    });
  });

  const elasticsearchStats = () => {
    const div = document.getElementById('pmf-elasticsearch-stats');
    div.innerHTML = '';
    fetch(`index.php?action=ajax&ajax=elasticsearch&ajaxaction=stats`)
      .then((response) => {
        return response.json();
      })
      .then((stats) => {
        const count = stats?.indices?.phpmyfaq?.total?.docs?.count;
        const sizeInBytes = stats?.indices?.phpmyfaq?.total?.store?.size_in_bytes;
        let html = '<dl class="row">';
        html += `<dt class="col-sm-3">Documents</dt><dd class="col-sm-9">${count}</dd>`;
        html += `<dt class="col-sm-3">Storage size</dt><dd class="col-sm-9">${sizeInBytes}</dd>`;
        html += '</dl>';
        div.innerHTML = html;
      });
  };

  window.setTimeout(() => elasticsearchStats(), 1000);
});
