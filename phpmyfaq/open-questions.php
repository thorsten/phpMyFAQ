<?php

/**
 * Open questions frontend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-17
 */

use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqSession->userTracking('open_questions', 0);

try {
    $template->parse(
        'mainPageContent',
        [
            'pageHeader' => Translation::get('msgOpenQuestions'),
            'msgQuestionText' => Translation::get('msgQuestionText'),
            'msgDate_User' => Translation::get('msgDate_User'),
            'msgQuestion2' => Translation::get('msgQuestion2'),
            'renderOpenQuestionTable' => $faq->renderOpenQuestions()
        ]
    );
} catch (Exception) {
}
