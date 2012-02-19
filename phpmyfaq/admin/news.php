<?php
/**
 * The main administration file for the news.
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
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$news = new PMF_News($db, $Language);

// Re-evaluate $user
$user = PMF_User_CurrentUser::getFromSession($faqconfig->get('security.ipCheck'));

if ('addnews' == $action && $permission["addnews"]) {
?>
        <header>
            <h2><?php print $PMF_LANG['ad_news_add']; ?></h2>
        </header>

        <form id="faqEditor" name="faqEditor" action="?action=savenews" method="post">
        <fieldset>
            <legend><?php print $PMF_LANG['ad_news_data']; ?></legend>

            <p>
                <label class="control-label" for="newsheader"><?php print $PMF_LANG['ad_news_header']; ?></label>
                <input type="text" name="newsheader" id="newsheader" style="width: 300px;" autofocus="autofocus" />
            </p>

            <p>
                <label class="control-label" for="news"><?php print $PMF_LANG['ad_news_text']; ?>:</label>
            </p>
                <noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript>
                <textarea id="news" name="news" cols="84" rows="5"></textarea>

            <p>
                <label class="control-label" for="authorName"><?php print $PMF_LANG['ad_news_author_name']; ?></label>
                <input type="text" name="authorName" id="authorName" style="width: 300px;" value="<?php print $user->getUserData('display_name'); ?>"/>
            </p>

            <p>
                <label class="control-label" for="authorEmail"><?php print $PMF_LANG['ad_news_author_email']; ?></label>
                <input type="email" name="authorEmail" id="authorEmail" style="width: 300px;" value="<?php print $user->getUserData('email'); ?>"/>
            </p>

            <p>
                <label class="control-label" for="active"><?php print $PMF_LANG['ad_news_set_active']; ?></label>
                <input type="checkbox" name="active" id="active" value="y" /><?php print $PMF_LANG['ad_gen_yes']; ?>
            </p>

            <p>
                <label class="control-label" for="comment"><?php print $PMF_LANG['ad_news_allowComments']; ?></label>
                <input type="checkbox" name="comment" id="comment" value="y" /><?php print $PMF_LANG['ad_gen_yes']; ?>
            </p>

            <p>
                <label class="control-label" for="link"><?php print $PMF_LANG['ad_news_link_url']; ?></label>
                <input type="text" name="link" id="link" style="width: 300px;" value="http://" />
            </p>

            <p>
                <label class="control-label" for="linkTitle"><?php print $PMF_LANG['ad_news_link_title']; ?></label>
                <input type="text" name="linkTitle" id="linkTitle" style="width: 300px;" />
            </p>

            <p>
                <label class="control-label" ><?php print $PMF_LANG['ad_news_link_target']; ?></label>
                <input type="radio" name="target" value="blank" /><?php print $PMF_LANG['ad_news_link_window'] ?>
                <input type="radio" name="target" value="self" /><?php print $PMF_LANG['ad_news_link_faq'] ?>
                <input type="radio" name="target" value="parent" /><?php print $PMF_LANG['ad_news_link_parent'] ?>
            </p>
            <p>
                <label class="control-label" for="langTo"><?php print $PMF_LANG["ad_entry_locale"]; ?>:</label>
                <?php print PMF_Language::selectLanguages($LANGCODE, false, array(), 'langTo'); ?>
            </p>
        </fieldset>
            
        <fieldset>
            <legend><?php print $PMF_LANG['ad_news_expiration_window']; ?></legend>
            <p>
                <label class="control-label" for="dateStart"><?php print $PMF_LANG['ad_news_from']; ?></label>
                <input name="dateStart" id="dateStart" class="date-pick" />
            </p>

            <p>
                <label class="control-label" for="dateEnd"><?php print $PMF_LANG['ad_news_to']; ?></label>
                <input name="dateEnd" id="dateEnd" class="date-pick" />
            </fieldset>

            </p>
                <input class="submit" type="submit" value="<?php print $PMF_LANG['ad_news_add']; ?>" />
                <input class="submit" type="reset" value="<?php print $PMF_LANG['ad_gen_reset']; ?>" />
            <p>
        </form>
<?php
} elseif ('news' == $action && $permission["editnews"]) {
?>
        <header>
            <h2><?php print $PMF_LANG["msgNews"]; ?></h2>
        </header>
    
        <p><a href="?action=addnews"><?php print $PMF_LANG["ad_menu_news_add"]; ?></a></p>
        <table class="table table-striped">
        <thead>
            <tr>
                <th><?php print $PMF_LANG["ad_news_headline"]; ?></th>
                <th><?php print $PMF_LANG["ad_news_date"]; ?></th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
<?php
        $newsHeader = $news->getNewsHeader();
        if (count($newsHeader)) {
            foreach($newsHeader as $newsItem) {
?>
        <tr>
            <td><?php print $newsItem['header']; ?></td>
            <td><?php print PMF_Date::format($newsItem['date']); ?></td>
            <td>
                <a href="?action=editnews&amp;id=<?php print $newsItem['id']; ?>" title="<?php print $PMF_LANG["ad_news_update"]; ?>">
                    <img src="images/edit.png" width="16" height="16" alt="<?php print $PMF_LANG["ad_news_update"]; ?>" border="0" />
                </a>
                &nbsp;&nbsp;
                <a href="?action=deletenews&amp;id=<?php print $newsItem['id']; ?>" title="<?php print $PMF_LANG["ad_news_delete"]; ?>">
                    <img src="images/delete.png" width="16" height="16" alt="<?php print $PMF_LANG["ad_news_delete"]; ?>" border="0" />
                </a>
            </td>
        </tr>
<?php
            }
        } else {
            printf('<tr><td colspan="3">%s</td></tr>',
                $PMF_LANG['ad_news_nodata']);
        }
?>
        </tbody>
        </table>
<?php
} elseif ('editnews' == $action && $permission['editnews']) {
    $id       = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $newsData = $news->getNewsEntry($id, true);
?>
        <header>
            <h2><?php print $PMF_LANG['ad_news_edit']; ?></h2>
        </header>

        <form action="?action=updatenews" method="post">
        <fieldset>
        <legend><?php print $PMF_LANG['ad_news_data']; ?></legend>
            <input type="hidden" name="id" value="<?php print $newsData['id']; ?>" />

            <p>
                <label class="control-label" for="newsheader"><?php print $PMF_LANG['ad_news_header']; ?></label>
                <input type="text" name="newsheader" id="newsheader" style="width: 300px;"
                       value="<?php if (isset($newsData['header'])) { print $newsData['header']; } ?>"
                       autofocus="autofocus" />
            </p>

            <p>
                <label class="control-label" for="news"><?php print $PMF_LANG['ad_news_text']; ?>:</label>
            </p>
                <noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript>
                <textarea id="news" name="news" cols="84" rows="5"><?php if (isset($newsData['content'])) { print htmlspecialchars($newsData['content'], ENT_QUOTES); } ?></textarea>
            </p>

            <p>
                <label class="control-label" for="authorName"><?php print $PMF_LANG['ad_news_author_name']; ?></label>
                <input type="text" name="authorName" style="width: 390px;" value="<?php print $newsData['authorName']; ?>" />
            </p>

            <p>
                <label class="control-label" for="authorEmail"><?php print $PMF_LANG['ad_news_author_email']; ?></label>
                <input type="text" name="authorEmail" style="width: 390px;" value="<?php print $newsData['authorEmail']; ?>" />
            </p>

            <p>
                <label class="control-label" for="active"><?php print $PMF_LANG['ad_news_set_active']; ?></label>
                <input type="checkbox" name="active" id="active" value="y"<?php if (isset($newsData['active']) && $newsData['active']) { print " checked"; } ?> /><?php print $PMF_LANG['ad_gen_yes']; ?>
            </p>

            <p>
                <label class="control-label" for="comment"><?php print $PMF_LANG['ad_news_allowComments']; ?></label>
                <input type="checkbox" name="comment" id="comment" value="y"<?php if (isset($newsData['allowComments']) && $newsData['allowComments']) { print " checked"; } ?> /><?php print $PMF_LANG['ad_gen_yes']; ?>
            </p>

            <p>
                <label class="control-label" for="link"><?php print $PMF_LANG['ad_news_link_url']; ?></label>
                <input type="text" name="link" style="width: 390px;" value="<?php print $newsData['link']; ?>" />
            </p>

            <p>
                <label class="control-label" for="linkTitle"><?php print $PMF_LANG['ad_news_link_title']; ?></label>
                <input type="text" name="linkTitle" style="width: 390px;" value="<?php print $newsData['linkTitle']; ?>" />
            </p>

            <p>
                <label class="control-label" for="linkTarget"><?php print $PMF_LANG['ad_news_link_target']; ?></label>
                <input type="radio" name="target" value="blank" <?php if ('blank' == $newsData['target']) { ?>
                       checked="checked"<?php } ?> /><?php print $PMF_LANG['ad_news_link_window'] ?>
                <input type="radio" name="target" value="self" <?php if ('self' == $newsData['target']) { ?>
                       checked="checked"<?php } ?> /><?php print $PMF_LANG['ad_news_link_faq'] ?>
                <input type="radio" name="target" value="parent" <?php if ('parent' == $newsData['target']) { ?>
                       checked="checked"<?php } ?> /><?php print $PMF_LANG['ad_news_link_parent'] ?>
            </p>
            <p>
                <label class="control-label" for="langTo"><?php print $PMF_LANG["ad_entry_locale"]; ?>:</label>
                <?php print PMF_Language::selectLanguages($newsData['lang'], false, array(), 'langTo'); ?>
            </p>
        </fieldset>

<?php
    $dateStart = ($newsData['dateStart'] != '00000000000000' ? PMF_Date::createIsoDate($newsData['dateStart'], 'Y-m-d') : '');
    $dateEnd   = ($newsData['dateEnd'] != '99991231235959' ? PMF_Date::createIsoDate($newsData['dateEnd'], 'Y-m-d') : '');
?>

        <fieldset>
            <legend><?php print $PMF_LANG['ad_news_expiration_window']; ?></legend>
            <p>
                <label class="control-label" for="dateStart"><?php print $PMF_LANG['ad_news_from']; ?></label>
                <input name="dateStart" id="dateStart" class="date-pick" value="<?php print $dateStart; ?>" />
            </p>

            <p>
                <label class="control-label" for="dateEnd"><?php print $PMF_LANG['ad_news_to']; ?></label>
                <input name="dateEnd" id="dateEnd" class="date-pick" value="<?php print $dateEnd; ?>" />
            </p>
        </fieldset>

        <p>
            <input class="submit" type="submit" value="<?php print $PMF_LANG['ad_news_edit']; ?>" />
            <input class="submit" type="reset" value="<?php print $PMF_LANG['ad_gen_reset']; ?>" />
        </p>
        </form>
<?php
    $newsId   = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $oComment = new PMF_Comment();
    $comments = $oComment->getCommentsData($newsId, PMF_Comment::COMMENT_TYPE_NEWS);
    if (count($comments) > 0) {
?>
            <p><strong><?php print $PMF_LANG["ad_entry_comment"] ?></strong></p>
<?php
    }
    foreach ($comments as $item) {
?>
    <p><?php print $PMF_LANG["ad_entry_commentby"] ?> <a href="mailto:<?php print($item['email']); ?>"><?php print($item['user']); ?></a>:<br /><?php print($item['content']); ?><br /><?php print($PMF_LANG['newsCommentDate'].PMF_Date::createIsoDate($item['date'], 'Y-m-d H:i', false)); ?><a href="?action=delcomment&amp;artid=<?php print($newsId); ?>&amp;cmtid=<?php print($item['id']); ?>&amp;type=<?php print(PMF_Comment::COMMENT_TYPE_NEWS);?>"><img src="images/delete.gif" alt="<?php print $PMF_LANG["ad_entry_delete"] ?>" title="<?php print $PMF_LANG["ad_entry_delete"] ?>" border="0" width="17" height="18" align="right" /></a></p>
<?php
    }
} elseif ('savenews' == $action && $permission["addnews"]) {

    $dateStart = PMF_Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd   = PMF_Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $header    = PMF_Filter::filterInput(INPUT_POST, 'newsheader', FILTER_SANITIZE_STRIPPED);
    $content   = PMF_Filter::filterInput(INPUT_POST, 'news', FILTER_SANITIZE_SPECIAL_CHARS);
    $author    = PMF_Filter::filterInput(INPUT_POST, 'authorName', FILTER_SANITIZE_STRIPPED);
    $email     = PMF_Filter::filterInput(INPUT_POST, 'authorEmail', FILTER_VALIDATE_EMAIL);
    $active    = PMF_Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING);
    $comment   = PMF_Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $link      = PMF_Filter::filterInput(INPUT_POST, 'link', FILTER_VALIDATE_URL);
    $linktitle = PMF_Filter::filterInput(INPUT_POST, 'linkTitle', FILTER_SANITIZE_STRIPPED);
    $newslang  = PMF_Filter::filterInput(INPUT_POST, 'langTo', FILTER_SANITIZE_STRING);
    $target    = PMF_Filter::filterInput(INPUT_POST, 'target', FILTER_SANITIZE_STRIPPED);
    
    $newsData = array(
        'lang'          => $newslang,
        'header'        => $header,
        'content'       => html_entity_decode($content),
        'authorName'    => $author,
        'authorEmail'   => $email,
        'active'        => (is_null($active)) ? 'n' : 'y',
        'comment'       => (is_null($comment)) ? 'n' : 'y',
        'dateStart'     => (empty($dateStart)) ? '00000000000000' : str_replace('-', '', $dateStart) . '000000',
        'dateEnd'       => (empty($dateEnd))  ? '99991231235959' : str_replace('-', '', $dateEnd) . '235959',
        'link'          => $link,
        'linkTitle'     => $linktitle,
        'date'          => date('YmdHis'),
        'target'        => (is_null($target)) ? '' : $target
    );

    if ($news->addNewsEntry($newsData)) {
        printf('<p class="success">%s</p>', $PMF_LANG['ad_news_updatesuc']);
    } else {
        printf('<p class="error">%s</p>', $PMF_LANG['ad_news_insertfail']);
    }
    printf('<p>&rarr; <a href="?action=news">%s</a></p>', $PMF_LANG['msgNews']);
} elseif ('updatenews' == $action && $permission["editnews"]) {

    $dateStart = PMF_Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd   = PMF_Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $header    = PMF_Filter::filterInput(INPUT_POST, 'newsheader', FILTER_SANITIZE_STRIPPED);
    $content   = PMF_Filter::filterInput(INPUT_POST, 'news', FILTER_SANITIZE_SPECIAL_CHARS);
    $author    = PMF_Filter::filterInput(INPUT_POST, 'authorName', FILTER_SANITIZE_STRIPPED);
    $email     = PMF_Filter::filterInput(INPUT_POST, 'authorEmail', FILTER_VALIDATE_EMAIL);
    $active    = PMF_Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING);
    $comment   = PMF_Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $link      = PMF_Filter::filterInput(INPUT_POST, 'link', FILTER_VALIDATE_URL);
    $linktitle = PMF_Filter::filterInput(INPUT_POST, 'linkTitle', FILTER_SANITIZE_STRIPPED);
    $newslang  = PMF_Filter::filterInput(INPUT_POST, 'langTo', FILTER_SANITIZE_STRING);
    $target    = PMF_Filter::filterInput(INPUT_POST, 'target', FILTER_SANITIZE_STRIPPED);

    $newsData = array(
        'lang'          => $newslang,
        'header'        => $header,
        'content'       => html_entity_decode($content),
        'authorName'    => $author,
        'authorEmail'   => $email,
        'active'        => (is_null($active)) ? 'n' : 'y',
        'comment'       => (is_null($comment)) ? 'n' : 'y',
        'dateStart'     => (empty($dateStart)) ? '00000000000000' : str_replace('-', '', $dateStart) . '000000',
        'dateEnd'       => (empty($dateEnd))   ? '99991231235959' : str_replace('-', '', $dateEnd) . '235959',
        'link'          => $link,
        'linkTitle'     => $linktitle,
        'date'          => date('YmdHis'),
        'target'        => (is_null($target)) ? '' : $target);
    
    $newsId = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($news->updateNewsEntry($newsId, $newsData)) {
        printf('<p class="success">%s</p>', $PMF_LANG['ad_news_updatesuc']);
    } else {
        printf('<p class="error">%s</p>', $PMF_LANG['ad_news_updatefail']);
    }
    printf('<p>&rarr; <a href="?action=news">%s</a></p>', $PMF_LANG['msgNews']);
} elseif ('deletenews' == $action && $permission["delnews"]) {

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
    
    <script type="text/javascript">
    /* <![CDATA[ */
    $(function()
    {
        $('.date-pick').datePicker();
    });
    /* ]]> */
    </script>
<?php
    } else {
        $delete_id = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $news->deleteNews($delete_id);
        printf('<p class="success">%s</p>', $PMF_LANG['ad_news_delsuc']);
        printf('<p>&rarr; <a href="?action=news">%s</a></p>', $PMF_LANG['msgNews']);
    }
} else {
    print $PMF_LANG["err_NotAuth"];
}
