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
 * @copyright 2003-2010 phpMyFAQ Team
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

$fa = new PMF_Attachment_Collection;
$itemsPerPage = 32;
$allCrumbs = $fa->getBreadcrumbs();

$crumbs   = array_slice(
    $allCrumbs, 
    ($page-1)*$itemsPerPage,
    $itemsPerPage
);

$pagination = new PMF_Pagination(
    array(
        'baseUrl'   => '?' . str_replace('&', '&amp;', $_SERVER['QUERY_STRING']),
        'total'     => count($allCrumbs),
        'perPage'   => $itemsPerPage,
        'layoutTpl' => '<p align="center"><strong>{LAYOUT_CONTENT}</strong></p>',
    )
);

printf('<h2>%s</h2>', $PMF_LANG['ad_menu_attachment_admin']);

?>
<table cellspacing="30">
	<thead>
   		<tr>
   			<th>Filename</th>
   			<th>Language</th>
   			<th>Filesize</th>
   			<th>Mime type</th>
   			<th>Actions</th>
   		</tr>
	</thead>
	<tbody>
<?php
    foreach($crumbs as $item) {
        print <<<ROW
 		<tr>
 			<td>{$item->filename}</td>
 			<td>{$item->record_lang}</td>
 			<td>{$item->filesize}</td>
 			<td>{$item->mime_type}</td>
 			<td>
 				
 			</td>
 		</tr>
ROW;
    }
?>
	</tbody>
	<tfoot>
		<tr>
			<td><?php echo $pagination->render(); ?></td>
		</tr>
	</tfoot>
</table>
<script type="text/javascript">
/**
 * Ajax call for deleting attachments
 *
 * @param  integer record_id   Record id
 * @param  string  record_lang Record language
 * @return void
 */
function deleteRecord(record_id, record_lang)
{
/*    if (confirm('Are you sure that the topic should be deleted?')) {
        $('#saving_data_indicator').html('<img src="images/indicator.gif" /> deleting ...');
        $.ajax({
            type:    "POST",
            url:     "index.php?action=ajax&ajax=records&ajaxaction=delete_record",
            data:    "record_id=" + record_id + "&record_lang=" + record_lang,
            success: function(msg) {
                $('.record_' + record_id + '_' + record_lang).fadeOut('slow');
                $('.record_' + record_id + '_' + record_lang).after('<tr><td colspan="8">' + msg + '</td></tr>');
                $('#saving_data_indicator').html('Issue <strong>successfully</strong> deleted.');
            }
        });
    }*/
} 
</script>