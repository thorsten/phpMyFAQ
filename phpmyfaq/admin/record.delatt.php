<?php
/**
 * Deletes an attachment
 *
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2003-02-24
 * @copyright  2003-2009 phpMyFAQ Team
 * @version    SVN: $Id$
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
 */

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf("<h2>%s</h2>\n", $PMF_LANG['ad_entry_aor']);

if ($permission["delatt"]) {

    $record_id   = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $record_lang = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);
    $record_file = PMF_Filter::filterInput(INPUT_GET, 'which', FILTER_SANITIZE_STRING);
    $record_path = PMF_ROOT_DIR . PATH_SEPARATOR . "attachments" . PATH_SEPARATOR . $record_id;
    $filename    = realpath($record_path . PATH_SEPARATOR. $record_file);
    $file_okay   = true;
    
    if (!$filename || strpos($filename, realpath($record_path)) !== 0) {
    	$file_okay = false;
    }
    
    if ($file_okay && @unlink($filename)) {
        printf("<p>%s</p>\n", $PMF_LANG['ad_att_delsuc']);
    } else {
        printf("<p>%s</p>\n", $PMF_LANG['ad_att_delfail']);
    }
    printf('<p><a href="?action=editentry&amp;id=%d&amp;lang=%s">%s</a></p>',
        $record_id, $record_lang, $PMF_LANG['ad_entry_back']);
} else {
    print $PMF_LANG['err_NotAuth'];
}
