<?php
/**
* $Id: record.delcomment.php,v 1.12 2008-01-26 17:12:45 thorstenr Exp $
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
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if (isset($_REQUEST['type']) && (PMF_Comment::COMMENT_TYPE_NEWS == $_REQUEST['type'])) {
    print "<h2>".$PMF_LANG['ad_news_edit']."</h2>\n";
} else {
    print "<h2>".$PMF_LANG['ad_entry_aor']."</h2>\n";
}

if ($permission['delcomment']) {
    if ($_REQUEST['subm'] == $PMF_LANG['ad_gen_yes']) {
        $recordId = (int)$_REQUEST['artid'];
        $cmtId = (int)$_REQUEST['cmtid'];
        $oComment = new PMF_Comment(& $db, $LANGCODE);
        if ($oComment->deleteComment($recordId, $cmtId)) {
            $msg = $PMF_LANG['ad_entry_commentdelsuc'];
        } else {
            $msg = $PMF_LANG['ad_entry_commentdelfail'];
        }
    } else {
        $msg = $PMF_LANG['ad_entry_commentdelfail'];
    }
    if (   !isset($_REQUEST['type'])
        || (isset($_REQUEST['type']) && (PMF_Comment::COMMENT_TYPE_FAQ == $_REQUEST['type']))
       ) {
        printf('<p align="center">%s<p><a href="?action=editentry&amp;id=%d&amp;lang=%s">%s</a></p>',
            $msg,
            $_REQUEST['artid'],
            $_REQUEST['lang'],
            $PMF_LANG['ad_entry_back']);
    }
    if (isset($_REQUEST['type']) && (PMF_Comment::COMMENT_TYPE_NEWS == $_REQUEST['type'])) {
        printf('<p align="center">%s<p><a href="?action=news&amp;do=edit&amp;id=%d">%s</a></p>',
            $msg,
            $_REQUEST['artid'],
            $PMF_LANG['ad_entry_back']);
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}
