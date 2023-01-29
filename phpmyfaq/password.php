<?php

/**
 * This is the page there a user can request a new password.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-26
 */

use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

try {
    $faqSession->userTracking('forgot_password', 0);
} catch (Exception $exception) {
    $faqConfig->getLogger()->error('Tracking of forgot password', ['exception' => $exception->getMessage()]);
}

if ($faqConfig->get('security.enableRegistration')) {
    $template->parseBlock(
        'mainPageContent',
        'enableRegistration',
        [
            'registerUser' => Translation::get('msgRegistration'),
        ]
    );
}

if ($faqConfig->isSignInWithMicrosoftActive()) {
    $template->parseBlock(
        'mainPageContent', 'useSignInWithMicrosoft', [
            'msgSignInWithMicrosoft' => Translation::get('msgSignInWithMicrosoft'),
        ]
    );
}

$template->parse(
    'mainPageContent',
    [
        'pageHeader' => Translation::get('lostPassword'),
        'lang' => $Language->getLanguage(),
        'msgUsername' => Translation::get('ad_auth_user'),
        'msgEmail' => Translation::get('ad_entry_email'),
        'msgSubmit' => Translation::get('msgNewContentSubmit'),
    ]
);
