<?php
/**
 * $Id: record.delatt.php,v 1.9 2006-11-10 21:02:36 thorstenr Exp $
 *
 * Deletes an attachment
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since       2003-02-24
 * @copyright   (c) 2003-2006 phpMyFAQ Team
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
    
    $record_id   = (int)$_GET['id'];
    $record_lang = $_GET['lang'];
    $which_file  = $_GET['which'];
    
    if (@unlink(PMF_ROOT_DIR."/attachments/".$record_id."/".$which_file)) {
        printf("<p>%s</p>\n", $PMF_LANG['ad_att_delsuc']);
    } else {
        printf("<p>%s</p>\n", $PMF_LANG['ad_att_delfail']);
    }
    printf('<p><a href="%s&amp;action=editentry&amp;id=%d&amp;lang=%s">%s</a></p>',
        $linkext, $record_id, $record_lang, $PMF_LANG['ad_entry_back']);
} else {
    print $PMF_LANG['err_NotAuth'];
}