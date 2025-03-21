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
use phpMyFAQ\Filter;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Twig\Error\LoaderError;

class DashboardController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     */
    #[Route('/', name: 'admin.dashboard', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userIsAuthenticated();

        $session = $this->container->get('phpmyfaq.admin.session');
        $faq = $this->container->get('phpmyfaq.faq');

        $faqTableInfo = $this->configuration->getDb()->getTableStatus(Database::getTablePrefix());
        $userId = $this->currentUser->getUserId();

        $templateVars = [
            'isDebugMode' => DEBUG,
            'isMaintenanceMode' => $this->configuration->get('main.maintenanceMode'),
            'isDevelopmentVersion' => System::isDevelopmentVersion(),
            'currentVersionApp' => System::getVersion(),
            'msgAdminWarningDevelopmentVersion' => sprintf(
                Translation::get('msgAdminWarningDevelopmentVersion'),
                System::getVersion(),
                System::getGitHubIssuesUrl()
            ),
            'adminDashboardInfoNumVisits' => $session->getNumberOfSessions(),
            'adminDashboardInfoNumFaqs' => $faqTableInfo[Database::getTablePrefix() . 'faqdata'],
            'adminDashboardInfoNumComments' => $faqTableInfo[Database::getTablePrefix() . 'faqcomments'],
            'adminDashboardInfoNumQuestions' => $faqTableInfo[Database::getTablePrefix() . 'faqquestions'],
            'adminDashboardInfoUser' => Translation::get('msgNews'),
            'adminDashboardInfoNumUser' => $faqTableInfo[Database::getTablePrefix() . 'faquser'] - 1,
            'adminDashboardHeaderVisits' => Translation::get('ad_stat_report_visits'),
            'hasUserTracking' => $this->configuration->get('main.enableUserTracking'),
            'adminDashboardHeaderInactiveFaqs' => Translation::get('ad_record_inactive'),
            'adminDashboardInactiveFaqs' => $faq->getInactiveFaqsData(),
            'hasPermissionEditConfig' => $this->currentUser->perm->hasPermission(
                $userId,
                PermissionType::CONFIGURATION_EDIT->value
            ),
            'showVersion' => $this->configuration->get('main.enableAutoUpdateHint'),
            'documentationUrl' => System::getDocumentationUrl(),
        ];

        if (version_compare($this->configuration->getVersion(), System::getVersion(), '<')) {
            $templateVars = [
                ... $templateVars,
                'hasVersionConflict' => true,
                'currentVersionDatabase' => $this->configuration->getVersion()
            ];
        }

        if ($this->currentUser->perm->hasPermission($userId, PermissionType::CONFIGURATION_EDIT->value)) {
            $version = Filter::filterVar($request->get('param'), FILTER_SANITIZE_SPECIAL_CHARS);
            if (!$this->configuration->get('main.enableAutoUpdateHint')) {
                if ($version === 'version') {
                    try {
                        $versions = $this->container->get('phpmyfaq.admin.api')->getVersions();
                        if (-1 === version_compare($versions['installed'], $versions['stable'])) {
                            $templateVars = [
                                ...$templateVars,
                                'adminDashboardShouldUpdateMessage' => true,
                                'adminDashboardLatestVersionMessage' => Translation::get('ad_you_should_update'),
                                'adminDashboardVersions' => $versions,

                            ];
                        } else {
                            $templateVars = [
                                ...$templateVars,
                                'adminDashboardShouldUpdateMessage' => false,
                                'adminDashboardLatestVersionMessage' => Translation::get('ad_xmlrpc_latest'),
                                'adminDashboardVersions' => $versions,

                            ];
                        }
                    } catch (DecodingExceptionInterface | TransportExceptionInterface | Exception $e) {
                        $templateVars = [
                            ...$templateVars,
                            'adminDashboardErrorMessage' => $e->getMessage()
                        ];
                    }
                }
            }

            $templateVars = [
                ...$templateVars,
                'showVersion' => $this->configuration->get('main.enableAutoUpdateHint') || ($version === 'version'),
            ];
        }

        return $this->render(
            '@admin/dashboard.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                ... $templateVars,

            ]
        );
    }
}
