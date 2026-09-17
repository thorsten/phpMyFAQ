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
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/', name: 'admin.dashboard', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userIsAuthenticated();

        $userId = $this->currentUser->getUserId();

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
            'adminDashboardInfoUser' => Translation::get(key: 'msgNews'),
            'adminDashboardHeaderUsersOnline' => Translation::get(key: 'msgUserOnline'),
            'adminDashboardHeaderVisits' => Translation::get(key: 'ad_stat_report_visits'),
            'hasUserTracking' => $this->configuration->get(item: 'main.enableUserTracking'),
            'adminDashboardHeaderInactiveFaqs' => Translation::get(key: 'ad_record_inactive'),
            'hasPermissionEditConfig' => $this->currentUser->perm->hasPermission(
                $userId,
                PermissionType::CONFIGURATION_EDIT->value,
            ),
            'showVersion' => $this->configuration->get(item: 'main.enableAutoUpdateHint'),
            'documentationUrl' => System::getDocumentationUrl(),
            ...$this->getPermissionGatedWidgets($userId),
        ];

        if (version_compare($this->configuration->getVersion(), System::getVersion(), operator: '<')) {
            $templateVars = [
                ...$templateVars,
                'hasVersionConflict' => true,
                'currentVersionDatabase' => $this->configuration->getVersion(),
            ];
        }

        if ($this->currentUser->perm->hasPermission($userId, PermissionType::CONFIGURATION_EDIT->value)) {
            $version = Filter::filterVar($request->query->get(key: 'param'), FILTER_SANITIZE_SPECIAL_CHARS);
            if (!$this->configuration->get(item: 'main.enableAutoUpdateHint') && $version === 'version') {
                try {
                    $versions = $this->container->get(id: 'phpmyfaq.admin.api')->getVersions();
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

    /**
     * Returns the dashboard widgets the current user is allowed to see.
     *
     * The dashboard is the landing page of every authenticated user, so it must not require a
     * specific right itself. Each data set is therefore gated on the right its dedicated admin page
     * already requires, and the underlying queries are only executed when that right is held:
     *
     * - site-wide counters and the visit/top-ten charts: STATISTICS_VIEWLOGS (see the dashboard API)
     * - unpublished FAQs with their edit links: FAQ_EDIT (see admin.faq.edit)
     * - newest registered users: USER_EDIT (see admin.user.edit)
     * - last backup information: BACKUP (see admin.backup)
     *
     * A user holding none of these rights gets an empty dashboard instead of the data.
     *
     * @return array<string, mixed>
     */
    public function getPermissionGatedWidgets(int $userId): array
    {
        $permission = $this->currentUser->perm;

        $widgets = [
            'hasPermissionViewStatistics' => false,
            'hasPermissionViewInactiveFaqs' => false,
            'hasPermissionViewLatestUsers' => false,
            'hasPermissionViewBackup' => false,
            'adminDashboardInactiveFaqs' => [],
            'adminDashboardLatestUsers' => [],
        ];

        if ($permission->hasPermission($userId, PermissionType::STATISTICS_VIEWLOGS->value)) {
            $session = $this->container->get(id: 'phpmyfaq.admin.session');
            $faqTableInfo = $this->configuration->getDb()->getTableStatus(Database::getTablePrefix());

            $widgets = [
                ...$widgets,
                'hasPermissionViewStatistics' => true,
                'adminDashboardInfoNumVisits' => $session->getNumberOfSessions(),
                'adminDashboardInfoNumFaqs' => $faqTableInfo[Database::getTablePrefix() . 'faqdata'],
                'adminDashboardInfoNumComments' => $faqTableInfo[Database::getTablePrefix() . 'faqcomments'],
                'adminDashboardInfoNumQuestions' => $faqTableInfo[Database::getTablePrefix() . 'faqquestions'],
                'adminDashboardInfoNumUser' => $faqTableInfo[Database::getTablePrefix() . 'faquser'] - 1,
                'adminDashboardInfoNumUsersOnline' => $session->getNumberOfOnlineUsers(windowSeconds: 600),
            ];
        }

        if ($permission->hasPermission($userId, PermissionType::FAQ_EDIT->value)) {
            $widgets = [
                ...$widgets,
                'hasPermissionViewInactiveFaqs' => true,
                'adminDashboardInactiveFaqs' => $this->container->get(id: 'phpmyfaq.admin.faq')->getInactiveFaqsData(),
            ];
        }

        if ($permission->hasPermission($userId, PermissionType::USER_EDIT->value)) {
            $widgets = [
                ...$widgets,
                'hasPermissionViewLatestUsers' => true,
                'adminDashboardLatestUsers' => $this->container->get(id: 'phpmyfaq.admin.latest-users')->getList(
                    limit: 5,
                ),
            ];
        }

        if ($permission->hasPermission($userId, PermissionType::BACKUP->value)) {
            $backupInfo = $this->container->get(id: 'phpmyfaq.admin.backup')->getLastBackupInfo();

            $widgets = [
                ...$widgets,
                'hasPermissionViewBackup' => true,
                'lastBackupDate' => $backupInfo['lastBackupDate'],
                'isBackupOlderThan30Days' => $backupInfo['isBackupOlderThan30Days'],
            ];
        }

        return $widgets;
    }
}
