<?php

/**
 * Sessions per day.
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

use phpMyFAQ\Configuration;
use phpMyFAQ\Date;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\CoreExtension;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);
$request = Request::createFromGlobals();

if ($user->perm->hasPermission($user->getUserId(), PermissionType::STATISTICS_VIEWLOGS->value)) {
    $perpage = 50;
    $day = Filter::filterVar($request->request->get('day'), FILTER_VALIDATE_INT);
    $firstHour = mktime(0, 0, 0, date('m', $day), date('d', $day), date('Y', $day));
    $lastHour = mktime(23, 59, 59, date('m', $day), date('d', $day), date('Y', $day));

    $session = new Session($faqConfig);
    $sessionData = $session->getSessionsByDate($firstHour, $lastHour);
    $date = new Date($faqConfig);

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $twig->getExtension(CoreExtension::class)->setDateFormat('Y-m-d H:i', '%d days');
    $template = $twig->loadTemplate('./admin/statistics/sessions.day.twig');

    $templateVars = [
        'adminHeaderSessionsPerDay' => Translation::get('ad_sess_session'),
        'currentDay' => date('Y-m-d', $day),
        'msgIpAdress' => Translation::get('ad_sess_ip'),
        'msgSessionDate' => Translation::get('ad_sess_s_date'),
        'msgSession' => Translation::get('ad_sess_session'),
        'sessionData' => $sessionData,
    ];

    echo $template->render($templateVars);
} else {
    require 'no-permission.php';
}
