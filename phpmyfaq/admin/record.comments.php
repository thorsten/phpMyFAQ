<?php
/**
 * $Id: record.comments.php,v 1.1 2007-03-04 14:12:41 thorstenr Exp $
 *
 * Shows all comments in the categories and provides a link to delete comments
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since       2007-03-04
 * @copyright   (c) 2007 phpMyFAQ Team
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

printf("<h2>%s</h2>\n", $PMF_LANG['ad_comment_administration']);
if ($permission['delcomment']) {

    $comment = new PMF_Comment($db, $LANGCODE);

    $category = new PMF_Category('', $current_admin_user, $current_admin_groups, false);
    $category->buildTree();

    $faq = new PMF_Faq($db, $LANGCODE);

    $faqcomments  = $comment->getAllComments('faq');
    $newscomments = $comment->getAllComments('news');

    printf("<p><strong>%s</strong></p>\n", $PMF_LANG['ad_comment_faqs']);
?>
    <table class="listrecords">
    <thead>
    <tr>
        <th class="listhead" width="100"></th>
        <th class="listhead"></th>
        <th class="listhead" width="20">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
<?php
    foreach ($faqcomments as $faqcomment) {
?>
    <tr>
        <td class="list"><a href="mailto:<?php print $faqcomment['email']; ?>"><?php print $faqcomment['user']; ?></td>
        <td class="list"><?php print $faqcomment['content']; ?></td>
        <td class="list"><a href="?action=delcomment&amp;artid=<?php print $faqcomment['record_id']; ?>&amp;cmtid=<?php print $faqcomment['comment_id']; ?>"><img src="images/delete.gif" alt="<?php print $PMF_LANG["ad_entry_delete"] ?>" title="<?php print $PMF_LANG["ad_entry_delete"] ?>" border="0" width="17" height="18" align="right" /></a></td>
    </tr>
<?php
    }
?>
    </tbody>
    </table>
<?php
    printf("<p><strong>%s</strong></p>\n", $PMF_LANG['ad_comment_news']);

?>
    <table class="listrecords">
    <thead>
    <tr>
        <th class="listhead" width="100"></th>
        <th class="listhead"></th>
        <th class="listhead" width="20">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
<?php
    foreach ($newscomments as $newscomment) {
?>
    <tr>
        <td class="list"><a href="mailto:<?php print $newscomment['email']; ?>"><?php print $newscomment['user']; ?></td>
        <td class="list"><?php print $newscomment['content']; ?></td>
        <td class="list"><a href="?action=delcomment&amp;artid=<?php print $newscomment['record_id']; ?>&amp;cmtid=<?php print $newscomment['comment_id']; ?>"><img src="images/delete.gif" alt="<?php print $PMF_LANG["ad_entry_delete"] ?>" title="<?php print $PMF_LANG["ad_entry_delete"] ?>" border="0" width="17" height="18" align="right" /></a></td>
    </tr>
<?php
    }
?>
    </tbody>
    </table>
<?php

} else {
    print $PMF_LANG["err_NotAuth"];
}