<?php

/**
 * The main statistics page.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */

use phpMyFAQ\Date;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\StatisticsHelper;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');
$user = CurrentUser::getCurrentUser($faqConfig);
$request = Request::createFromGlobals();

if ($user->perm->hasPermission($user->getUserId(), PermissionType::STATISTICS_VIEWLOGS->value)) {
    $session = $container->get('phpmyfaq.admin.session');
    $date = new Date($faqConfig);
    $visits = $container->get('phpmyfaq.visits');
    $statisticsHelper = new StatisticsHelper($session, $visits, $date);

    $stats = $statisticsHelper->getTrackingFilesStatistics();
    $visitsPerDay = $session->getNumberOfSessions();

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('./admin/statistics/sessions.twig');

    $statdelete = Filter::filterVar($request->request->get('statdelete'), FILTER_SANITIZE_SPECIAL_CHARS);
    $month = Filter::filterVar($request->request->get('month'), FILTER_SANITIZE_SPECIAL_CHARS);
    $csrfTokenFromPost = Filter::filterVar($request->request->get('csrf'), FILTER_SANITIZE_SPECIAL_CHARS);
    $csrfTokenFromGet = Filter::filterVar($request->query->get('csrf'), FILTER_SANITIZE_SPECIAL_CHARS);

    if ($csrfTokenFromPost && !Token::getInstance()->verifyToken('sessions', $csrfTokenFromPost)) {
        $statdelete = null;
    }

    if ($csrfTokenFromGet && !Token::getInstance()->verifyToken('clear-visits', $csrfTokenFromGet)) {
        $clearVisits = false;
    } else {
        $clearVisits = true;
    }

    // Delete sessions and session files
    if ($statdelete == 'delete' && $month !== '') {
        $hasMessage = $statisticsHelper->deleteTrackingFiles($month);
        $message = Translation::get('ad_adminlog_delete_success');
    }

    // Reset all visits and sessions
    if ('clear-visits' === $action && $clearVisits) {
        $hasMessage = $statisticsHelper->clearAllVisits();
        $message = Translation::get('ad_reset_visits_success');
    }

    $templateVars = [
        'adminHeaderSessions' => Translation::get('ad_stat_sess'),
        'csrfTokenClearVisits' => Token::getInstance()->getTokenString('clear-visits'),
        'msgClearVisits' => Translation::get('ad_clear_all_visits'),
        'hasMessage' => $hasMessage ?? false,
        'message' => $message ?? '',
        'msgDays' => Translation::get('ad_stat_days'),
        'numberOfDays' => $stats->numberOfDays,
        'msgVisits' => Translation::get('ad_stat_vis'),
        'numberOfVisits' => $visitsPerDay,
        'msgVisitsPerDay' => Translation::get('ad_stat_vpd'),
        'visitsPerDay' => ($stats->numberOfDays != 0) ? round(($visitsPerDay / $stats->numberOfDays), 2) : 0,
        'msgFirstDate' =>  Translation::get('ad_stat_fien'),
        'firstDate' =>  $statisticsHelper->getFirstTrackingDate($stats->firstDate),
        'msgLastDate' => Translation::get('ad_stat_laen'),
        'lastDate' => $statisticsHelper->getLastTrackingDate($stats->lastDate),
        'msgSessionBrowse' => Translation::get('ad_stat_browse'),
        'renderedDaySelector' => $statisticsHelper->renderDaySelector(),
        'buttonOkay' => Translation::get('ad_stat_ok'),
        'msgSessionManagement' => Translation::get('ad_stat_management'),
        'csrfTokenSessions' => Token::getInstance()->getTokenInput('sessions'),
        'msgChooseMonth' => Translation::get('ad_stat_choose'),
        'renderedMonthSelector' => $statisticsHelper->renderMonthSelector(),
        'buttonDeleteMonth' => Translation::get('ad_stat_delete'),
        'msgExportSessions' => Translation::get('msgExportSessions'),
        'msgExportSessionsAsCSV' => Translation::get('msgExportSessionsAsCSV'),
        'csrfTokenExport' => Token::getInstance()->getTokenString('export-sessions'),
        'dateToday' => date('Y-m-d'),
        'msgExportSessionsFrom' => Translation::get('msgExportSessionsFrom'),
        'msgExportSessionsTo' => Translation::get('msgExportSessionsTo'),
        'datePickerMinDate' => date('Y-m-d', $stats->firstDate),
    ];

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
