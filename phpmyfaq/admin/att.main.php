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

printf('<h2>%s</h2>', $PMF_LANG['ad_menu_attachment_admin']);

$fa = new PMF_Attachment_Collection;

$c = $fa->getBreadcrumbs();
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
    foreach($c as $item) {
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
</table>