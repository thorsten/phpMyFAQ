<?php

/**
 * Request removal page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-02-03
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Twig\Extension\DebugExtension;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

$faqSession->userTracking('request_removal', 0);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$twig->addExtension(new DebugExtension());
$twigTemplate = $twig->loadTemplate('./request-removal.twig');

// Twig template variables
$templateVars = [
    'pageHeader' => Translation::get('msgUserRemoval'),
    'msgContact' => Translation::get('msgContact'),
    'msgUserRemovalText' => Translation::get('msgUserRemovalText'),
    'msgContactRemove' => Translation::get('msgContactRemove'),
    'msgContactPrivacyNote' => Translation::get('msgContactPrivacyNote'),
    'msgPrivacyNote' => Translation::get('msgPrivacyNote'),
    'privacyURL' => Strings::htmlentities($faqConfig->get('main.privacyURL')),
    'msgNewContentName' => Translation::get('msgNewContentName'),
    'msgNewContentMail' => Translation::get('msgNewContentMail'),
    'ad_user_loginname' => Translation::get('ad_user_loginname'),
    'csrf' => Token::getInstance()->getTokenInput('request-removal'),
    'lang' => $Language->getLanguage(),
    'userId' => $user->getUserId(),
    'defaultContentMail' => ($user->getUserId() > 0) ? $user->getUserData('email') : '',
    'defaultContentName' =>
        ($user->getUserId() > 0) ? Strings::htmlentities($user->getUserData('display_name')) : '',
    'defaultLoginName' => ($user->getUserId() > 0) ? Strings::htmlentities($user->getLogin()) : '',
    'msgMessage' => Translation::get('msgMessage'),
    'msgS2FButton' => Translation::get('msgS2FButton')
];

$template->addRenderedTwigOutput(
    'mainPageContent',
    $twigTemplate->render($templateVars)
);
