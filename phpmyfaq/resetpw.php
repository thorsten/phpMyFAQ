<?php

/**
 * Page where a user submits the new password using a signed reset token.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-05-08
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Twig\TwigWrapper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');

$faqSession = $container->get('phpmyfaq.user.session');
$faqSession->userTracking('reset_password', 0);

$userId = (int) Filter::filterVar($request->query->get('u'), FILTER_VALIDATE_INT);
$expires = (int) Filter::filterVar($request->query->get('exp'), FILTER_VALIDATE_INT);
$signature = (string) Filter::filterVar($request->query->get('sig'), FILTER_SANITIZE_SPECIAL_CHARS);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
$twigTemplate = $twig->loadTemplate('./resetpw.twig');

$templateVars = [
    ... $templateVars,
    'lang' => $faqConfig->getLanguage()->getLanguage(),
    'resetUserId' => $userId,
    'resetExpires' => $expires,
    'resetSignature' => $signature,
];

return $templateVars;
