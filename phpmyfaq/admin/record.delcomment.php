<?php
/**
* $Id: record.delcomment.php,v 1.6 2006-06-18 10:09:31 thorstenr Exp $
*
* Deletes a user comment
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-23
* @copyright    (c) 2001-2006 phpMyFAQ Team
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
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf("<h2>%s</h2>\n", $PMF_LANG['ad_entry_aor']);
if ($permission['delcomment']) {
    if ($_REQUEST['subm'] == $PMF_LANG['ad_gen_yes']) {
        $faq->deleteComment($_REQUEST['artid'], $_REQUEST['cmtid']);
        $msg = $PMF_LANG['ad_entry_commentdelsuc'];
    } else {
        $msg = $PMF_LANG['ad_entry_commentdelfail'];
    }
    printf('<p align="center">%s<p><a href="?aktion=editentry&id=%d&amp;lang=%s">%s</a></p>',
        $msg,
        $_REQUEST['artid'],
        $_REQUEST['lang'],
        $PMF_LANG['ad_entry_back']);
} else {
    print $PMF_LANG['err_NotAuth'];
}