<?php
/**
 * Ajax interface for attachments.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2010-12-13
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$page = PMF_Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = 1 > $page ? 1 : $page;

$fa = new PMF_Attachment_Collection($faqConfig);
$itemsPerPage = 32;
$allCrumbs = $fa->getBreadcrumbs();

$crumbs = array_slice($allCrumbs, ($page - 1) * $itemsPerPage, $itemsPerPage);

$pagination = new PMF_Pagination(
    $faqConfig,
    array(
        'baseUrl' => PMF_Link::getSystemRelativeUri().'?'.str_replace('&', '&amp;', $_SERVER['QUERY_STRING']),
        'total' => count($allCrumbs),
        'perPage' => $itemsPerPage,
    )
);
?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-file"></i> <?php echo $PMF_LANG['ad_menu_attachment_admin'] ?>
                </h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th><?php echo $PMF_LANG['msgAttachmentsFilename'] ?></th>
                        <th><?php echo $PMF_LANG['msgTransToolLanguage'] ?></th>
                        <th><?php echo $PMF_LANG['msgAttachmentsFilesize'] ?></th>
                        <th colspan="2"><?php echo $PMF_LANG['msgAttachmentsMimeType'] ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($crumbs as $item): ?>
                        <tr class="att_<?php echo $item->id ?>" title="<?php echo $item->thema ?>">
                            <td><?php echo $item->filename ?></td>
                            <td><?php echo $item->record_lang ?></td>
                            <td><?php echo $item->filesize ?></td>
                            <td><?php echo $item->mime_type ?></td>
                            <td>
                                <a href="javascript:deleteAttachment(<?php echo $item->id ?>, '<?php echo $user->getCsrfTokenFromSession() ?>'); void(0);"
                                   class="btn btn-danger" title="<?php echo $PMF_LANG['ad_gen_delete'] ?>">
                                    <i aria-hidden="true" class="fa fa-trash-o"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="5"><?php echo $pagination->render(); ?></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <script type="text/javascript">
        /**
         * Ajax call for deleting attachments
         *
         * @param att_id Attachment id
         * @apram csrf CSRF token
         */
        function deleteAttachment(att_id, csrf)
        {
            if (confirm('<?php echo $PMF_LANG['msgAttachmentsWannaDelete'] ?>')) {
                $('#saving_data_indicator').html('<i aria-hidden="true" class="fa fa-spinner fa-spin"></i> Deleting ...');
                $.ajax({
                    type:    "GET",
                    url:     "index.php?action=ajax&ajax=att&ajaxaction=delete",
                    data:    { attId: att_id, csrf: csrf},
                    success: function(msg) {
                        $('.att_' + att_id).fadeOut('slow');
                        $('#saving_data_indicator').html('<p class="alert alert-success">' + msg + '</p>');
                    }
                });
            }
        }
        </script>