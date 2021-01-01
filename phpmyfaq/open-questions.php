<?php

/**
 * Open questions frontend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2002-09-17
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

try {
    $faqSession->userTracking('open_questions', 0);
} catch (Exception $e) {
    // @todo handle the exception
}

try {
    $template->parse(
        'mainPageContent',
        [
            'pageHeader' => $PMF_LANG['msgOpenQuestions'],
            'msgQuestionText' => $PMF_LANG['msgQuestionText'],
            'msgDate_User' => $PMF_LANG['msgDate_User'],
            'msgQuestion2' => $PMF_LANG['msgQuestion2'],
            'renderOpenQuestionTable' => $faq->renderOpenQuestions()
        ]
    );
} catch (Exception $e) {
}
