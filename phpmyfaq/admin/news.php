<?php
/**
 * The main administration file for the news.
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$news = new PMF_News($faqConfig);

if ('addnews' == $action && $user->perm->checkRight($user->getUserId(), "addnews")) {
    $twig->loadTemplate('news/add.twig')
        ->display(
            array(
                'PMF_LANG'         => $PMF_LANG,
                'languageSelector' => PMF_Language::selectLanguages($LANGCODE, false, array(), 'langTo'),
                'userDisplayName'  => $user->getUserData('display_name'),
                'userEmail'        => $user->getUserData('email')
            )
        );

} elseif ('news' == $action && $user->perm->checkRight($user->getUserId(), "editnews")) {
    $date       = new PMF_Date($faqConfig);
    $newsHeader = $news->getNewsHeader();
    foreach($newsHeader as $key => $newsItem) {
        $newsHeader[$key]['date'] = $date->format($newsItem['date']);
    }

    $twig->loadTemplate('news/list.twig')
        ->display(
            array(
                'PMF_LANG'   => $PMF_LANG,
                'newsHeader' => $newsHeader
            )
        );

    unset($date, $newsHeader, $key, $newsItem);
} elseif ('editnews' == $action && $user->perm->checkRight($user->getUserId(), 'editnews')) {
    $id       = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $newsData = $news->getNewsEntry($id, true);
    $dateStart = ($newsData['dateStart'] != '00000000000000' ? PMF_Date::createIsoDate($newsData['dateStart'], 'Y-m-d') : '');
    $dateEnd   = ($newsData['dateEnd'] != '99991231235959' ? PMF_Date::createIsoDate($newsData['dateEnd'], 'Y-m-d') : '');
    $newsId   = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $oComment = new PMF_Comment($faqConfig);
    $comments = $oComment->getCommentsData($newsId, PMF_Comment::COMMENT_TYPE_NEWS);

    foreach ($comments as $item) {
        $item['date'] = PMF_Date::createIsoDate($item['date'], 'Y-m-d H:i', false);
    }

    $twig->loadTemplate('news/edit.twig')
        ->display(
            array(
                'PMF_LANG' => $PMF_LANG,
                'comments' => $comments,
                'commentType' => PMF_Comment::COMMENT_TYPE_NEWS,
                'dateEnd' => $dateEnd,
                'dateStart' => $dateStart,
                'languageSelector' => PMF_Language::selectLanguages($newsData['lang'], false, array(), 'langTo'),
                'newsData' => $newsData
            )
        );
} elseif ('savenews' == $action && $user->perm->checkRight($user->getUserId(), "addnews")) {

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

    $success = $news->addNewsEntry($newsData);

    $twig->loadTemplate('news/save.twig')
        ->display(
            array(
                'PMF_LANG' => $PMF_LANG,
                'success'  => $success
            )
        );
} elseif ('updatenews' == $action && $user->perm->checkRight($user->getUserId(), "editnews")) {

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
        'lang'        => $newslang,
        'header'      => $header,
        'content'     => html_entity_decode($content),
        'authorName'  => $author,
        'authorEmail' => $email,
        'active'      => (is_null($active)) ? 'n' : 'y',
        'comment'     => (is_null($comment)) ? 'n' : 'y',
        'dateStart'   => (empty($dateStart)) ? '00000000000000' : str_replace('-', '', $dateStart) . '000000',
        'dateEnd'     => (empty($dateEnd))   ? '99991231235959' : str_replace('-', '', $dateEnd) . '235959',
        'link'        => $link,
        'linkTitle'   => $linktitle,
        'date'        => date('YmdHis'),
        'target'      => (is_null($target)) ? '' : $target
    );
    
    $newsId = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $success = $news->updateNewsEntry($newsId, $newsData);

    $twig->loadTemplate('news/update.twig')
        ->display(
            array(
                'PMF_LANG' => $PMF_LANG,
                'success'  => $success
            )
        );
} elseif ('deletenews' == $action && $user->perm->checkRight($user->getUserId(), "delnews")) {

    $precheck  = PMF_Filter::filterInput(INPUT_POST, 'really', FILTER_SANITIZE_STRING, 'no');
    $delete_id = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ('no' == $precheck) {
        $twig->loadTemplate('news/delete_check.twig')
            ->display(
                array(
                    'PMF_LANG' => $PMF_LANG,
                    'deleteId' => $delete_id
                )
            );

    } else {
        $delete_id = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $news->deleteNews($delete_id);

        $twig->loadTemplate('news/delete_success.twig')
            ->display(
                array(
                    'PMF_LANG' => $PMF_LANG
                )
            );
    }
} else {
    require 'noperm.php';
}
