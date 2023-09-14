<?php

/**
 * The main administration file for the news.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-23
 */

use phpMyFAQ\Comments;
use phpMyFAQ\Component\Alert;
use phpMyFAQ\Date;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Language;
use phpMyFAQ\News;
use phpMyFAQ\Session\Token;use phpMyFAQ\Strings;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$news = new News($faqConfig);

$csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

if ('add-news' == $action && $user->perm->hasPermission($user->getUserId(), 'addnews')) { ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">
    <i aria-hidden="true" class="fa fa-pencil"></i>
    <?= Translation::get('ad_news_add') ?>
  </h1>
</div>

<div class="row">
    <div class="col-12">
        <form id="faqEditor" name="faqEditor" action="?action=save-news" method="post" novalidate
              data-pmf-enable-editor="<?= $faqConfig->get('main.enableWysiwygEditor') ?>"
              data-pmf-editor-language="en"
              data-pmf-default-url="<?= $faqConfig->getDefaultUrl() ?>">
            <?= Token::getInstance()->getTokenInput('save-news') ?>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="newsheader">
                    <?= Translation::get('ad_news_header') ?>
                </label>
                <div class="col-9">
                    <input class="form-control" type="text" name="newsheader" id="newsheader">
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="news"><?= Translation::get('ad_news_text') ?>:</label>
                <div class="col-9">
                    <textarea name="news" rows="5" class="form-control" id="editor"></textarea>
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="authorName">
                <?= Translation::get('ad_news_author_name') ?>
                </label>
                <div class="col-9">
                    <input class="form-control" type="text" name="authorName" id="authorName"
                           value="<?= $user->getUserData('display_name') ?>">
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="authorEmail">
                <?= Translation::get('ad_news_author_email') ?>
                </label>
                <div class="col-9">
                    <input class="form-control" type="email" name="authorEmail" id="authorEmail"
                           value="<?= $user->getUserData('email') ?>">
                </div>
            </div>

            <div class="row mb-2">
                <div class="offset-3 col-9">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="y" id="active" name="active">
                      <label class="form-check-label" for="active">
                        <?= Translation::get('ad_news_set_active') ?>
                      </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="y" id="comment" name="comment">
                        <label class="form-check-label" for="comment">
                            <?= Translation::get('ad_news_allowComments') ?>
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="link">
                <?= Translation::get('ad_news_link_url') ?>
                </label>
                <div class="col-9">
                    <input class="form-control" type="text" name="link" id="link"
                    placeholder="https://www.example.com/">
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="linkTitle">
                <?= Translation::get('ad_news_link_title') ?>
                </label>
                <div class="col-9">
                    <input type="text" name="linkTitle" id="linkTitle" class="form-control">
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" ><?= Translation::get('ad_news_link_target') ?></label>
                <div class="col-9 radio">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="target" id="blank" value="blank">
                        <label class="form-check-label" for="blank">
                            <?= Translation::get('ad_news_link_window') ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="target" id="self" value="self">
                        <label class="form-check-label" for="self">
                            <?= Translation::get('ad_news_link_faq') ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="target" id="parent" value="parent">
                        <label class="form-check-label" for="parent">
                            <?= Translation::get('ad_news_link_parent') ?>
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="langTo"><?= Translation::get('ad_entry_locale') ?>:</label>
                <div class="col-9">
                    <?= LanguageHelper::renderSelectLanguage($faqLangCode, false, [], 'langTo') ?>
                </div>
            </div>

            <h6><?= Translation::get('ad_news_expiration_window') ?></h6>
            <div class="row mb-2">
                <label class="col-3 col-form-label" for="dateStart"><?= Translation::get('ad_news_from') ?></label>
                <div class="col-3">
                    <input type="date" name="dateStart" id="dateStart" class="form-control">
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="dateEnd"><?= Translation::get('ad_news_to') ?></label>
                <div class="col-3">
                    <input type="date" name="dateEnd" id="dateEnd" class="form-control">
                </div>
            </div>

            <div class="row mb-2">
              <div class="col-12 text-end">
                  <a class="btn btn-secondary" href="?action=news">
                    <?= Translation::get('ad_entry_back') ?>
                  </a>
                  <button class="btn btn-primary" type="submit">
                    <?= Translation::get('ad_news_add') ?>
                  </button>
              </div>
            </div>

        </form>
    </div>
</div>


    <?php
} elseif ('news' == $action && $user->perm->hasPermission($user->getUserId(), 'editnews')) {
    ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i aria-hidden="true" class="fa fa-pencil"></i> <?= Translation::get('msgNews') ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group mr-2">
            <a href="?action=add-news">
                <button class="btn btn-sm btn-success">
                    <i aria-hidden="true" class="fa fa-plus"></i> <?= Translation::get('ad_menu_news_add') ?>
                </button>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <table class="table table-hover align-middle">
        <thead class="thead-dark">
            <tr>
                <th><?= Translation::get('ad_news_headline') ?></th>
                <th><?= Translation::get('ad_news_date') ?></th>
                <th colspan="2">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
    <?php
    $newsHeader = $news->getNewsHeader();
    $date = new Date($faqConfig);
    if (is_countable($newsHeader) ? count($newsHeader) : 0) {
        foreach ($newsHeader as $newsItem) {
            ?>
            <tr>
                <td><?= Strings::htmlentities($newsItem['header']) ?></td>
                <td><?= $date->format($newsItem['date']) ?></td>
                <td>
                    <a class="btn btn-primary" href="?action=edit-news&amp;id=<?= $newsItem['id'] ?>">
                        <span title="<?= Translation::get('ad_news_update') ?>" class="fa fa-edit"></span>
                    </a>
                </td>
                <td>
                    <a class="btn btn-danger" href="?action=delete-news&amp;id=<?= $newsItem['id'] ?>">
                        <span title="<?= Translation::get('ad_news_delete') ?>" class="fa fa-trash"></span>
                    </a>
                </td>
            </tr>
            <?php
        }
    } else {
        printf(
            '<tr><td colspan="4">%s</td></tr>',
            Translation::get('ad_news_nodata')
        );
    }
    ?>
        </tbody>
        </table>
    </div>
</div>
    <?php
} elseif ('edit-news' == $action && $user->perm->hasPermission($user->getUserId(), 'editnews')) {
    $id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $newsData = $news->getNewsEntry($id, true);
    ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">
    <i aria-hidden="true" class="fa fa-pencil"></i>
    <?= Translation::get('ad_news_edit') ?>
  </h1>
</div>

<div class="row">
    <div class="col-12">
        <form id="faqEditor" action="?action=update-news" method="post" accept-charset="utf-8" class="needs-validation"
              data-pmf-enable-editor="<?= $faqConfig->get('main.enableWysiwygEditor') ?>"
              data-pmf-editor-language="en"
              data-pmf-default-url="<?= $faqConfig->getDefaultUrl() ?>">
            <input type="hidden" name="id" value="<?= $newsData['id'] ?>">
            <?= Token::getInstance()->getTokenInput('update-news') ?>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="newsheader">
                <?= Translation::get('ad_news_header') ?>
                </label>
                <div class="col-9">
                    <input type="text" name="newsheader" id="newsheader" class="form-control"
                           value="<?= Strings::htmlentities($newsData['header']) ?? '' ?>">
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="news">
                <?= Translation::get('ad_news_text') ?>:
                </label>
                <div class="col-9">
                    <noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript>
                    <textarea id="editor" name="news" class="form-control" rows="5"><?php
                    if (isset($newsData['content'])) {
                        echo htmlspecialchars((string) $newsData['content'], ENT_QUOTES);
                    } ?></textarea>
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="authorName">
                <?= Translation::get('ad_news_author_name') ?>
                </label>
                <div class="col-9">
                    <input type="text" name="authorName"
                           value="<?= Strings::htmlentities($newsData['authorName']) ?>" class="form-control">
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="authorEmail">
                <?= Translation::get('ad_news_author_email') ?>
                </label>
                <div class="col-9">
                    <input type="email" name="authorEmail"
                           value="<?= Strings::htmlentities($newsData['authorEmail']) ?>" class="form-control">
                </div>
            </div>

            <div class="row mb-2">
                <div class="offset-3 col-9">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="y" id="active" name="active"
                         <?php if (isset($newsData['active']) && $newsData['active']) {
                                echo ' checked';
                         } ?>>
                      <label class="form-check-label" for="active">
                        <?= Translation::get('ad_news_set_active') ?>
                      </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="y" id="comment" name="comment"
                        <?php if (isset($newsData['allowComments']) && $newsData['allowComments']) {
                            echo ' checked';
                        } ?>>
                        <label class="form-check-label" for="comment">
                            <?= Translation::get('ad_news_allowComments') ?>
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="link">
                <?= Translation::get('ad_news_link_url') ?>
                </label>
                <div class="col-9">
                    <input type="text" id="link" name="link" value="<?= Strings::htmlentities($newsData['link']) ?>" class="form-control">
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="linkTitle">
                <?= Translation::get('ad_news_link_title') ?>
                </label>
                <div class="col-9">
                    <input type="text" id="linkTitle" name="linkTitle" value="<?= Strings::htmlentities($newsData['linkTitle']) ?>"
                    class="form-control">
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" ><?= Translation::get('ad_news_link_target') ?></label>
                <div class="col-9 radio">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="target" id="blank" value="blank"
                        <?php if ('blank' == $newsData['target']) {
                            echo ' checked';
                        } ?>>
                        <label class="form-check-label" for="blank">
                            <?= Translation::get('ad_news_link_window') ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="target" id="self" value="self"
                        <?php if ('self' == $newsData['target']) {
                            echo ' checked';
                        } ?>>
                        <label class="form-check-label" for="self">
                            <?= Translation::get('ad_news_link_faq') ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="target" id="parent" value="parent"
                        <?php if ('parent' == $newsData['target']) {
                            echo ' checked';
                        } ?>>
                        <label class="form-check-label" for="parent">
                            <?= Translation::get('ad_news_link_parent') ?>
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="langTo"><?= Translation::get('ad_entry_locale') ?>:</label>
                <div class="col-9">
                <?= LanguageHelper::renderSelectLanguage($newsData['lang'], false, [], 'langTo') ?>
                </div>
            </div>
    <?php
    $dateStart = ($newsData['dateStart'] != '00000000000000' ? Date::createIsoDate($newsData['dateStart'], 'Y-m-d') : '');
    $dateEnd = ($newsData['dateEnd'] != '99991231235959' ? Date::createIsoDate($newsData['dateEnd'], 'Y-m-d') : '');
    ?>
            <div class="row mb-2">
                <label class="col-3 col-form-label" for="dateStart"><?= Translation::get('ad_news_from') ?></label>
                <div class="col-9">
                    <input type="date" name="dateStart" id="dateStart" class="form-control" value="<?= $dateStart ?>">
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-3 col-form-label" for="dateEnd"><?= Translation::get('ad_news_to') ?></label>
                <div class="col-9">
                    <input type="date"  name="dateEnd" id="dateEnd" class="form-control" value="<?= $dateEnd ?>">
                </div>
            </div>

            <div class="row">
              <div class="col-12 text-end">
                  <a class="btn btn-secondary" href="?action=news">
                    <?= Translation::get('ad_entry_back') ?>
                  </a>
                  <button class="btn btn-primary" type="submit">
                    <?= Translation::get('ad_news_edit') ?>
                  </button>
              </div>
            </div>
        </form>
    <?php
    $newsId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $oComment = new Comments($faqConfig);
    $comments = $oComment->getCommentsData($newsId, CommentType::NEWS);
    if ((is_countable($comments) ? count($comments) : 0) > 0) {
        ?>
        <div class="row"><strong><?= Translation::get('ad_entry_comment') ?></strong></div>
        <?php
    }
    foreach ($comments as $item) {
        ?>
        <div class="row">
            <?= Translation::get('ad_entry_commentby') ?>
            <a href="mailto:<?= $item['email'] ?>">
                <?= $item['user'] ?>
            </a>:<br>
            <?= $item['content'] ?><br>
            <?= Translation::get('newsCommentDate') . Date::createIsoDate($item['date'], 'Y-m-d H:i', false) ?>
            <a href="?action=delcomment&artid=<?= $newsId ?>&cmtid=<?= $item['id'] ?>&type=<?= CommentType::NEWS ?>">
                <i aria-hidden="true" class="fa fa-trash"></i>
            </a>
        </div>
    </div>
</div>
        <?php
    }
} elseif ('save-news' == $action && $user->perm->hasPermission($user->getUserId(), 'addnews')) {
    ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fa fa-pencil"></i>
            <?= Translation::get('ad_news_data') ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-12">
    <?php
    $dateStart = Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_SPECIAL_CHARS);
    $dateEnd = Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_SPECIAL_CHARS);
    $header = Filter::filterInput(INPUT_POST, 'newsheader', FILTER_SANITIZE_SPECIAL_CHARS);
    $content = Filter::filterInput(INPUT_POST, 'news', FILTER_SANITIZE_SPECIAL_CHARS);
    $author = Filter::filterInput(INPUT_POST, 'authorName', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = Filter::filterInput(INPUT_POST, 'authorEmail', FILTER_VALIDATE_EMAIL);
    $active = Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_SPECIAL_CHARS);
    $comment = Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_SPECIAL_CHARS);
    $link = Filter::filterInput(INPUT_POST, 'link', FILTER_SANITIZE_SPECIAL_CHARS);
    $linkTitle = Filter::filterInput(INPUT_POST, 'linkTitle', FILTER_SANITIZE_SPECIAL_CHARS);
    $newsLang = Filter::filterInput(INPUT_POST, 'langTo', FILTER_SANITIZE_SPECIAL_CHARS);
    $target = Filter::filterInput(INPUT_POST, 'target', FILTER_SANITIZE_SPECIAL_CHARS);

    $newsData = ['lang' => $newsLang, 'header' => $header, 'content' => html_entity_decode((string) $content), 'authorName' => $author, 'authorEmail' => $email, 'active' => (is_null($active)) ? 'n' : 'y', 'comment' => (is_null($comment)) ? 'n' : 'y', 'dateStart' => (empty($dateStart)) ? '00000000000000' : str_replace('-', '', (string) $dateStart) . '000000', 'dateEnd' => (empty($dateEnd)) ? '99991231235959' : str_replace('-', '', (string) $dateEnd) . '235959', 'link' => $link, 'linkTitle' => $linkTitle, 'date' => date('YmdHis'), 'target' => (is_null($target)) ? '' : $target];

    if ($news->addNewsEntry($newsData)) {
        echo Alert::success('ad_news_updatesuc');
    } else {
        echo Alert::danger('ad_news_insertfail', $faqConfig->getDb()->error());
    }
    printf('<div class="row">&rarr; <a href="?action=news">%s</a></p>', Translation::get('msgNews'));
    ?>
            </div>
        </div>
    <?php
} elseif ('update-news' == $action && $user->perm->hasPermission($user->getUserId(), 'editnews')) {
    ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fa fa-pencil"></i>
            <?= Translation::get('ad_news_data') ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-12">
    <?php
    $dateStart = Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_SPECIAL_CHARS);
    $dateEnd = Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_SPECIAL_CHARS);
    $header = Filter::filterInput(INPUT_POST, 'newsheader', FILTER_SANITIZE_SPECIAL_CHARS);
    $content = Filter::filterInput(INPUT_POST, 'news', FILTER_SANITIZE_SPECIAL_CHARS);
    $author = Filter::filterInput(INPUT_POST, 'authorName', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = Filter::filterInput(INPUT_POST, 'authorEmail', FILTER_VALIDATE_EMAIL);
    $active = Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_SPECIAL_CHARS);
    $comment = Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_SPECIAL_CHARS);
    $link = Filter::filterInput(INPUT_POST, 'link', FILTER_SANITIZE_SPECIAL_CHARS);
    $linkTitle = Filter::filterInput(INPUT_POST, 'linkTitle', FILTER_SANITIZE_SPECIAL_CHARS);
    $newsLang = Filter::filterInput(INPUT_POST, 'langTo', FILTER_SANITIZE_SPECIAL_CHARS);
    $target = Filter::filterInput(INPUT_POST, 'target', FILTER_SANITIZE_SPECIAL_CHARS);

    $newsData = [
        'lang' => $newsLang,
        'header' => $header,
        'content' => html_entity_decode((string) $content),
        'authorName' => $author,
        'authorEmail' => $email,
        'active' => (is_null($active)) ? 'n' : 'y',
        'comment' => (is_null($comment)) ? 'n' : 'y',
        'dateStart' => (empty($dateStart)) ? '00000000000000' : str_replace('-', '', (string) $dateStart) . '000000',
        'dateEnd' => (empty($dateEnd)) ? '99991231235959' : str_replace('-', '', (string) $dateEnd) . '235959',
        'link' => $link,
        'linkTitle' => $linkTitle,
        'date' => date('YmdHis'),
        'target' => (is_null($target)) ? '' : $target,
    ];

    $newsId = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($news->updateNewsEntry((int) $newsId, $newsData)) {
        echo Alert::success('ad_news_updatesuc');
    } else {
        echo Alert::danger('ad_news_updatefail', $faqConfig->getDb()->error());
    }
    printf('<div class="row">&rarr; <a href="?action=news">%s</a></p>', Translation::get('msgNews'));
    ?>
            </div>
        </div>
    <?php
} elseif ('delete-news' == $action && $user->perm->hasPermission($user->getUserId(), 'delnews')) {
    ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fa fa-pencil"></i>
            <?= Translation::get('ad_news_data') ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-12">
    <?php
    $precheck = Filter::filterInput(INPUT_POST, 'really', FILTER_SANITIZE_SPECIAL_CHARS, 'no');
    $deleteId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ('no' == $precheck) {
        ?>
    <div class="row"><?= Translation::get('ad_news_del');
    ?></div>
    <div class="text-center">
    <form action="?action=delete-news" method="post" accept-charset="utf-8">
    <input type="hidden" name="id" value="<?= $deleteId ?>">
    <input type="hidden" name="really" value="yes">
    <?= Token::getInstance()->getTokenInput('delete-news') ?>
        <button class="btn btn-warning" type="submit" name="submit">
            <?= Translation::get('ad_news_yesdelete') ?>
        </button>
        <a class="btn btn-inverse" onclick="history.back();">
            <?= Translation::get('ad_news_nodelete') ?>
        </a>
    </form>
    </div>
    
        <?php
    } else {
        if (Token::getInstance()->verifyToken('delete-news', $csrfToken)) {
            $deleteId = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $news->deleteNews((int)$deleteId);
            echo Alert::success('ad_news_delsuc');
            printf('<div class="row">&rarr; <a href="?action=news">%s</a></p>', Translation::get('msgNews'));
        }
    }
} else {
    echo Translation::get('err_NotAuth');
}
