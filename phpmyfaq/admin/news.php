<?php
/**
 * The main administration file for the news.
 *
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @since      2003-02-23 
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

$news = new PMF_News($db, $LANGCODE);

// Re-evaluate $user
$user = PMF_User_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));

if ('addnews' == $_action && $permission["addnews"]) {
?>
    <h2><?php print $PMF_LANG['ad_news_add']; ?></h2>
    <form id="faqEditor" name="faqEditor" action="?action=savenews" method="post">
    <fieldset>
    <legend><?php print $PMF_LANG['ad_news_data']; ?></legend>

        <label class="lefteditor" for="header"><?php print $PMF_LANG['ad_news_header']; ?></label>
        <textarea name="header" style="width: 390px; height: 50px;" cols="2" rows="50"></textarea><br />

        <label for="content"><?php print $PMF_LANG['ad_news_text']; ?></label>
        <noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript><textarea id="content" name="content" cols="84" rows="5"></textarea><br />

        <label class="lefteditor" for="authorName"><?php print $PMF_LANG['ad_news_author_name']; ?></label>
        <input type="text" name="authorName" style="width: 390px;" value="<?php print $user->getUserData('display_name'); ?>"/><br />

        <label class="lefteditor" for="authorEmail"><?php print $PMF_LANG['ad_news_author_email']; ?></label>
        <input type="text" name="authorEmail" style="width: 390px;" value="<?php print $user->getUserData('email'); ?>"/><br />

        <label class="lefteditor" for="active"><?php print $PMF_LANG['ad_news_set_active']; ?></label>
        <input type="checkbox" name="active" id="active" value="y" /><?php print $PMF_LANG['ad_gen_yes']; ?><br />

        <label class="lefteditor" for="comment"><?php print $PMF_LANG['ad_news_allowComments']; ?></label>
        <input type="checkbox" name="comment" id="comment" value="y" /><?php print $PMF_LANG['ad_gen_yes']; ?><br />

        <label class="lefteditor" for="link"><?php print $PMF_LANG['ad_news_link_url']; ?></label>
        <input type="text" name="link" style="width: 390px;" /><br />

        <label class="lefteditor" for="linkTitle"><?php print $PMF_LANG['ad_news_link_title']; ?></label>
        <input type="text" name="linkTitle" style="width: 390px;" /><br />

        <label class="lefteditor" for="linkTarget"><?php print $PMF_LANG['ad_news_link_target']; ?></label>
        <input type="radio" name="target" value="blank" /><?php print $PMF_LANG['ad_news_link_window'] ?>
        <input type="radio" name="target" value="self" /><?php print $PMF_LANG['ad_news_link_faq'] ?>
        <input type="radio" name="target" value="parent" /><?php print $PMF_LANG['ad_news_link_parent'] ?><br />
    </fieldset>
    <fieldset>
    <legend><?php print $PMF_LANG['ad_news_expiration_window']; ?></legend>
        <label class="lefteditor" for="from"><?php print $PMF_LANG['ad_news_from']; ?></label>
<?php
    $dateStartAv = isset($newsData['dateStart']) && ($newsData['dateStart'] != '00000000000000');
    $date['YYYY'] = $dateStartAv ? substr($newsData['dateStart'],  0, 4) : '';
    $date['MM']   = $dateStartAv ? substr($newsData['dateStart'],  4, 2) : '';
    $date['DD']   = $dateStartAv ? substr($newsData['dateStart'],  6, 2) : '';
    $date['HH']   = $dateStartAv ? substr($newsData['dateStart'],  8, 2) : '';
    $date['mm']   = $dateStartAv ? substr($newsData['dateStart'], 10, 2) : '';
    $date['ss']   = $dateStartAv ? substr($newsData['dateStart'], 12, 2) : '';
    print(printDateTimeInput('dateStart', $date));
?>
        <br />

        <label class="lefteditor" for="to"><?php print $PMF_LANG['ad_news_to']; ?></label>
<?php
    $dateEndAv = isset($newsData['dateEnd']) && ($newsData['dateEnd'] != '99991231235959');
    $date['YYYY'] = $dateEndAv ? substr($newsData['dateEnd'],  0, 4) : '';
    $date['MM']   = $dateEndAv ? substr($newsData['dateEnd'],  4, 2) : '';
    $date['DD']   = $dateEndAv ? substr($newsData['dateEnd'],  6, 2) : '';
    $date['HH']   = $dateEndAv ? substr($newsData['dateEnd'],  8, 2) : '';
    $date['mm']   = $dateEndAv ? substr($newsData['dateEnd'], 10, 2) : '';
    $date['ss']   = $dateEndAv ? substr($newsData['dateEnd'], 12, 2) : '';
    print(printDateTimeInput('dateEnd', $date));
?>
    </fieldset>
    <br />
    <input class="submit" type="submit" value="<?php print $PMF_LANG['ad_news_add']; ?>" />
    <input class="submit" type="reset" value="<?php print $PMF_LANG['ad_gen_reset']; ?>" />
    </form>
<?php
} elseif ('news' == $_action && $permission["editnews"]) {
?>
    <table class="list">
    <thead>
        <tr>
            <th class="list"><?php print $PMF_LANG["ad_news_headline"]; ?></th>
            <th class="list"><?php print $PMF_LANG["ad_news_date"]; ?></th>
            <th class="list">&nbsp;</th>
        </tr>
    </thead>
    <tbody>
<?php
        $newsHeader = $news->getNewsHeader();
        if (count($newsHeader)) {
            foreach($newsHeader as $newsItem) {
?>
        <tr>
            <td class="list"><?php print $newsItem['header']; ?></td>
            <td class="list"><?php print $newsItem['date']; ?></td>
            <td class="list"><a href="?action=editnews&amp;id=<?php print $newsItem['id']; ?>" title="<?php print $PMF_LANG["ad_news_update"]; ?>"><img src="images/edit.gif" width="18" height="18" alt="<?php print $PMF_LANG["ad_news_update"]; ?>" border="0" /></a>&nbsp;&nbsp;<a href="?action=deletenews&amp;id=<?php print $newsItem['id']; ?>" title="<?php print $PMF_LANG["ad_news_delete"]; ?>"><img src="images/delete.gif" width="17" height="18" alt="<?php print $PMF_LANG["ad_news_delete"]; ?>" border="0" /></a></td>
        </tr>
<?php
            }
        } else {
            printf('<tr><td colspan="3" class="list">%s</td></tr>',
                $PMF_LANG['ad_news_nodata']);
        }
?>
    </tbody>
    </table>
    <p><a href="?action=addnews"><?php print $PMF_LANG["ad_menu_news_add"]; ?></a></p>
<?php
} elseif ('editnews' == $_action && $permission['editnews']) {
    $id = (int)$_REQUEST['id'];
    $newsData = $news->getNewsEntry($id, true);
?>
    <h2><?php print $PMF_LANG['ad_news_edit']; ?></h2>
    <form  style="float: left;" id="faqEditor" name="faqEditor" action="?action=updatenews" method="post">
    <fieldset>
    <legend><?php print $PMF_LANG['ad_news_data']; ?></legend>
        <input type="hidden" name="id" value="<?php print $newsData['id']; ?>" />

        <label class="lefteditor" for="header"><?php print $PMF_LANG['ad_news_header']; ?></label>
        <textarea name="header" style="width: 390px; height: 50px;" cols="2" rows="50"><?php if (isset($newsData['header'])) { print $newsData['header']; } ?></textarea><br />

        <label for="content"><?php print $PMF_LANG['ad_news_text']; ?></label>
        <noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript><textarea id="content" name="content" cols="84" rows="5"><?php if (isset($newsData['content'])) { print htmlspecialchars($newsData['content'], ENT_QUOTES); } ?></textarea><br />

        <label class="lefteditor" for="authorName"><?php print $PMF_LANG['ad_news_author_name']; ?></label>
        <input type="text" name="authorName" style="width: 390px;" value="<?php print $newsData['authorName']; ?>" /><br />

        <label class="lefteditor" for="authorEmail"><?php print $PMF_LANG['ad_news_author_email']; ?></label>
        <input type="text" name="authorEmail" style="width: 390px;" value="<?php print $newsData['authorEmail']; ?>" /><br />

        <label class="lefteditor" for="active"><?php print $PMF_LANG['ad_news_set_active']; ?></label>
        <input type="checkbox" name="active" id="active" value="y"<?php if (isset($newsData['active']) && $newsData['active']) { print " checked"; } ?> /><?php print $PMF_LANG['ad_gen_yes']; ?><br />

        <label class="lefteditor" for="comment"><?php print $PMF_LANG['ad_news_allowComments']; ?></label>
        <input type="checkbox" name="comment" id="comment" value="y"<?php if (isset($newsData['allowComments']) && $newsData['allowComments']) { print " checked"; } ?> /><?php print $PMF_LANG['ad_gen_yes']; ?><br />

        <label class="lefteditor" for="link"><?php print $PMF_LANG['ad_news_link_url']; ?></label>
        <input type="text" name="link" style="width: 390px;" value="<?php print $newsData['link']; ?>" /><br />

        <label class="lefteditor" for="linkTitle"><?php print $PMF_LANG['ad_news_link_title']; ?></label>
        <input type="text" name="linkTitle" style="width: 390px;" value="<?php print $newsData['linkTitle']; ?>" /><br />

        <label class="lefteditor" for="linkTarget"><?php print $PMF_LANG['ad_news_link_target']; ?></label>
        <input type="radio" name="target" value="blank" <?php if ('blank' == $newsData['target']) { ?> checked="checked"<?php } ?> /><?php print $PMF_LANG['ad_news_link_window'] ?>
        <input type="radio" name="target" value="self" <?php if ('self' == $newsData['target']) { ?> checked="checked"<?php } ?> /><?php print $PMF_LANG['ad_news_link_faq'] ?>
        <input type="radio" name="target" value="parent" <?php if ('parent' == $newsData['target']) { ?> checked="checked"<?php } ?> /><?php print $PMF_LANG['ad_news_link_parent'] ?><br />
    </fieldset>
    <fieldset>
    <legend><?php print $PMF_LANG['ad_news_expiration_window']; ?></legend>
        <label class="lefteditor" for="from"><?php print $PMF_LANG['ad_news_from']; ?></label>
<?php
        $dateStartAv = isset($newsData['dateStart']) && ($newsData['dateStart'] != '00000000000000');
        $date['YYYY'] = $dateStartAv ? substr($newsData['dateStart'],  0, 4) : '';
        $date['MM']   = $dateStartAv ? substr($newsData['dateStart'],  4, 2) : '';
        $date['DD']   = $dateStartAv ? substr($newsData['dateStart'],  6, 2) : '';
        $date['HH']   = $dateStartAv ? substr($newsData['dateStart'],  8, 2) : '';
        $date['mm']   = $dateStartAv ? substr($newsData['dateStart'], 10, 2) : '';
        $date['ss']   = $dateStartAv ? substr($newsData['dateStart'], 12, 2) : '';
        print(printDateTimeInput('dateStart', $date));
?>
        <br />

        <label class="lefteditor" for="to"><?php print $PMF_LANG['ad_news_to']; ?></label>
<?php
        $dateEndAv = isset($newsData['dateEnd']) && ($newsData['dateEnd'] != '99991231235959');
        $date['YYYY'] = $dateEndAv ? substr($newsData['dateEnd'],  0, 4) : '';
        $date['MM']   = $dateEndAv ? substr($newsData['dateEnd'],  4, 2) : '';
        $date['DD']   = $dateEndAv ? substr($newsData['dateEnd'],  6, 2) : '';
        $date['HH']   = $dateEndAv ? substr($newsData['dateEnd'],  8, 2) : '';
        $date['mm']   = $dateEndAv ? substr($newsData['dateEnd'], 10, 2) : '';
        $date['ss']   = $dateEndAv ? substr($newsData['dateEnd'], 12, 2) : '';
        print(printDateTimeInput('dateEnd', $date));
?>
    </fieldset>
    <br />
    <input class="submit" type="submit" value="<?php print $PMF_LANG['ad_news_edit']; ?>" />
    <input class="submit" type="reset" value="<?php print $PMF_LANG['ad_gen_reset']; ?>" />
    </form>
<?php
    $newsId = (int)$_GET['id'];
    $oComment = new PMF_Comment($db, $LANGCODE);
    $comments = $oComment->getCommentsData($newsId, PMF_Comment::COMMENT_TYPE_NEWS);
    if (count($comments) > 0) {
?>
            <p><strong><?php print $PMF_LANG["ad_entry_comment"] ?></strong></p>
<?php
    }
    foreach ($comments as $item) {
?>
    <p><?php print $PMF_LANG["ad_entry_commentby"] ?> <a href="mailto:<?php print($item['email']); ?>"><?php print($item['user']); ?></a>:<br /><?php print($item['content']); ?><br /><?php print($PMF_LANG['newsCommentDate'].makeCommentDate($item['date'])); ?><a href="?action=delcomment&amp;artid=<?php print($newsId); ?>&amp;cmtid=<?php print($item['id']); ?>&amp;type=<?php print(PMF_Comment::COMMENT_TYPE_NEWS);?>"><img src="images/delete.gif" alt="<?php print $PMF_LANG["ad_entry_delete"] ?>" title="<?php print $PMF_LANG["ad_entry_delete"] ?>" border="0" width="17" height="18" align="right" /></a></p>
<?php
    }
} elseif ('savenews' == $_action && $permission["addnews"]) {

    // Evaluate the passed validity range, if any
    $dateStart =
        (isset($_POST['dateStartYYYY']) && !empty($_POST['dateStartYYYY']) ? str_pad((int)$_POST['dateStartYYYY'], 4, '0', STR_PAD_LEFT) : '0001') .
        (isset($_POST['dateStartMM']) && !empty($_POST['dateStartMM']) ? str_pad((int)$_POST['dateStartMM'], 2, '0', STR_PAD_LEFT) : '01') .
        (isset($_POST['dateStartDD']) && !empty($_POST['dateStartDD']) ? str_pad((int)$_POST['dateStartDD'], 2, '0', STR_PAD_LEFT) : '01') .
        (isset($_POST['dateStartHH']) && !empty($_POST['dateStartHH']) ? str_pad((int)$_POST['dateStartHH'], 2, '0', STR_PAD_LEFT) : '00') .
        (isset($_POST['dateStartmm']) && !empty($_POST['dateStartss']) ? str_pad((int)$_POST['dateStartmm'], 2, '0', STR_PAD_LEFT) : '00') .
        (isset($_POST['dateStartss']) && !empty($_POST['dateStartMM']) ? str_pad((int)$_POST['dateStartss'], 2, '0', STR_PAD_LEFT) : '00');
    $dateEnd =
        (isset($_POST['dateEndYYYY']) ? str_pad((int)$_POST['dateEndYYYY'], 4, '0', STR_PAD_LEFT) : '0000') .
        (isset($_POST['dateEndMM']) && !empty($_POST['dateEndMM']) ? str_pad((int)$_POST['dateEndMM'], 2, '0', STR_PAD_LEFT) : '01') .
        (isset($_POST['dateEndDD']) && !empty($_POST['dateEndDD']) ? str_pad((int)$_POST['dateEndDD'], 2, '0', STR_PAD_LEFT) : '01') .
        (isset($_POST['dateEndHH']) && !empty($_POST['dateEndHH']) ? str_pad((int)$_POST['dateEndHH'], 2, '0', STR_PAD_LEFT) : '00') .
        (isset($_POST['dateEndmm']) && !empty($_POST['dateEndmm']) ? str_pad((int)$_POST['dateEndmm'], 2, '0', STR_PAD_LEFT) : '00') .
        (isset($_POST['dateEndss']) && !empty($_POST['dateEndss']) ? str_pad((int)$_POST['dateEndss'], 2, '0', STR_PAD_LEFT) : '00');

    // Sanity checks
    if ('00000101000000' == $dateEnd) {
        $dateEnd = '99991231235959';
    }
        $newsData = array(
        'lang'          => $LANGCODE,
        'header'        => $db->escape_string($_POST['header']),
        'content'       => $db->escape_string($_POST['content']),
        'authorName'    => $db->escape_string($_POST['authorName']),
        'authorEmail'   => $db->escape_string($_POST['authorEmail']),
        'active'        => (isset($_POST['active'])) ? $db->escape_string($_POST['active']) : 'n',
        'comment'       => (isset($_POST['comment'])) ? $db->escape_string($_POST['comment']) : 'n',
        'dateStart'     => ('' == $dateStart) ? '00000000000000' : $db->escape_string($dateStart),
        'dateEnd'       => ('' == $dateEnd)   ? '99991231235959' : $db->escape_string($dateEnd),
        'link'          => $db->escape_string($_POST['link']),
        'linkTitle'     => $db->escape_string($_POST['linkTitle']),
        'date'          => date('YmdHis'),
        'target'        => (!isset($_POST['target'])) ? '' : $db->escape_string($_POST['target'])
        );

    if ($news->addNewsEntry($newsData)) {
        printf("<p>%s</p>", $PMF_LANG['ad_news_updatesuc']);
    } else {
        printf("<p>%s</p>", $PMF_LANG['ad_news_insertfail']);
    }
} elseif ('updatenews' == $_action && $permission["editnews"]) {

    // Evaluate the passed validity range, if any
    $dateStart =
        (isset($_POST['dateStartYYYY']) && !empty($_POST['dateStartYYYY']) ? str_pad((int)$_POST['dateStartYYYY'], 4, '0', STR_PAD_LEFT) : '0001') .
        (isset($_POST['dateStartMM']) && !empty($_POST['dateStartMM']) ? str_pad((int)$_POST['dateStartMM'], 2, '0', STR_PAD_LEFT) : '01') .
        (isset($_POST['dateStartDD']) && !empty($_POST['dateStartDD']) ? str_pad((int)$_POST['dateStartDD'], 2, '0', STR_PAD_LEFT) : '01') .
        (isset($_POST['dateStartHH']) && !empty($_POST['dateStartHH']) ? str_pad((int)$_POST['dateStartHH'], 2, '0', STR_PAD_LEFT) : '00') .
        (isset($_POST['dateStartmm']) && !empty($_POST['dateStartss']) ? str_pad((int)$_POST['dateStartmm'], 2, '0', STR_PAD_LEFT) : '00') .
        (isset($_POST['dateStartss']) && !empty($_POST['dateStartMM']) ? str_pad((int)$_POST['dateStartss'], 2, '0', STR_PAD_LEFT) : '00');
    $dateEnd =
        (isset($_POST['dateEndYYYY']) ? str_pad((int)$_POST['dateEndYYYY'], 4, '0', STR_PAD_LEFT) : '0000') .
        (isset($_POST['dateEndMM']) && !empty($_POST['dateEndMM']) ? str_pad((int)$_POST['dateEndMM'], 2, '0', STR_PAD_LEFT) : '01') .
        (isset($_POST['dateEndDD']) && !empty($_POST['dateEndDD']) ? str_pad((int)$_POST['dateEndDD'], 2, '0', STR_PAD_LEFT) : '01') .
        (isset($_POST['dateEndHH']) && !empty($_POST['dateEndHH']) ? str_pad((int)$_POST['dateEndHH'], 2, '0', STR_PAD_LEFT) : '00') .
        (isset($_POST['dateEndmm']) && !empty($_POST['dateEndmm']) ? str_pad((int)$_POST['dateEndmm'], 2, '0', STR_PAD_LEFT) : '00') .
        (isset($_POST['dateEndss']) && !empty($_POST['dateEndss']) ? str_pad((int)$_POST['dateEndss'], 2, '0', STR_PAD_LEFT) : '00');

    // Sanity checks
    if ('00000101000000' == $dateEnd) {
        $dateEnd = '99991231235959';
    }
    $newsData = array(
        'lang'          => $LANGCODE,
        'header'        => $db->escape_string($_POST['header']),
        'content'       => $db->escape_string($_POST['content']),
        'authorName'    => $db->escape_string($_POST['authorName']),
        'authorEmail'   => $db->escape_string($_POST['authorEmail']),
        'active'        => (isset($_POST['active'])) ? $db->escape_string($_POST['active']) : 'n',
        'comment'       => (isset($_POST['comment'])) ? $db->escape_string($_POST['comment']) : 'n',
        'dateStart'     => ('' == $dateStart) ? '00000000000000' : $db->escape_string($dateStart),
        'dateEnd'       => ('' == $dateEnd)   ? '99991231235959' : $db->escape_string($dateEnd),
        'link'          => $db->escape_string($_POST['link']),
        'linkTitle'     => $db->escape_string($_POST['linkTitle']),
        'date'          => date('YmdHis'),
        'target'        => (!isset($_POST['target'])) ? '' : $db->escape_string($_POST['target'])
        );

    $newsId = (int)$_POST['id'];
    if ($news->updateNewsEntry($newsId, $newsData)) {
        printf("<p>%s</p>", $PMF_LANG['ad_news_updatesuc']);
    } else {
        printf("<p>%s</p>", $PMF_LANG['ad_news_updatefail']);
    }
} elseif ('deletenews' == $_action && $permission["delnews"]) {
	
	$precheck  = PMF_Filter::filterInput(INPUT_POST, 'really', FILTER_SANITIZE_STRING, 'no');
	$delete_id = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
	
    if ('no' == $precheck) {
?>
    <p><?php print $PMF_LANG["ad_news_del"]; ?></p>
    <div align="center">
    <form action="?action=deletenews" method="post">
    <input type="hidden" name="id" value="<?php print $delete_id; ?>" />
    <input type="hidden" name="really" value="yes" />
    <input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["ad_news_yesdelete"]; ?>" style="color: Red;" />
    <input class="submit" type="reset" onclick="javascript:history.back();" value="<?php print $PMF_LANG["ad_news_nodelete"]; ?>" />
    </form>
    </div>
<?php
    } else {
    	$delete_id = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $news->deleteNews($delete_id);
        print "<p>".$PMF_LANG["ad_news_delsuc"]."</p>";
    }
} else {
    print $PMF_LANG["err_NotAuth"];
}
