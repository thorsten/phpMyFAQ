<?php

/**
 * Ajax interface for attachments.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Anatoliy Belsky <ab@php.net>
 * @copyright 2010-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2010-12-13
 */

use phpMyFAQ\Attachment\AttachmentCollection;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$page = Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = 1 > $page ? 1 : $page;

$attachmentCollection = new AttachmentCollection($faqConfig);
$itemsPerPage = 24;
$allCrumbs = $attachmentCollection->getBreadcrumbs();

$crumbs = array_slice($allCrumbs, ($page - 1) * $itemsPerPage, $itemsPerPage);

$pagination = new Pagination(
    $faqConfig,
    [
        'baseUrl' => $faqConfig->getDefaultUrl() . 'admin/?' . str_replace('&', '&amp;', $_SERVER['QUERY_STRING']),
        'total' => count($allCrumbs),
        'perPage' => $itemsPerPage,
    ]
);
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">
    <i aria-hidden="true" class="fa fa-paperclip"></i>
      <?= $PMF_LANG['ad_menu_attachment_admin'] ?>
  </h1>
</div>

<div class="row">
  <div class="col-lg-12">
    <table class="table table-striped">
      <thead>
      <tr>
        <th>#</th>
        <th><?= $PMF_LANG['msgAttachmentsFilename'] ?></th>
        <th><?= $PMF_LANG['msgTransToolLanguage'] ?></th>
        <th><?= $PMF_LANG['msgAttachmentsFilesize'] ?></th>
        <th colspan="3"><?= $PMF_LANG['msgAttachmentsMimeType'] ?></th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($crumbs as $item) : ?>
        <tr class="att_<?= $item->id ?>" title="<?= $item->thema ?>">
          <td><?= $item->id ?></td>
          <td><?= $item->filename ?></td>
          <td><?= $item->record_lang ?></td>
          <td><?= $item->filesize ?></td>
          <td><?= $item->mime_type ?></td>
          <td>
            <a href="deleteAttachment(<?= $item->id ?>, '<?= $user->getCsrfTokenFromSession() ?>'); void(0);"
               class="btn btn-danger" title="<?= $PMF_LANG['ad_gen_delete'] ?>">
              <i aria-hidden="true" class="fa fa-trash"></i>
            </a>
          </td>
          <td>
            <a title="<?= $PMF_LANG['ad_entry_faq_record'] ?>" class="btn btn-info"
               href="../index.php?action=faq&id=<?= $item->record_id ?>&lang=<?= $item->record_lang ?>">
              <i aria-hidden="true" class="fa fa-link"></i>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
      <tr>
        <td colspan="5"><?= $pagination->render(); ?></td>
      </tr>
      </tfoot>
    </table>
  </div>
</div>

<script>
  /**
   * Ajax call for deleting attachments
   *
   * @param attachmentId Attachment id
   * @param csrf
   * @param csrf CSRF token
   */
  function deleteAttachment(attachmentId, csrf) {
    if (confirm('<?= $PMF_LANG['msgAttachmentsWannaDelete'] ?>')) {
      $('#pmf-admin-saving-data-indicator').html('<i class="fa fa-cog fa-spin fa-fw"></i><span class="sr-only">Deleting ...</span>');
      $.ajax({
        type: "GET",
        url: "index.php?action=ajax&ajax=att&ajaxaction=delete",
        data: {attId: attachmentId, csrf: csrf},
        success: function (msg) {
          $('.att_' + attachmentId).fadeOut('slow');
          $('#pmf-admin-saving-data-indicator').html('<p class="alert alert-success">' + msg + '</p>');
        }
      });
    }
  }
</script>
