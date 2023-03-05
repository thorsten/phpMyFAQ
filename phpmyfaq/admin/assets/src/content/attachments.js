/**
 * Attachment administration stuff
 *
 * @todo needs to be refactored
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-22
 */

document.addEventListener('DOMContentLoaded', () => {
  const attachmentTable = document.getElementById('attachment-table');

  attachmentTable.addEventListener('click', (event) => {
    event.preventDefault();
    const isButton = event.target.nodeName === 'BUTTON';

    if (isButton) {
      const attachmentId = event.target.getAttribute('data-attachment-id');
      const csrf = event.target.getAttribute('data-csrf');

      $('#pmf-admin-saving-data-indicator').html(
        '<i class="fa fa-cog fa-spin fa-fw"></i><span class="sr-only">Deleting ...</span>'
      );
      $.ajax({
        type: 'GET',
        url: 'index.php?action=ajax&ajax=att&ajaxaction=delete',
        data: { attId: attachmentId, csrf: csrf },
        success: function (msg) {
          $('.att_' + attachmentId).fadeOut('slow');
          $('#pmf-admin-saving-data-indicator').html('<p class="alert alert-success">' + msg + '</p>');
        },
      });
    }
  });
});
