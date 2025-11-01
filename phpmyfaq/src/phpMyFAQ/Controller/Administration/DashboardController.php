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
 * @copyright 2024-2025 phpMyFAQ Team
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

        $session = $this->container->get(id: 'phpmyfaq.admin.session');
        $faq = $this->container->get(id: 'phpmyfaq.admin.faq');
        $backup = $this->container->get(id: 'phpmyfaq.admin.backup');
        $latestUsers = $this->container->get(id: 'phpmyfaq.admin.latest-users');

        $faqTableInfo = $this->configuration->getDb()->getTableStatus(Database::getTablePrefix());
        $userId = $this->currentUser->getUserId();

        $backupInfo = $backup->getLastBackupInfo();

        $templateVars = [
            'isDebugMode' => Environment::isDebugMode(),
            'isMaintenanceMode' => $this->configuration->get(item: 'main.maintenanceMode'),
            'isDevelopmentVersion' => System::isDevelopmentVersion(),
            'currentVersionApp' => System::getVersion(),
            'msgAdminWarningDevelopmentVersion' => sprintf(
                Translation::get(languageKey: 'msgAdminWarningDevelopmentVersion'),
                System::getVersion(),
                System::getGitHubIssuesUrl(),
            ),
            'adminDashboardInfoNumVisits' => $session->getNumberOfSessions(),
            'adminDashboardInfoNumFaqs' => $faqTableInfo[Database::getTablePrefix() . 'faqdata'],
            'adminDashboardInfoNumComments' => $faqTableInfo[Database::getTablePrefix() . 'faqcomments'],
            'adminDashboardInfoNumQuestions' => $faqTableInfo[Database::getTablePrefix() . 'faqquestions'],
            'adminDashboardInfoUser' => Translation::get(languageKey: 'msgNews'),
            'adminDashboardInfoNumUser' => $faqTableInfo[Database::getTablePrefix() . 'faquser'] - 1,
            'adminDashboardHeaderUsersOnline' => Translation::get(languageKey: 'msgUserOnline'),
            'adminDashboardInfoNumUsersOnline' => $session->getNumberOfOnlineUsers(windowSeconds: 600),
            'adminDashboardHeaderVisits' => Translation::get(languageKey: 'ad_stat_report_visits'),
            'hasUserTracking' => $this->configuration->get(item: 'main.enableUserTracking'),
            'adminDashboardHeaderInactiveFaqs' => Translation::get(languageKey: 'ad_record_inactive'),
            'adminDashboardInactiveFaqs' => $faq->getInactiveFaqsData(),
            'hasPermissionEditConfig' => $this->currentUser->perm->hasPermission(
                $userId,
                PermissionType::CONFIGURATION_EDIT->value,
            ),
            'showVersion' => $this->configuration->get(item: 'main.enableAutoUpdateHint'),
            'documentationUrl' => System::getDocumentationUrl(),
            'lastBackupDate' => $backupInfo['lastBackupDate'],
            'isBackupOlderThan30Days' => $backupInfo['isBackupOlderThan30Days'],
            'adminDashboardLatestUsers' => $latestUsers->getList(limit: 5),
        ];

        if (version_compare($this->configuration->getVersion(), System::getVersion(), operator: '<')) {
            $templateVars = [
                ...$templateVars,
                'hasVersionConflict' => true,
                'currentVersionDatabase' => $this->configuration->getVersion(),
            ];
        }

        if ($this->currentUser->perm->hasPermission($userId, PermissionType::CONFIGURATION_EDIT->value)) {
            $version = Filter::filterVar($request->get(key: 'param'), FILTER_SANITIZE_SPECIAL_CHARS);
            if (!$this->configuration->get(item: 'main.enableAutoUpdateHint') && $version === 'version') {
                try {
                    $versions = $this->container->get(id: 'phpmyfaq.admin.api')->getVersions();
                    $templateVars = [
                        ...$templateVars,
                        'adminDashboardShouldUpdateMessage' => false,
                        'adminDashboardLatestVersionMessage' => Translation::get(languageKey: 'ad_xmlrpc_latest'),
                        'adminDashboardVersions' => $versions,
                    ];

                    if (-1 === version_compare($versions['installed'], $versions['stable'])) {
                        $templateVars = [
                            ...$templateVars,
                            'adminDashboardShouldUpdateMessage' => true,
                            'adminDashboardLatestVersionMessage' => Translation::get(
                                languageKey: 'ad_you_should_update',
                            ),
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

        return $this->render(
            file: '@admin/dashboard.twig',
            context: [
                ...$this->getHeader($request),
                ...$this->getFooter(),
                ...$templateVars,
            ],
        );
    }
}
