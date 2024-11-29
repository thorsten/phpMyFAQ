<?php

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

class StatisticsSessionsController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/statistics/sessions', name: 'admin.statistics.sessions', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $session = $this->container->get('session');
        $adminSession = $this->container->get('phpmyfaq.admin.session');
        $date = $this->container->get('phpmyfaq.date');
        $visits = $this->container->get('phpmyfaq.visits');
        $statisticsHelper = new StatisticsHelper($adminSession, $visits, $date);

        $stats = $statisticsHelper->getTrackingFilesStatistics();
        $visitsPerDay = $adminSession->getNumberOfSessions();

        return $this->render(
            '@admin/statistics/sessions.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'adminHeaderSessions' => Translation::get('ad_stat_sess'),
                'csrfTokenClearVisits' => Token::getInstance($session)->getTokenString('clear-visits'),
                'msgClearVisits' => Translation::get('ad_clear_all_visits'),
                'msgDays' => Translation::get('ad_stat_days'),
                'numberOfDays' => $stats->numberOfDays,
                'msgVisits' => Translation::get('ad_stat_vis'),
                'numberOfVisits' => $visitsPerDay,
                'msgVisitsPerDay' => Translation::get('ad_stat_vpd'),
                'visitsPerDay' => ($stats->numberOfDays !== 0) ? round(($visitsPerDay / $stats->numberOfDays), 2) : 0,
                'msgFirstDate' =>  Translation::get('ad_stat_fien'),
                'firstDate' =>  $statisticsHelper->getFirstTrackingDate($stats->firstDate),
                'msgLastDate' => Translation::get('ad_stat_laen'),
                'lastDate' => $statisticsHelper->getLastTrackingDate($stats->lastDate),
                'msgSessionBrowse' => Translation::get('ad_stat_browse'),
                'renderedDaySelector' => $statisticsHelper->renderDaySelector(),
                'buttonOkay' => Translation::get('ad_stat_ok'),
                'msgSessionManagement' => Translation::get('ad_stat_management'),
                'csrfTokenSessions' => Token::getInstance($session)->getTokenInput('sessions'),
                'msgChooseMonth' => Translation::get('ad_stat_choose'),
                'renderedMonthSelector' => $statisticsHelper->renderMonthSelector(),
                'buttonDeleteMonth' => Translation::get('ad_stat_delete'),
                'csrfTokenExport' => Token::getInstance($session)->getTokenString('export-sessions'),
                'dateToday' => date('Y-m-d'),
                'datePickerMinDate' => date('Y-m-d', $stats->firstDate),
            ]
        );
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/statistics/sessions/:day', name: 'admin.statistics.session', methods: ['GET'])]
    public function viewDay(Request $request): Response
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $day = Filter::filterVar($request->get('day'), FILTER_VALIDATE_INT);

        $firstHour = strtotime('midnight', $day);
        $lastHour = strtotime('tomorrow', $firstHour) - 1;

        $session = $this->container->get('phpmyfaq.admin.session');
        $sessionData = $session->getSessionsByDate($firstHour, $lastHour);

        return $this->render(
            '@admin/statistics/sessions.day.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'adminHeaderSessionsPerDay' => Translation::get('ad_sess_session'),
                'currentDay' => date('Y-m-d', $day),
                'msgIpAddress' => Translation::get('ad_sess_ip'),
                'msgSessionDate' => Translation::get('ad_sess_s_date'),
                'msgSession' => Translation::get('ad_sess_session'),
                'sessionData' => $sessionData,
            ]
        );
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/statistics/session/:sessionId', name: 'admin.statistics.session', methods: ['POST'])]
    public function viewSession(Request $request): Response
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $sessionId = Filter::filterVar($request->get('sessionId'), FILTER_VALIDATE_INT);

        $session = $this->container->get('phpmyfaq.admin.session');
        $time = $session->getTimeFromSessionId($sessionId);
        $trackingData = explode("\n", file_get_contents(PMF_CONTENT_DIR . '/core/data/tracking' . date('dmY', $time)));

        return $this->render(
            '@admin/statistics/sessions.session.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'ad_sess_session' => Translation::get('ad_sess_session'),
                'sessionId' => $sessionId,
                'ad_sess_back' => Translation::get('ad_sess_back'),
                'ad_sess_referer' => Translation::get('ad_sess_referer'),
                'ad_sess_browser' => Translation::get('ad_sess_browser'),
                'ad_sess_ip' => Translation::get('ad_sess_ip'),
                'trackingData' => $trackingData,
                'thisDay' => date('Y-m-d', $time),
            ]
        );
    }
}
