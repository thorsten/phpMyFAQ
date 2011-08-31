<?php
/**
 * Deletes an attachment
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
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-24
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf("<header><h2>%s</h2></header>\n", $PMF_LANG['ad_entry_aor']);

if ($permission["delatt"]) {

    $recordId   = PMF_Filter::filterInput(INPUT_GET, 'record_id', FILTER_VALIDATE_INT);
    $recordLang = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);
    $id         = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    $att = PMF_Attachment_Factory::create($id);
    
    if ($att && $att->delete()) {
        printf('<p class="success">%s</p>', $PMF_LANG['ad_att_delsuc']);
    } else {
        printf('<p class="error">%s</p>', $PMF_LANG['ad_att_delfail']);
    }
    printf('<p><a href="?action=editentry&amp;id=%d&amp;lang=%s">%s</a></p>',
        $recordId, $recordLang, $PMF_LANG['ad_entry_back']);
} else {
    print $PMF_LANG['err_NotAuth'];
}

