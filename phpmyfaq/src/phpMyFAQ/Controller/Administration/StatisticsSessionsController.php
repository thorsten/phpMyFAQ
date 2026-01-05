<?php

/**
 * The Session statistics Administration Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\StatisticsHelper;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class StatisticsSessionsController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/statistics/sessions', name: 'admin.statistics.sessions', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $adminSession = $this->container->get(id: 'phpmyfaq.admin.session');
        $date = $this->container->get(id: 'phpmyfaq.date');
        $visits = $this->container->get(id: 'phpmyfaq.visits');
        $statisticsHelper = new StatisticsHelper($adminSession, $visits, $date);

        $stats = $statisticsHelper->getTrackingFilesStatistics();
        $visitsPerDay = $adminSession->getNumberOfSessions();

        return $this->render(file: '@admin/statistics/sessions.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'adminHeaderSessions' => Translation::get(key: 'ad_stat_sess'),
            'csrfTokenClearVisits' => Token::getInstance($this->session)->getTokenString(page: 'clear-visits'),
            'msgClearVisits' => Translation::get(key: 'ad_clear_all_visits'),
            'msgDays' => Translation::get(key: 'ad_stat_days'),
            'numberOfDays' => $stats->numberOfDays,
            'msgVisits' => Translation::get(key: 'ad_stat_vis'),
            'numberOfVisits' => $visitsPerDay,
            'msgVisitsPerDay' => Translation::get(key: 'ad_stat_vpd'),
            'visitsPerDay' => $stats->numberOfDays !== 0
                ? round($visitsPerDay / $stats->numberOfDays, precision: 2)
                : 0,
            'msgFirstDate' => Translation::get(key: 'ad_stat_fien'),
            'firstDate' => $statisticsHelper->getFirstTrackingDate($stats->firstDate),
            'msgLastDate' => Translation::get(key: 'ad_stat_laen'),
            'lastDate' => $statisticsHelper->getLastTrackingDate($stats->lastDate),
            'msgSessionBrowse' => Translation::get(key: 'ad_stat_browse'),
            'renderedDaySelector' => $statisticsHelper->renderDaySelector(),
            'buttonOkay' => Translation::get(key: 'ad_stat_ok'),
            'msgSessionManagement' => Translation::get(key: 'ad_stat_management'),
            'csrfTokenSessions' => Token::getInstance($this->session)->getTokenInput(page: 'sessions'),
            'msgChooseMonth' => Translation::get(key: 'ad_stat_choose'),
            'renderedMonthSelector' => $statisticsHelper->renderMonthSelector(),
            'buttonDeleteMonth' => Translation::get(key: 'ad_stat_delete'),
            'csrfTokenExport' => Token::getInstance($this->session)->getTokenString(page: 'export-sessions'),
            'dateToday' => date(format: 'Y-m-d'),
            'datePickerMinDate' => date(format: 'Y-m-d', timestamp: $stats->firstDate),
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/statistics/sessions/:day', name: 'admin.statistics.session', methods: ['GET'])]
    public function viewDay(Request $request): Response
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $day = (int) Filter::filterVar($request->getPayload()->get(key: 'day'), FILTER_VALIDATE_INT);

        if ($day === 0) {
            $day = strtotime((string) $request->attributes->get(key: 'date'));
        }

        $firstHour = strtotime(datetime: 'midnight', baseTimestamp: $day);
        $lastHour = strtotime(datetime: 'tomorrow', baseTimestamp: $firstHour) - 1;

        $session = $this->container->get(id: 'phpmyfaq.admin.session');
        $sessionData = $session->getSessionsByDate($firstHour, $lastHour);

        return $this->render(file: '@admin/statistics/sessions.day.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'adminHeaderSessionsPerDay' => Translation::get(key: 'ad_sess_session'),
            'currentDay' => date(format: 'Y-m-d', timestamp: $day),
            'msgIpAddress' => Translation::get(key: 'ad_sess_ip'),
            'msgSessionDate' => Translation::get(key: 'ad_sess_s_date'),
            'msgSession' => Translation::get(key: 'ad_sess_session'),
            'sessionData' => $sessionData,
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/statistics/session/:sessionId', name: 'admin.statistics.session', methods: ['POST'])]
    public function viewSession(Request $request): Response
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $sessionId = (int) Filter::filterVar($request->attributes->get(key: 'sessionId'), FILTER_VALIDATE_INT);

        $session = $this->container->get(id: 'phpmyfaq.admin.session');
        $time = $session->getTimeFromSessionId($sessionId);
        $trackingData = explode(
            separator: "\n",
            string: file_get_contents(PMF_CONTENT_DIR . '/core/data/tracking' . date(format: 'dmY', timestamp: $time)),
        );

        return $this->render(file: '@admin/statistics/sessions.session.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'ad_sess_session' => Translation::get(key: 'ad_sess_session'),
            'sessionId' => $sessionId,
            'ad_sess_back' => Translation::get(key: 'ad_sess_back'),
            'ad_sess_referer' => Translation::get(key: 'ad_sess_referer'),
            'ad_sess_browser' => Translation::get(key: 'ad_sess_browser'),
            'ad_sess_ip' => Translation::get(key: 'ad_sess_ip'),
            'trackingData' => $trackingData,
            'thisDay' => date(format: 'Y-m-d', timestamp: $time),
        ]);
    }
}
