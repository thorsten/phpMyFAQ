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

use phpMyFAQ\Administration\Api;
use phpMyFAQ\Administration\Backup;
use phpMyFAQ\Administration\Faq as AdminFaq;
use phpMyFAQ\Administration\LatestUsers;
use phpMyFAQ\Administration\Session as AdminSession;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Environment;
use phpMyFAQ\Filter;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Twig\Error\LoaderError;

final class DashboardController extends AbstractAdministrationController
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly AdminFaq $adminFaq,
        private readonly Backup $backup,
        private readonly LatestUsers $latestUsers,
        private readonly Api $adminApi,
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

        $faqTableInfo = $this->configuration->getDb()->getTableStatus(Database::getTablePrefix());
        $userId = $this->currentUser->getUserId();

        $backupInfo = $this->backup->getLastBackupInfo();

        $templateVars = [
            'isDebugMode' => Environment::isDebugMode(),
            'isMaintenanceMode' => $this->configuration->get(item: 'main.maintenanceMode'),
            'isDevelopmentVersion' => System::isDevelopmentVersion(),
            'currentVersionApp' => System::getVersion(),
            'msgAdminWarningDevelopmentVersion' => sprintf(
                Translation::get(key: 'msgAdminWarningDevelopmentVersion'),
                System::getVersion(),
                System::getGitHubIssuesUrl(),
            ),
            'adminDashboardInfoNumVisits' => $this->adminSession->getNumberOfSessions(),
            'adminDashboardInfoNumFaqs' => $faqTableInfo[Database::getTablePrefix() . 'faqdata'],
            'adminDashboardInfoNumComments' => $faqTableInfo[Database::getTablePrefix() . 'faqcomments'],
            'adminDashboardInfoNumQuestions' => $faqTableInfo[Database::getTablePrefix() . 'faqquestions'],
            'adminDashboardInfoUser' => Translation::get(key: 'msgNews'),
            'adminDashboardInfoNumUser' => $faqTableInfo[Database::getTablePrefix() . 'faquser'] - 1,
            'adminDashboardHeaderUsersOnline' => Translation::get(key: 'msgUserOnline'),
            'adminDashboardInfoNumUsersOnline' => $this->adminSession->getNumberOfOnlineUsers(windowSeconds: 600),
            'adminDashboardHeaderVisits' => Translation::get(key: 'ad_stat_report_visits'),
            'hasUserTracking' => $this->configuration->get(item: 'main.enableUserTracking'),
            'adminDashboardHeaderInactiveFaqs' => Translation::get(key: 'ad_record_inactive'),
            'adminDashboardInactiveFaqs' => $this->adminFaq->getInactiveFaqsData(),
            'hasPermissionEditConfig' => $this->currentUser?->perm->hasPermission(
                $userId,
                PermissionType::CONFIGURATION_EDIT->value,
            ),
            'showVersion' => $this->configuration->get(item: 'main.enableAutoUpdateHint'),
            'documentationUrl' => System::getDocumentationUrl(),
            'lastBackupDate' => $backupInfo['lastBackupDate'],
            'isBackupOlderThan30Days' => $backupInfo['isBackupOlderThan30Days'],
            'adminDashboardLatestUsers' => $this->latestUsers->getList(limit: 5),
            'hasRecentNews' => $this->configuration->get(item: 'main.enableRecentNews'),
        ];

        if (version_compare($this->configuration->getVersion(), System::getVersion(), operator: '<')) {
            $templateVars = [
                ...$templateVars,
                'hasVersionConflict' => true,
                'currentVersionDatabase' => $this->configuration->getVersion(),
            ];
        }

        if ($this->currentUser->perm->hasPermission($userId, PermissionType::CONFIGURATION_EDIT->value)) {
            $version = Filter::filterVar($request->attributes->get(key: 'param'), FILTER_SANITIZE_SPECIAL_CHARS);
            if (!$this->configuration->get(item: 'main.enableAutoUpdateHint') && $version === 'version') {
                try {
                    $versions = $this->adminApi->getVersions();
                    $templateVars = [
                        ...$templateVars,
                        'adminDashboardShouldUpdateMessage' => false,
                        'adminDashboardLatestVersionMessage' => Translation::get(key: 'ad_xmlrpc_latest'),
                        'adminDashboardVersions' => $versions,
                    ];

                    if (-1 === version_compare($versions['installed'], $versions['stable'])) {
                        $templateVars = [
                            ...$templateVars,
                            'adminDashboardShouldUpdateMessage' => true,
                            'adminDashboardLatestVersionMessage' => Translation::get(key: 'ad_you_should_update'),
                            'adminDashboardVersions' => $versions,
                        ];
                    }
                } catch (DecodingExceptionInterface|TransportExceptionInterface|Exception $e) {
                    $templateVars = [
                        ...$templateVars,
                        'adminDashboardErrorMessage' => $e->getMessage(),
                    ];
                }
            }

            $templateVars = [
                ...$templateVars,
                'showVersion' => $this->configuration->get(item: 'main.enableAutoUpdateHint') || $version === 'version',
            ];
        }

        return $this->render(file: '@admin/dashboard.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$templateVars,
        ]);
    }
}
