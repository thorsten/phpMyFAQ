<?php

/**
 * Footer of the admin area.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-26
 * @deprecated will be removed in phpMyFAQ 4.1
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\System;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$template = $twig->loadTemplate('@admin/footer.twig');

$templateVars = [
    'msgSessionExpiringSoon' => Translation::get('msgSessionExpiringSoon'),
    'msgModalSessionWarning' => sprintf(Translation::get('ad_session_expiring'), PMF_AUTH_TIMEOUT_WARNING),
    'msgNoLogMeOut' => Translation::get('msgNoLogMeOut'),
    'msgYesKeepMeLoggedIn' => Translation::get('msgYesKeepMeLoggedIn'),
    'msgPoweredBy' => System::getPoweredByString(),
    'documentationUrl' => System::getDocumentationUrl(),
    'phpMyFaqUrl' => System::PHPMYFAQ_URL,
    'isUserLoggedIn' => $user->isLoggedIn(),
    'currentLanguage' => $faqLangCode,
    'currentTimeStamp' => time(),
    'currentYear' => date('Y'),
];

echo $template->render($templateVars);
