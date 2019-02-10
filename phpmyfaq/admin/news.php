<?php
/**
 * The main administration file for the news.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-23
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$news = new PMF_News($faqConfig);

$csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
    $csrfCheck = false;
} else {
    $csrfCheck = true;
}

if ('addnews' == $action && $user->perm->checkRight($user->getUserId(), 'addnews')) {
    ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-pencil"></i> <?php echo $PMF_LANG['ad_news_add'] ?></h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal" id="faqEditor" name="faqEditor" action="?action=savenews" method="post" accept-charset="utf-8">

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="newsheader">
                            <?php echo $PMF_LANG['ad_news_header'] ?>
                        </label>
                        <div class="col-lg-4">
                            <input class="form-control" type="text" name="newsheader">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="news"><?php echo $PMF_LANG['ad_news_text'] ?>:</label>
                        <div class="col-lg-4">
                            <noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript>
                            <textarea name="news" rows="5" class="form-control"></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="authorName"><?php echo $PMF_LANG['ad_news_author_name'] ?></label>
                        <div class="col-lg-4">
                            <input class="form-control" type="text" name="authorName" id="authorName"
                                   value="<?php echo $user->getUserData('display_name') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="authorEmail"><?php echo $PMF_LANG['ad_news_author_email'] ?></label>
                        <div class="col-lg-4">
                            <input class="form-control" type="email" name="authorEmail" id="authorEmail"
                                   value="<?php echo $user->getUserData('email') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="active">
                            <?php echo $PMF_LANG['ad_news_set_active'] ?>:
                        </label>
                        <div class="col-lg-4 checkbox">
                            <label>
                                <input type="checkbox" name="active" id="active" value="y">
                                <?php echo $PMF_LANG['ad_gen_yes'] ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="comment"><?php echo $PMF_LANG['ad_news_allowComments'] ?></label>
                        <div class="col-lg-4 checkbox">
                            <label>
                                <input type="checkbox" name="comment" id="comment" value="y">
                                <?php echo $PMF_LANG['ad_gen_yes'] ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="link"><?php echo $PMF_LANG['ad_news_link_url'] ?></label>
                        <div class="col-lg-4">
                            <input class="form-control" type="url" name="link" id="link" placeholder="http://www.example.com/">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="linkTitle"><?php echo $PMF_LANG['ad_news_link_title'] ?></label>
                        <div class="col-lg-4">
                            <input type="text" name="linkTitle" id="linkTitle" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" ><?php echo $PMF_LANG['ad_news_link_target'] ?></label>
                        <div class="col-lg-4 radio">
                            <label>
                                <input type="radio" name="target" value="blank">
                                <?php echo $PMF_LANG['ad_news_link_window'] ?>
                                <br>
                                <input type="radio" name="target" value="self">
                                <?php echo $PMF_LANG['ad_news_link_faq'] ?>
                                <br>
                                <input type="radio" name="target" value="parent">
                                <?php echo $PMF_LANG['ad_news_link_parent'] ?>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="langTo"><?php echo $PMF_LANG['ad_entry_locale'] ?>:</label>
                        <div class="col-lg-4">
                            <?php echo PMF_Language::selectLanguages($LANGCODE, false, [], 'langTo') ?>
                        </div>
                    </div>

                    <legend><?php echo $PMF_LANG['ad_news_expiration_window'] ?></legend>
                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="dateStart"><?php echo $PMF_LANG['ad_news_from'];
    ?></label>
                        <div class="col-lg-2">
                            <input type="date" name="dateStart" id="dateStart" class="form-control date-pick">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="dateEnd"><?php echo $PMF_LANG['ad_news_to'];
    ?></label>
                        <div class="col-lg-2">
                            <input type="date" name="dateEnd" id="dateEnd" class="form-control date-pick">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-4">
                            <button class="btn btn-primary" type="submit">
                                <?php echo $PMF_LANG['ad_news_add'];
    ?>
                            </button>
                            <a class="btn btn-info" href="?action=news">
                                <?php echo $PMF_LANG['ad_entry_back'];
    ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
<?php

} elseif ('news' == $action && $user->perm->checkRight($user->getUserId(), 'editnews')) {
    ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-pencil"></i> <?php echo $PMF_LANG['msgNews'] ?>
                    <div class="pull-right">
                        <a class="btn btn-success" href="?action=addnews">
                            <i aria-hidden="true" class="fa fa-plus fa fa-white"></i> <?php echo $PMF_LANG['ad_menu_news_add'] ?>
                        </a>
                    </div>
                </h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
                <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?php echo $PMF_LANG['ad_news_headline'];
    ?></th>
                        <th colspan="2"><?php echo $PMF_LANG['ad_news_date'];
    ?></th>
                    </tr>
                </thead>
                <tbody>
<?php
        $newsHeader = $news->getNewsHeader();
    $date = new PMF_Date($faqConfig);
    if (count($newsHeader)) {
        foreach ($newsHeader as $newsItem) {
            ?>
                    <tr>
                        <td><?php echo $newsItem['header'];
            ?></td>
                        <td><?php echo $date->format($newsItem['date']);
            ?></td>
                        <td>
                            <a class="btn btn-primary" href="?action=editnews&amp;id=<?php echo $newsItem['id'];
            ?>">
                                <span title="<?php echo $PMF_LANG['ad_news_update'];
            ?>" class="fa fa-edit"></span>
                            </a>
                            &nbsp;&nbsp;
                            <a class="btn btn-danger" href="?action=deletenews&amp;id=<?php echo $newsItem['id'];
            ?>">
                                <span title="<?php echo $PMF_LANG['ad_news_delete'];
            ?>" class="fa fa-trash-o"></span>
                            </a>
                        </td>
                    </tr>
<?php

        }
    } else {
        printf(
                '<tr><td colspan="3">%s</td></tr>',
                $PMF_LANG['ad_news_nodata']
            );
    }
    ?>
                </tbody>
                </table>
            </div>
        </div>
<?php

} elseif ('editnews' == $action && $user->perm->checkRight($user->getUserId(), 'editnews')) {
    $id = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $newsData = $news->getNewsEntry($id, true);
    ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-pencil"></i> <?php echo $PMF_LANG['ad_news_edit'];
    ?></h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal" action="?action=updatenews" method="post" accept-charset="utf-8">
                    <input type="hidden" name="id" value="<?php echo $newsData['id'];
    ?>">

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="newsheader"><?php echo $PMF_LANG['ad_news_header'];
    ?></label>
                        <div class="col-lg-4">
                            <input type="text" name="newsheader" id="newsheader" class="form-control"
                                   value="<?php if (isset($newsData['header'])) {
    echo $newsData['header'];
}
    ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="news"><?php echo $PMF_LANG['ad_news_text'];
    ?>:</label>
                        <div class="col-lg-4">
                            <noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript>
                            <textarea id="news" name="news" class="form-control" rows="5"><?php
                                if (isset($newsData['content'])) {
                                    echo htmlspecialchars($newsData['content'], ENT_QUOTES);
                                }
    ?></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="authorName"><?php echo $PMF_LANG['ad_news_author_name'];
    ?></label>
                        <div class="col-lg-4">
                            <input type="text" name="authorName" value="<?php echo $newsData['authorName'];
    ?>" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="authorEmail"><?php echo $PMF_LANG['ad_news_author_email'];
    ?></label>
                        <div class="col-lg-4">
                            <input type="email" name="authorEmail" value="<?php echo $newsData['authorEmail'];
    ?>" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="active">
                            <?php echo $PMF_LANG['ad_news_set_active'];
    ?>:
                        </label>
                        <div class="col-lg-4">
                            <label>
                                <input type="checkbox" name="active" id="active" value="y"
                                    <?php if (isset($newsData['active']) && $newsData['active']) {
    echo ' checked';
}
    ?>
                                <?php echo $PMF_LANG['ad_gen_yes'];
    ?>
                            </label>
                        </div>

                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="comment"><?php echo $PMF_LANG['ad_news_allowComments'];
    ?></label>
                        <div class="col-lg-4">
                            <label>
                                <input type="checkbox" name="comment" id="comment" value="y"<?php if (isset($newsData['allowComments']) && $newsData['allowComments']) {
    echo ' checked';
}
    ?>>
                                <?php echo $PMF_LANG['ad_gen_yes'];
    ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="link"><?php echo $PMF_LANG['ad_news_link_url'];
    ?></label>
                        <div class="col-lg-4">
                            <input type="url" name="link" value="<?php echo $newsData['link'];
    ?>" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="linkTitle"><?php echo $PMF_LANG['ad_news_link_title'];
    ?></label>
                        <div class="col-lg-4">
                            <input type="text" name="linkTitle" value="<?php echo $newsData['linkTitle'];
    ?>" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="target"><?php echo $PMF_LANG['ad_news_link_target'];
    ?></label>
                        <div class="col-lg-4">
                            <label>
                                <input type="radio" name="target" value="blank" <?php if ('blank' == $newsData['target']) {
    ?>
                                   checked="checked"<?php 
}
    ?>>
                                <?php echo $PMF_LANG['ad_news_link_window'] ?>
                            </label>
                            <label>
                                <input type="radio" name="target" value="self" <?php if ('self' == $newsData['target']) {
    ?>
                                   checked="checked"<?php 
}
    ?>>
                                <?php echo $PMF_LANG['ad_news_link_faq'] ?>
                            </label>
                            <label>
                                <input type="radio" name="target" value="parent" <?php if ('parent' == $newsData['target']) {
    ?>
                                   checked="checked"<?php 
}
    ?>>
                                <?php echo $PMF_LANG['ad_news_link_parent'] ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="langTo"><?php echo $PMF_LANG['ad_entry_locale'];
    ?>:</label>
                        <div class="col-lg-4">
                        <?php echo PMF_Language::selectLanguages($newsData['lang'], false, [], 'langTo');
    ?>
                    </div>
<?php
    $dateStart = ($newsData['dateStart'] != '00000000000000' ? PMF_Date::createIsoDate($newsData['dateStart'], 'Y-m-d') : '');
    $dateEnd = ($newsData['dateEnd'] != '99991231235959' ? PMF_Date::createIsoDate($newsData['dateEnd'], 'Y-m-d') : '');
    ?>

                    <legend><?php echo $PMF_LANG['ad_news_expiration_window'];
    ?></legend>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="dateStart"><?php echo $PMF_LANG['ad_news_from'];
    ?></label>
                        <div class="col-lg-2">
                            <input name="dateStart" id="dateStart" class="form-control date-pick" value="<?php echo $dateStart;
    ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="dateEnd"><?php echo $PMF_LANG['ad_news_to'];
    ?></label>
                        <div class="col-lg-2">
                            <input name="dateEnd" id="dateEnd" class="form-control date-pick" value="<?php echo $dateEnd;
    ?>">
                        </div>
                    </div>

                <div class="form-group">
                    <div class="col-lg-offset-2 col-lg-4">
                        <button class="btn btn-primary" type="submit">
                            <?php echo $PMF_LANG['ad_news_edit'];
    ?>
                        </button>
                        <a class="btn btn-info" href="?action=news">
                            <?php echo $PMF_LANG['ad_entry_back'];
    ?>
                        </a>
                    </div>
                </div>
                </form>
<?php
    $newsId = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $oComment = new PMF_Comment($faqConfig);
    $comments = $oComment->getCommentsData($newsId, PMF_Comment::COMMENT_TYPE_NEWS);
    if (count($comments) > 0) {
        ?>
                <div class="form-group"><strong><?php echo $PMF_LANG['ad_entry_comment'] ?></strong></div>
<?php

    }
    foreach ($comments as $item) {
        ?>
                <div class="form-group">
                    <?php echo $PMF_LANG['ad_entry_commentby'] ?>
                    <a href="mailto:<?php print($item['email']);
        ?>">
                        <?php print($item['user']);
        ?>
                    </a>:<br>
                    <?php print($item['content']);
        ?><br>
                    <?php print($PMF_LANG['newsCommentDate'].PMF_Date::createIsoDate($item['date'], 'Y-m-d H:i', false));
        ?>
                    <a href="?action=delcomment&amp;artid=<?php print($newsId);
        ?>&amp;cmtid=<?php print($item['id']);
        ?>&amp;type=<?php print(PMF_Comment::COMMENT_TYPE_NEWS);
        ?>">
                        <i aria-hidden="true" class="fa fa-trash-o"></i>
                    </a>
                </div>
            </div>
        </div>
<?php

    }
} elseif ('savenews' == $action && $user->perm->checkRight($user->getUserId(), 'addnews')) {
    ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-pencil"></i> <?php echo $PMF_LANG['ad_news_data'];
    ?></h2>
            </div>
        </header>


        <div class="row">
            <div class="col-lg-12">
<?php
    $dateStart = PMF_Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd = PMF_Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $header = PMF_Filter::filterInput(INPUT_POST, 'newsheader', FILTER_SANITIZE_STRIPPED);
    $content = PMF_Filter::filterInput(INPUT_POST, 'news', FILTER_SANITIZE_SPECIAL_CHARS);
    $author = PMF_Filter::filterInput(INPUT_POST, 'authorName', FILTER_SANITIZE_STRIPPED);
    $email = PMF_Filter::filterInput(INPUT_POST, 'authorEmail', FILTER_VALIDATE_EMAIL);
    $active = PMF_Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING);
    $comment = PMF_Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $link = PMF_Filter::filterInput(INPUT_POST, 'link', FILTER_VALIDATE_URL);
    $linktitle = PMF_Filter::filterInput(INPUT_POST, 'linkTitle', FILTER_SANITIZE_STRIPPED);
    $newslang = PMF_Filter::filterInput(INPUT_POST, 'langTo', FILTER_SANITIZE_STRING);
    $target = PMF_Filter::filterInput(INPUT_POST, 'target', FILTER_SANITIZE_STRIPPED);

    $newsData = array(
        'lang' => $newslang,
        'header' => $header,
        'content' => html_entity_decode($content),
        'authorName' => $author,
        'authorEmail' => $email,
        'active' => (is_null($active)) ? 'n' : 'y',
        'comment' => (is_null($comment)) ? 'n' : 'y',
        'dateStart' => (empty($dateStart)) ? '00000000000000' : str_replace('-', '', $dateStart).'000000',
        'dateEnd' => (empty($dateEnd))  ? '99991231235959' : str_replace('-', '', $dateEnd).'235959',
        'link' => $link,
        'linkTitle' => $linktitle,
        'date' => date('YmdHis'),
        'target' => (is_null($target)) ? '' : $target,
    );

    if ($news->addNewsEntry($newsData)) {
        printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_news_updatesuc']);
    } else {
        printf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_news_insertfail']);
    }
    printf('<div class="form-group">&rarr; <a href="?action=news">%s</a></p>', $PMF_LANG['msgNews']);
    ?>
            </div>
        </div>
<?php

} elseif ('updatenews' == $action && $user->perm->checkRight($user->getUserId(), 'editnews')) {
    ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-pencil"></i> <?php echo $PMF_LANG['ad_news_data'];
    ?></h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
<?php
    $dateStart = PMF_Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd = PMF_Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $header = PMF_Filter::filterInput(INPUT_POST, 'newsheader', FILTER_SANITIZE_STRIPPED);
    $content = PMF_Filter::filterInput(INPUT_POST, 'news', FILTER_SANITIZE_SPECIAL_CHARS);
    $author = PMF_Filter::filterInput(INPUT_POST, 'authorName', FILTER_SANITIZE_STRIPPED);
    $email = PMF_Filter::filterInput(INPUT_POST, 'authorEmail', FILTER_VALIDATE_EMAIL);
    $active = PMF_Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING);
    $comment = PMF_Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $link = PMF_Filter::filterInput(INPUT_POST, 'link', FILTER_VALIDATE_URL);
    $linktitle = PMF_Filter::filterInput(INPUT_POST, 'linkTitle', FILTER_SANITIZE_STRIPPED);
    $newslang = PMF_Filter::filterInput(INPUT_POST, 'langTo', FILTER_SANITIZE_STRING);
    $target = PMF_Filter::filterInput(INPUT_POST, 'target', FILTER_SANITIZE_STRIPPED);

    $newsData = array(
        'lang' => $newslang,
        'header' => $header,
        'content' => html_entity_decode($content),
        'authorName' => $author,
        'authorEmail' => $email,
        'active' => (is_null($active)) ? 'n' : 'y',
        'comment' => (is_null($comment)) ? 'n' : 'y',
        'dateStart' => (empty($dateStart)) ? '00000000000000' : str_replace('-', '', $dateStart).'000000',
        'dateEnd' => (empty($dateEnd))   ? '99991231235959' : str_replace('-', '', $dateEnd).'235959',
        'link' => $link,
        'linkTitle' => $linktitle,
        'date' => date('YmdHis'),
        'target' => (is_null($target)) ? '' : $target,
    );

    $newsId = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($news->updateNewsEntry($newsId, $newsData)) {
        printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_news_updatesuc']);
    } else {
        printf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_news_updatefail']);
    }
    printf('<div class="form-group">&rarr; <a href="?action=news">%s</a></p>', $PMF_LANG['msgNews']);
    ?>
            </div>
        </div>
<?php

} elseif ('deletenews' == $action && $user->perm->checkRight($user->getUserId(), 'delnews')) {
    ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-pencil"></i> <?php echo $PMF_LANG['ad_news_data'];
    ?></h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
<?php
    $precheck = PMF_Filter::filterInput(INPUT_POST, 'really', FILTER_SANITIZE_STRING, 'no');
    $deleteId = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ('no' == $precheck) {
        ?>
    <div class="form-group"><?php echo $PMF_LANG['ad_news_del'];
        ?></div>
    <div class="text-center">
    <form action="?action=deletenews" method="post" accept-charset="utf-8">
    <input type="hidden" name="id" value="<?php echo $deleteId ?>">
    <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession() ?>">
    <input type="hidden" name="really" value="yes">
        <button class="btn btn-warning" type="submit" name="submit">
            <?php echo $PMF_LANG['ad_news_yesdelete'];
        ?>
        </button>
        <a class="btn btn-inverse" onclick="javascript:history.back();">
            <?php echo $PMF_LANG['ad_news_nodelete'];
        ?>
        </a>
    </form>
    </div>
    
    <script type="text/javascript">
    if (!Modernizr.inputtypes.date) {
        $(function() {
            $('.date-pick').datePicker();
        });
    }
    </script>
<?php

    } else {
        if ($csrfCheck) {
            $deleteId = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $news->deleteNews($deleteId);
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_news_delsuc']);
            printf('<div class="form-group">&rarr; <a href="?action=news">%s</a></p>', $PMF_LANG['msgNews']);
        }
    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
