<?php

/**
 * The start page with some information about the FAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alexander M. Turek <me@derrabus.de>
 * @copyright 2005-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-02-05
 */

use phpMyFAQ\Administration\Api;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session;
use phpMyFAQ\System;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$faqTableInfo = $faqConfig->getDb()->getTableStatus(Database::getTablePrefix());
$user = CurrentUser::getCurrentUser($faqConfig);
$userId = $user->getUserId();
$faqSystem = new System();
$faqSession = new Session($faqConfig);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$template = $twig->loadTemplate('./admin/dashboard.twig');

$templateVars = [
    'isDebugMode' => DEBUG,
    'isMaintenanceMode' => $faqConfig->get('main.maintenanceMode'),
    'isDevelopmentVersion' => System::isDevelopmentVersion(),
    'currentVersionApp' => System::getVersion(),
    'msgAdminWarningDevelopmentVersion' => sprintf(
        Translation::get('msgAdminWarningDevelopmentVersion'),
        System::getVersion(),
        System::getGitHubIssuesUrl()
    ),
    'adminDashboardInfoNumVisits' => $faqSession->getNumberOfSessions(),
    'adminDashboardInfoNumFaqs' => $faqTableInfo[Database::getTablePrefix() . 'faqdata'],
    'adminDashboardInfoNumComments' => $faqTableInfo[Database::getTablePrefix() . 'faqcomments'],
    'adminDashboardInfoNumQuestions' => $faqTableInfo[Database::getTablePrefix() . 'faqquestions'],
    'adminDashboardInfoUser' => Translation::get('msgNews'),
    'adminDashboardInfoNumUser' => $faqTableInfo[Database::getTablePrefix() . 'faquser'] - 1,
    'adminDashboardHeaderVisits' => Translation::get('ad_stat_report_visits'),
    'hasUserTracking' => $faqConfig->get('main.enableUserTracking'),
    'adminDashboardHeaderInactiveFaqs' => Translation::get('ad_record_inactive'),
    'adminDashboardInactiveFaqs' => $faq->getInactiveFaqsData(),
    'hasPermissionEditConfig' => $user->perm->hasPermission($userId, PermissionType::CONFIGURATION_EDIT->value),
    'showVersion' => $faqConfig->get('main.enableAutoUpdateHint'),
    'documentationUrl' => System::getDocumentationUrl(),
];

if (version_compare($faqConfig->getVersion(), System::getVersion(), '<')) {
    $templateVars = [
        ...$templateVars,
        'hasVersionConflict' => true,
        'currentVersionDatabase' => $faqConfig->getVersion()
    ];
}

if ($user->perm->hasPermission($userId, PermissionType::CONFIGURATION_EDIT->value)) {
    $api = new Api($faqConfig, $faqSystem);

    $version = Filter::filterInput(INPUT_POST, 'param', FILTER_SANITIZE_SPECIAL_CHARS);
    if (!$faqConfig->get('main.enableAutoUpdateHint')) {
        if ($version === 'version') {
            $shouldUpdate = false;
            $errorMessage = '';
            $versions = [];
            try {
                $versions = $api->getVersions();
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
        'showVersion' => $faqConfig->get('main.enableAutoUpdateHint') || ($version === 'version'),
    ];
}

echo $template->render($templateVars);
