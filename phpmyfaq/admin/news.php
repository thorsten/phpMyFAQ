<?php
/**
 * The main administration file for the news.
 *
 * 
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-23
 */

use phpMyFAQ\Comment;
use phpMyFAQ\Date;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\News;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$news = new News($faqConfig);

$csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
    $csrfCheck = false;
} else {
    $csrfCheck = true;
}

if ('add-news' == $action && $user->perm->checkRight($user->getUserId(), 'addnews')) { ?>

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fas fa-pencil"></i>
            <?= $PMF_LANG['ad_news_add'] ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <form  id="faqEditor" name="faqEditor" action="?action=save-news" method="post" accept-charset="utf-8">

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="newsheader">
                            <?= $PMF_LANG['ad_news_header'] ?>
                        </label>
                        <div class="col-lg-4">
                            <input class="form-control" type="text" name="newsheader" id="newsheader">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="news"><?= $PMF_LANG['ad_news_text'] ?>:</label>
                        <div class="col-lg-4">
                            <textarea name="news" rows="5" class="form-control" id="news"></textarea>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="authorName"><?= $PMF_LANG['ad_news_author_name'] ?></label>
                        <div class="col-lg-4">
                            <input class="form-control" type="text" name="authorName" id="authorName"
                                   value="<?= $user->getUserData('display_name') ?>">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="authorEmail"><?= $PMF_LANG['ad_news_author_email'] ?></label>
                        <div class="col-lg-4">
                            <input class="form-control" type="email" name="authorEmail" id="authorEmail"
                                   value="<?= $user->getUserData('email') ?>">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="active">
                            <?= $PMF_LANG['ad_news_set_active'] ?>:
                        </label>
                        <div class="col-lg-4 checkbox">
                            <label>
                                <input type="checkbox" name="active" id="active" value="y">
                                <?= $PMF_LANG['ad_gen_yes'] ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="comment"><?= $PMF_LANG['ad_news_allowComments'] ?></label>
                        <div class="col-lg-4 checkbox">
                            <label>
                                <input type="checkbox" name="comment" id="comment" value="y">
                                <?= $PMF_LANG['ad_gen_yes'] ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="link"><?= $PMF_LANG['ad_news_link_url'] ?></label>
                        <div class="col-lg-4">
                            <input class="form-control" type="url" name="link" id="link" placeholder="http://www.example.com/">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="linkTitle"><?= $PMF_LANG['ad_news_link_title'] ?></label>
                        <div class="col-lg-4">
                            <input type="text" name="linkTitle" id="linkTitle" class="form-control">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" ><?= $PMF_LANG['ad_news_link_target'] ?></label>
                        <div class="col-lg-4 radio">
                            <label>
                                <input type="radio" name="target" value="blank">
                                <?= $PMF_LANG['ad_news_link_window'] ?>
                                <br>
                                <input type="radio" name="target" value="self">
                                <?= $PMF_LANG['ad_news_link_faq'] ?>
                                <br>
                                <input type="radio" name="target" value="parent">
                                <?= $PMF_LANG['ad_news_link_parent'] ?>
                            </label>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="langTo"><?= $PMF_LANG['ad_entry_locale'] ?>:</label>
                        <div class="col-lg-4">
                            <?= Language::selectLanguages($LANGCODE, false, [], 'langTo') ?>
                        </div>
                    </div>

                    <legend><?= $PMF_LANG['ad_news_expiration_window'] ?></legend>
                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="dateStart"><?= $PMF_LANG['ad_news_from'] ?></label>
                        <div class="col-lg-2">
                            <input type="date" name="dateStart" id="dateStart" class="form-control date-pick">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="dateEnd"><?= $PMF_LANG['ad_news_to'] ?></label>
                        <div class="col-lg-2">
                            <input type="date" name="dateEnd" id="dateEnd" class="form-control date-pick">
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="offset-lg-2 col-lg-4">
                            <button class="btn btn-primary" type="submit">
                                <?= $PMF_LANG['ad_news_add'] ?>
                            </button>
                            <a class="btn btn-info" href="?action=news">
                                <?= $PMF_LANG['ad_entry_back'] ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
<?php

} elseif ('news' == $action && $user->perm->checkRight($user->getUserId(), 'editnews')) {
    ?>
         <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fas fa-pencil"></i>
              <?= $PMF_LANG['msgNews'] ?>
          </h1>
          <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group mr-2">
              <a href="?action=add-news">
                  <button class="btn btn-sm btn-outline-success">
                    <i aria-hidden="true" class="fas fa-plus"></i> <?= $PMF_LANG['ad_menu_news_add'] ?>
                  </button>
              </a>
            </div>
          </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <table class="table table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th><?= $PMF_LANG['ad_news_headline'] ?></th>
                        <th><?= $PMF_LANG['ad_news_date'] ?></th>
                        <th colspan="2">&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
<?php
    $newsHeader = $news->getNewsHeader();
    $date = new Date($faqConfig);
    if (count($newsHeader)) {
        foreach ($newsHeader as $newsItem) {
            ?>
                    <tr>
                        <td><?= $newsItem['header'] ?></td>
                        <td><?= $date->format($newsItem['date']) ?></td>
                        <td>
                            <a class="btn btn-primary" href="?action=edit-news&amp;id=<?= $newsItem['id'] ?>">
                                <span title="<?= $PMF_LANG['ad_news_update'] ?>" class="fas fa-edit"></span>
                            </a>
                        </td>
                        <td>
                            <a class="btn btn-danger" href="?action=delete-news&amp;id=<?= $newsItem['id'] ?>">
                                <span title="<?= $PMF_LANG['ad_news_delete'] ?>" class="fas fa-trash"></span>
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
    $id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $newsData = $news->getNewsEntry($id, true);
    ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fas fa-pencil"></i>
            <?= $PMF_LANG['ad_news_edit'] ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <form  action="?action=update-news" method="post" accept-charset="utf-8">
                    <input type="hidden" name="id" value="<?= $newsData['id'] ?>">

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="newsheader"><?= $PMF_LANG['ad_news_header'] ?></label>
                        <div class="col-lg-4">
                            <input type="text" name="newsheader" id="newsheader" class="form-control"
                                   value="<?= $newsData['header'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="news"><?= $PMF_LANG['ad_news_text'] ?>:</label>
                        <div class="col-lg-4">
                            <noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript>
                            <textarea id="news" name="news" class="form-control" rows="5"><?php
                                if (isset($newsData['content'])) {
                                    echo htmlspecialchars($newsData['content'], ENT_QUOTES);
                                } ?></textarea>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="authorName"><?= $PMF_LANG['ad_news_author_name'] ?></label>
                        <div class="col-lg-4">
                            <input type="text" name="authorName" value="<?= $newsData['authorName'] ?>" class="form-control">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="authorEmail"><?= $PMF_LANG['ad_news_author_email'] ?></label>
                        <div class="col-lg-4">
                            <input type="email" name="authorEmail" value="<?= $newsData['authorEmail'] ?>" class="form-control">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="active">
                            <?= $PMF_LANG['ad_news_set_active'] ?>:
                        </label>
                        <div class="col-lg-4">
                            <label>
                                <input type="checkbox" name="active" id="active" value="y"
                                    <?php if (isset($newsData['active']) && $newsData['active']) {
    echo ' checked';
}
    ?>
                                <?= $PMF_LANG['ad_gen_yes'] ?>
                            </label>
                        </div>

                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="comment"><?= $PMF_LANG['ad_news_allowComments'] ?></label>
                        <div class="col-lg-4">
                            <label>
                                <input type="checkbox" name="comment" id="comment" value="y"<?php if (isset($newsData['allowComments']) && $newsData['allowComments']) {
    echo ' checked';
}
    ?>>
                                <?= $PMF_LANG['ad_gen_yes'] ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="link"><?= $PMF_LANG['ad_news_link_url'];
    ?></label>
                        <div class="col-lg-4">
                            <input type="url" name="link" value="<?= $newsData['link'];
    ?>" class="form-control">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="linkTitle"><?= $PMF_LANG['ad_news_link_title'];
    ?></label>
                        <div class="col-lg-4">
                            <input type="text" name="linkTitle" value="<?= $newsData['linkTitle'];
    ?>" class="form-control">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="target"><?= $PMF_LANG['ad_news_link_target'];
    ?></label>
                        <div class="col-lg-4">
                            <label>
                                <input type="radio" name="target" value="blank" <?php if ('blank' == $newsData['target']) {
    ?>
                                   checked<?php
}
    ?>>
                                <?= $PMF_LANG['ad_news_link_window'] ?>
                            </label>
                            <label>
                                <input type="radio" name="target" value="self" <?php if ('self' == $newsData['target']) {
    ?>
                                   checked<?php
}
    ?>>
                                <?= $PMF_LANG['ad_news_link_faq'] ?>
                            </label>
                            <label>
                                <input type="radio" name="target" value="parent" <?php if ('parent' == $newsData['target']) {
    ?>
                                   checked<?php
}
    ?>>
                                <?= $PMF_LANG['ad_news_link_parent'] ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="langTo"><?= $PMF_LANG['ad_entry_locale'] ?>:</label>
                        <div class="col-lg-4">
                        <?= Language::selectLanguages($newsData['lang'], false, [], 'langTo') ?>
                    </div>
<?php
    $dateStart = ($newsData['dateStart'] != '00000000000000' ? Date::createIsoDate($newsData['dateStart'], 'Y-m-d') : '');
    $dateEnd = ($newsData['dateEnd'] != '99991231235959' ? Date::createIsoDate($newsData['dateEnd'], 'Y-m-d') : '');
    ?>

                    <legend><?= $PMF_LANG['ad_news_expiration_window'] ?></legend>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="dateStart"><?= $PMF_LANG['ad_news_from'] ?></label>
                        <div class="col-lg-2">
                            <input name="dateStart" id="dateStart" class="form-control date-pick" value="<?= $dateStart ?>">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="dateEnd"><?= $PMF_LANG['ad_news_to'] ?></label>
                        <div class="col-lg-2">
                            <input name="dateEnd" id="dateEnd" class="form-control date-pick" value="<?= $dateEnd ?>">
                        </div>
                    </div>

                <div class="form-group row">
                    <div class="offset-lg-2 col-lg-4">
                        <button class="btn btn-primary" type="submit">
                            <?= $PMF_LANG['ad_news_edit'] ?>
                        </button>
                        <a class="btn btn-info" href="?action=news">
                            <?= $PMF_LANG['ad_entry_back'] ?>
                        </a>
                    </div>
                </div>
                </form>
<?php
    $newsId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $oComment = new Comment($faqConfig);
    $comments = $oComment->getCommentsData($newsId, Comment::COMMENT_TYPE_NEWS);
    if (count($comments) > 0) {
        ?>
                <div class="form-group row"><strong><?= $PMF_LANG['ad_entry_comment'] ?></strong></div>
<?php

    }
    foreach ($comments as $item) {
        ?>
                <div class="form-group row">
                    <?= $PMF_LANG['ad_entry_commentby'] ?>
                    <a href="mailto:<?= $item['email'] ?>">
                        <?= $item['user'] ?>
                    </a>:<br>
                    <?= $item['content'] ?><br>
                    <?= $PMF_LANG['newsCommentDate'].Date::createIsoDate($item['date'], 'Y-m-d H:i', false) ?>
                    <a href="?action=delcomment&artid=<?= $newsId ?>&cmtid=<?= $item['id'] ?>&type=<?= Comment::COMMENT_TYPE_NEWS ?>">
                        <i aria-hidden="true" class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
        </div>
<?php

    }
} elseif ('save-news' == $action && $user->perm->checkRight($user->getUserId(), 'addnews')) {
    ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fas fa-pencil"></i>
            <?= $PMF_LANG['ad_news_data'] ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-lg-12">
<?php
    $dateStart = Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd = Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $header = Filter::filterInput(INPUT_POST, 'newsheader', FILTER_SANITIZE_STRIPPED);
    $content = Filter::filterInput(INPUT_POST, 'news', FILTER_SANITIZE_SPECIAL_CHARS);
    $author = Filter::filterInput(INPUT_POST, 'authorName', FILTER_SANITIZE_STRIPPED);
    $email = Filter::filterInput(INPUT_POST, 'authorEmail', FILTER_VALIDATE_EMAIL);
    $active = Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING);
    $comment = Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $link = Filter::filterInput(INPUT_POST, 'link', FILTER_VALIDATE_URL);
    $linktitle = Filter::filterInput(INPUT_POST, 'linkTitle', FILTER_SANITIZE_STRIPPED);
    $newslang = Filter::filterInput(INPUT_POST, 'langTo', FILTER_SANITIZE_STRING);
    $target = Filter::filterInput(INPUT_POST, 'target', FILTER_SANITIZE_STRIPPED);

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
    printf('<div class="form-group row">&rarr; <a href="?action=news">%s</a></p>', $PMF_LANG['msgNews']);
    ?>
            </div>
        </div>
<?php

} elseif ('update-news' == $action && $user->perm->checkRight($user->getUserId(), 'editnews')) {
    ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fas fa-pencil"></i>
            <?= $PMF_LANG['ad_news_data'] ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-lg-12">
<?php
    $dateStart = Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd = Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $header = Filter::filterInput(INPUT_POST, 'newsheader', FILTER_SANITIZE_STRIPPED);
    $content = Filter::filterInput(INPUT_POST, 'news', FILTER_SANITIZE_SPECIAL_CHARS);
    $author = Filter::filterInput(INPUT_POST, 'authorName', FILTER_SANITIZE_STRIPPED);
    $email = Filter::filterInput(INPUT_POST, 'authorEmail', FILTER_VALIDATE_EMAIL);
    $active = Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING);
    $comment = Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $link = Filter::filterInput(INPUT_POST, 'link', FILTER_VALIDATE_URL);
    $linktitle = Filter::filterInput(INPUT_POST, 'linkTitle', FILTER_SANITIZE_STRIPPED);
    $newslang = Filter::filterInput(INPUT_POST, 'langTo', FILTER_SANITIZE_STRING);
    $target = Filter::filterInput(INPUT_POST, 'target', FILTER_SANITIZE_STRIPPED);

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

    $newsId = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($news->updateNewsEntry($newsId, $newsData)) {
        printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_news_updatesuc']);
    } else {
        printf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_news_updatefail']);
    }
    printf('<div class="form-group row">&rarr; <a href="?action=news">%s</a></p>', $PMF_LANG['msgNews']);
    ?>
            </div>
        </div>
<?php

} elseif ('delete-news' == $action && $user->perm->checkRight($user->getUserId(), 'delnews')) {
    ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fas fa-pencil"></i>
            <?= $PMF_LANG['ad_news_data'] ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-lg-12">
<?php
    $precheck = Filter::filterInput(INPUT_POST, 'really', FILTER_SANITIZE_STRING, 'no');
    $deleteId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ('no' == $precheck) {
        ?>
    <div class="form-group row"><?= $PMF_LANG['ad_news_del'];
        ?></div>
    <div class="text-center">
    <form action="?action=deletenews" method="post" accept-charset="utf-8">
    <input type="hidden" name="id" value="<?= $deleteId ?>">
    <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession() ?>">
    <input type="hidden" name="really" value="yes">
        <button class="btn btn-warning" type="submit" name="submit">
            <?= $PMF_LANG['ad_news_yesdelete'];
        ?>
        </button>
        <a class="btn btn-inverse" onclick="history.back();">
            <?= $PMF_LANG['ad_news_nodelete'];
        ?>
        </a>
    </form>
    </div>
    
    <script>
    $(function() {
      $('.date-pick').datePicker();
    });
    </script>
<?php

    } else {
        if ($csrfCheck) {
            $deleteId = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $news->deleteNews($deleteId);
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_news_delsuc']);
            printf('<div class="form-group row">&rarr; <a href="?action=news">%s</a></p>', $PMF_LANG['msgNews']);
        }
    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
