<?php

/**
 * Frontend for handling with attachments.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2010-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-12-13
 */

use phpMyFAQ\Attachment\AttachmentCollection;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\Utils;
use phpMyFAQ\Strings;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();

$page = Filter::filterVar($request->query->get('page'), FILTER_VALIDATE_INT);
$page = max(1, $page);

$attachmentCollection = new AttachmentCollection($faqConfig);
$itemsPerPage = 24;
$allCrumbs = $attachmentCollection->getBreadcrumbs();

$crumbs = array_slice($allCrumbs, ($page - 1) * $itemsPerPage, $itemsPerPage);

$pagination = new Pagination(
    [
        'baseUrl' => $faqConfig->getDefaultUrl() . $request->getRequestUri(),
        'total' => is_countable($allCrumbs) ? count($allCrumbs) : 0,
        'perPage' => $itemsPerPage,
    ]
);
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">
    <i aria-hidden="true" class="fa fa-paperclip"></i>
      <?= Translation::get('ad_menu_attachment_admin') ?>
  </h1>
</div>

<div class="row">
  <div class="col-lg-12">
    <table class="table table-striped align-middle">
      <thead>
      <tr>
        <th>#</th>
        <th><?= Translation::get('msgAttachmentsFilename') ?></th>
        <th><?= Translation::get('msgTransToolLanguage') ?></th>
        <th><?= Translation::get('msgAttachmentsFilesize') ?></th>
        <th colspan="3"><?= Translation::get('msgAttachmentsMimeType') ?></th>
      </tr>
      </thead>
      <tbody id="attachment-table">
      <?php foreach ($crumbs as $item) : ?>
        <tr id="attachment_<?= $item->id ?>" title="<?= $item->thema ?>">
          <td><?= $item->id ?></td>
          <td><?= Strings::htmlentities($item->filename); ?></td>
          <td><?= Strings::htmlentities($item->record_lang); ?></td>
          <td><?= Utils::formatBytes($item->filesize) ?></td>
          <td><?= Strings::htmlentities($item->mime_type); ?></td>
          <td>
            <button class="btn btn-danger btn-delete-attachment" title="<?= Translation::get('ad_gen_delete') ?>"
                    data-attachment-id="<?= $item->id ?>"
                    data-csrf="<?= Token::getInstance()->getTokenString('delete-attachment') ?>">
              <i aria-hidden="true" class="fa fa-trash btn-delete-attachment" data-attachment-id="<?= $item->id ?>"
                    data-csrf="<?= Token::getInstance()->getTokenString('delete-attachment') ?>"></i>
            </button>
          </td>
          <td>
            <a title="<?= Translation::get('ad_entry_faq_record') ?>" class="btn btn-info"
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

