<?php
/**
 * Attachment administration interface
 * 
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2003-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-12-13
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$page = PMF_Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = 1 > $page ? 1 : $page;

$fa           = new PMF_Attachment_Collection;
$itemsPerPage = 32;
$allCrumbs    = $fa->getBreadcrumbs();

$crumbs   = array_slice($allCrumbs, ($page - 1) * $itemsPerPage, $itemsPerPage);

$pagination = new PMF_Pagination(
    array(
        'baseUrl'   => PMF_Link::getSystemRelativeUri() . '?' . str_replace('&', '&amp;', $_SERVER['QUERY_STRING']),
        'total'     => count($allCrumbs),
        'perPage'   => $itemsPerPage,
        'layoutTpl' => '<p align="center"><strong>{LAYOUT_CONTENT}</strong></p>',
    )
);

printf('<header><h2>%s</h2></header>', $PMF_LANG['ad_menu_attachment_admin']);

?>
        <table class="list" style="width: 100%;">
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
             *
             * @return void
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
                            $('#saving_data_indicator').
                                html('<p class="success"><?php print $PMF_LANG['msgAttachmentsDeleted']; ?></p>');
                        }
                    });
                }
            }
            </script>