<?php

/**
 * Show the session.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */

use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');
$user = CurrentUser::getCurrentUser($faqConfig);

$sessionId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($user->perm->hasPermission($user->getUserId(), PermissionType::STATISTICS_VIEWLOGS->value)) {
    $session = $container->get('phpmyfaq.admin.session');
    $time = $session->getTimeFromSessionId($sessionId);
    $trackingData = explode("\n", file_get_contents(PMF_CONTENT_DIR . '/core/data/tracking' . date('dmY', $time)));

    $templateVars = [
        'ad_sess_session' => Translation::get('ad_sess_session'),
        'sessionId' => $sessionId,
        'ad_sess_back' => Translation::get('ad_sess_back'),
        'ad_sess_referer' => Translation::get('ad_sess_referer'),
        'ad_sess_browser' => Translation::get('ad_sess_browser'),
        'ad_sess_ip' => Translation::get('ad_sess_ip'),
        'trackingData' => $trackingData
    ];

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('./admin/statistics/statistics.show.twig');

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
