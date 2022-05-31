<?php

/**
 * User Control Panel.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2012-01-12
 */

use phpMyFAQ\Services\Gravatar;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if ($user->isLoggedIn()) {
    try {
        $faqSession->userTracking('user_control_panel', $user->getUserId());
    } catch (Exception $e) {
        // @todo handle the exception
    }

    if ($faqConfig->get('main.enableGravatarSupport')) {
        $gravatar = new Gravatar($faqConfig);
        $gravatarImg = sprintf(
            '<a target="_blank" href="http://www.gravatar.com">%s</a>',
            $gravatar->getImage(
                $user->getUserData('email'),
                ['class' => 'img-responsive rounded-circle', 'size' => 125]
            )
        );
    } else {
        $gravatarImg = '';
    }

    $template->parse(
        'mainPageContent',
        [
            'headerUserControlPanel' => $PMF_LANG['headerUserControlPanel'],
            'ucpGravatarImage' => $gravatarImg,
            'userid' => $user->getUserId(),
            'csrf' => $user->getCsrfTokenFromSession(),
            'readonly' => $faqConfig->isLdapActive() ? 'readonly' : '',
            'msgRealName' => $PMF_LANG['ad_user_name'],
            'realname' => $user->getUserData('display_name'),
            'msgEmail' => $PMF_LANG['msgNewContentMail'],
            'email' => $user->getUserData('email'),
            'msgIsVisible' => $PMF_LANG['ad_user_data_is_visible'],
            'checked' => (int)$user->getUserData('is_visible') === 1 ? 'checked' : '',
            'msgPassword' => $PMF_LANG['ad_auth_passwd'],
            'msgConfirm' => $PMF_LANG['ad_user_confirm'],
            'msgSave' => $PMF_LANG['msgSave'],
            'msgCancel' => $PMF_LANG['msgCancel'],
        ]
    );

    $template->parseBlock(
        'index',
        'breadcrumb',
        [
            'breadcrumbHeadline' => $PMF_LANG['headerUserControlPanel']
        ]
    );
} else {
    // Redirect to login
    header('Location: ' . $faqConfig->getDefaultUrl());
    exit();
}
