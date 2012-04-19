<?php
/**
 * Ajax interface for attachments
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-12-13
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$page = PMF_Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = 1 > $page ? 1 : $page;

$fa           = new PMF_Attachment_Collection($faqConfig);
$itemsPerPage = 32;
$allCrumbs    = $fa->getBreadcrumbs();

$crumbs   = array_slice($allCrumbs, ($page - 1) * $itemsPerPage, $itemsPerPage);

$pagination = new PMF_Pagination(
    $faqConfig,
    array(
        'baseUrl'   => PMF_Link::getSystemRelativeUri() . '?' . str_replace('&', '&amp;', $_SERVER['QUERY_STRING']),
        'total'     => count($allCrumbs),
        'perPage'   => $itemsPerPage,
    )
);

printf('<header><h2>%s</h2></header>', $PMF_LANG['ad_menu_attachment_admin']);

?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php print $PMF_LANG['msgAttachmentsFilename'] ?></th>
                    <th><?php print $PMF_LANG['msgTransToolLanguage'] ?></th>
                    <th><?php print $PMF_LANG['msgAttachmentsFilesize'] ?></th>
                    <th><?php print $PMF_LANG['msgAttachmentsMimeType'] ?></th>
                    <th><?php print $PMF_LANG['msgTransToolActions'] ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($crumbs as $item): ?>
                <tr class="att_<?php print $item->id ?>" title="<?php print $item->thema ?>">
                    <td><?php print $item->filename ?></td>
                    <td><?php print $item->record_lang ?></td>
                    <td><?php print $item->filesize ?></td>
                    <td><?php print $item->mime_type ?></td>
                    <td>
                        <a href="javascript:deleteAttachment(<?php print $item->id ?>); void(0);">
                            <?php print $PMF_LANG['ad_gen_delete'] ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5"><?php print $pagination->render(); ?></td>
                </tr>
            </tfoot>
            </table>
        
            <script type="text/javascript">
            /**
             * Ajax call for deleting attachments
             *
             * @param att_id Attachment id
             */
            function deleteAttachment(att_id)
            {
                if (confirm('<?php print $PMF_LANG['msgAttachmentsWannaDelete'] ?>')) {
                    $('#saving_data_indicator').html('<img src="images/indicator.gif" /> deleting ...');
                    $.ajax({
                        type:    "GET",
                        url:     "index.php?action=ajax&ajax=att&ajaxaction=delete",
                        data:    {attId: att_id},
                        success: function(msg) {
                            $('.att_' + att_id).fadeOut('slow');
                            $('#saving_data_indicator').html('<p class="alert alert-success">' + msg + '</p>');
                        }
                    });
                }
            }
            </script>