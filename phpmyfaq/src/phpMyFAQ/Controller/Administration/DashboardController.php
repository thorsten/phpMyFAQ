<?php

/**
 * The Administration Dashboard Controller
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
 * @since     2024-12-29
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\Backup;
use phpMyFAQ\Administration\Faq as AdminFaq;
use phpMyFAQ\Administration\RecentUsers;
use phpMyFAQ\Administration\Session as AdminSession;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Environment;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class DashboardController extends AbstractAdministrationController
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly AdminFaq $adminFaq,
        private readonly Backup $backup,
        private readonly RecentUsers $recentUsers,
    ) {
        parent::__construct();
    }

    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/', name: 'admin.dashboard', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $this->userIsAuthenticated();

        // Every widget is gated on the permission its data requires on the sibling admin routes,
        // so a logged-in user without admin rights gets an empty dashboard instead of the data.
        $canViewStatistics = $this->userMay(PermissionType::STATISTICS_VIEWLOGS);
        $canViewInactiveFaqs = $this->userMay(PermissionType::FAQ_EDIT);
        // The shortcut to the FAQ overview is gated on the same right as admin.faqs itself,
        // otherwise a user sees a working link that ends in a 403.
        $canViewFaqOverview = $canViewInactiveFaqs;
        $canViewRecentUsers = $this->userMay(PermissionType::USER_EDIT);
        $canViewBackupStatus = $this->userMay(PermissionType::BACKUP);
        $canEditConfiguration = $this->userMay(PermissionType::CONFIGURATION_EDIT);

        $templateVars = [
            'isDebugMode' => Environment::isDebugMode(),
            'isMaintenanceMode' => $this->configuration->get(item: 'main.maintenanceMode'),
            'isDevelopmentVersion' => System::isDevelopmentVersion(),
            'currentVersionApp' => System::getVersion(),
            'msgAdminWarningDevelopmentVersion' => sprintf(
                Translation::getString(key: 'msgAdminWarningDevelopmentVersion'),
                System::getVersion(),
                System::getGitHubIssuesUrl(),
            ),
            'canViewStatistics' => $canViewStatistics,
            'canViewInactiveFaqs' => $canViewInactiveFaqs,
            'canViewFaqOverview' => $canViewFaqOverview,
            'canViewRecentUsers' => $canViewRecentUsers,
            'canViewBackupStatus' => $canViewBackupStatus,
            'adminDashboardInfoUser' => Translation::get(key: 'msgNews'),
            'adminDashboardHeaderUsersOnline' => Translation::get(key: 'msgUserOnline'),
            'adminDashboardHeaderVisits' => Translation::get(key: 'ad_stat_report_visits'),
            'hasUserTracking' => $this->configuration->get(item: 'main.enableUserTracking'),
            'adminDashboardHeaderInactiveFaqs' => Translation::get(key: 'ad_record_inactive'),
            'adminDashboardInactiveFaqs' => $canViewInactiveFaqs ? $this->adminFaq->getInactiveFaqsData() : [],
            'hasPermissionEditConfig' => $canEditConfiguration,
            'showVersion' => $this->configuration->get(item: 'main.enableAutoUpdateHint'),
            'documentationUrl' => System::getDocumentationUrl(),
            'adminDashboardRecentUsers' => $canViewRecentUsers ? $this->recentUsers->getList(limit: 5) : [],
            'hasRecentNews' => $this->configuration->get(item: 'main.enableRecentNews'),
            'dashboardCsrfToken' => Token::getInstance($this->session)->getTokenString(page: 'dashboard'),
        ];

        if ($canViewStatistics) {
            $faqTableInfo = $this->configuration->getDb()->getTableStatus(Database::getTablePrefix());
            $templateVars = [
                ...$templateVars,
                'adminDashboardInfoNumVisits' => $this->adminSession->getNumberOfSessions(),
                'adminDashboardInfoNumFaqs' => $faqTableInfo[Database::getTablePrefix() . 'faqdata'],
                'adminDashboardInfoNumComments' => $faqTableInfo[Database::getTablePrefix() . 'faqcomments'],
                'adminDashboardInfoNumQuestions' => $faqTableInfo[Database::getTablePrefix() . 'faqquestions'],
                'adminDashboardInfoNumUser' => (int) $faqTableInfo[Database::getTablePrefix() . 'faquser'] - 1,
                'adminDashboardInfoNumUsersOnline' => $this->adminSession->getNumberOfOnlineUsers(windowSeconds: 600),
            ];
        }

        if ($canViewBackupStatus) {
            $backupInfo = $this->backup->getLastBackupInfo();
            $templateVars = [
                ...$templateVars,
                'lastBackupDate' => $backupInfo['lastBackupDate'],
                'isBackupOlderThan30Days' => $backupInfo['isBackupOlderThan30Days'],
            ];
        }

        if (version_compare($this->configuration->getVersion(), System::getVersion(), operator: '<')) {
            $templateVars = [
                ...$templateVars,
                'hasVersionConflict' => true,
                'currentVersionDatabase' => $this->configuration->getVersion(),
            ];
        }

        if ($canEditConfiguration) {
            // The live version check runs in the browser against the dashboard API; requesting
            // it here only switches the widget to the loader that triggers that check.
            $version = Filter::filterVar($request->query->get(key: 'param'), FILTER_SANITIZE_SPECIAL_CHARS);
            $templateVars = [
                ...$templateVars,
                'showVersion' =>
                    (bool) $this->configuration->get(item: 'main.enableAutoUpdateHint') || $version === 'version',
            ];
        }

        return $this->render(file: '@admin/dashboard.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$templateVars,
        ]);
    }
}
