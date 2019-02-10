<?php

/**
 * Open questions frontend.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-17
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

try {
    $faqsession->userTracking('open_questions', 0);
} catch (PMF_Exception $e) {
    // @todo handle the exception
}

if ($faqConfig->get('main.enableRssFeeds')) {
    $rssFeedOpenQuestions = ' <a href="feed/openquestions/rss.php" target="_blank"><i aria-hidden="true" class="fa fa-rss"></i></a>';
} else {
    $rssFeedOpenQuestions = '';
}

$tpl->parse(
    'writeContent',
    [
        'msgOpenQuestions' => $PMF_LANG['msgOpenQuestions'],
        'msgQuestionText' => $PMF_LANG['msgQuestionText'],
        'msgDate_User' => $PMF_LANG['msgDate_User'],
        'msgQuestion2' => $PMF_LANG['msgQuestion2'],
        'printOpenQuestions' => $faq->printOpenQuestions()
    ]
);

$tpl->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => $PMF_LANG['msgOpenQuestions'].$rssFeedOpenQuestions
    ]
);
