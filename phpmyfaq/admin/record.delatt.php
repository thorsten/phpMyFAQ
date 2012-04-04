<?php
/**
 * Deletes an attachment
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-24
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf("<header><h2>%s</h2></header>\n", $PMF_LANG['ad_entry_aor']);

if ($permission['delattachment']) {

    $recordId   = PMF_Filter::filterInput(INPUT_GET, 'record_id', FILTER_VALIDATE_INT);
    $recordLang = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);
    $id         = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    $att = PMF_Attachment_Factory::create($id);
    
    if ($att && $att->delete()) {
        printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_att_delsuc']);
    } else {
        printf('<p class="alert alert-error">%s</p>', $PMF_LANG['ad_att_delfail']);
    }
    printf('<p><a href="?action=editentry&amp;id=%d&amp;lang=%s">%s</a></p>',
        $recordId, $recordLang, $PMF_LANG['ad_entry_back']);
} else {
    print $PMF_LANG['err_NotAuth'];
}

