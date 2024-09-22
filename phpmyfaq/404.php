<?php

/**
 * 404 error page
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-01-25
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\SessionActionType;
use phpMyFAQ\Session;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);
$faqSession = new Session($faqConfig);
$faqSession->setCurrentUser($user);

$faqSession->userTracking(SessionActionType::NOT_FOUND->value, 0);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/' . TwigWrapper::getTemplateSetName());
$twigTemplate = $twig->loadTemplate('./404.twig');
