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
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-02-05
 */

use phpMyFAQ\Administration\Api;
use phpMyFAQ\Component\Alert;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Filter;
use phpMyFAQ\Session;
use phpMyFAQ\System;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Twig\Extension\DebugExtension;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$faqTableInfo = $faqConfig->getDb()->getTableStatus(Database::getTablePrefix());
$user = CurrentUser::getCurrentUser($faqConfig);
$faqSystem = new System();
$faqSession = new Session($faqConfig);

$twig = new TwigWrapper('./assets/templates');
$twig->addExtension(new DebugExtension());
$template = $twig->loadTemplate('./dashboard.twig');

$templateVars = [
    'adminHeaderDashboard' => Translation::get('admin_mainmenu_home'),
    'isMaintenanceMode' => $faqConfig->get('main.maintenanceMode'),
    'adminDashboardMaintenance' => Translation::get('msgMaintenanceMode'),
    'adminDashboardOnline' => Translation::get('msgOnlineMode'),
    'currentVersionApp' => System::getVersion(),
    'adminDashboardInfoHeader' => Translation::get('ad_pmf_info'),
    'adminDashboardInfoVisits' => Translation::get('ad_start_visits'),
    'adminDashboardInfoNumVisits' => $faqSession->getNumberOfSessions(),
    'adminDashboardInfoFaqs' => Translation::get('ad_start_articles'),
    'adminDashboardInfoNumFaqs' => $faqTableInfo[Database::getTablePrefix() . 'faqdata'],
    'adminDashboardInfoComments' => Translation::get('ad_start_comments'),
    'adminDashboardInfoNumComments' => $faqTableInfo[Database::getTablePrefix() . 'faqcomments'],
    'adminDashboardInfoQuestions' => Translation::get('msgOpenQuestions'),
    'adminDashboardInfoNumQuestions' => $faqTableInfo[Database::getTablePrefix() . 'faqquestions'],
    'adminDashboardInfoNews' => Translation::get('msgNews'),
    'adminDashboardInfoNumNews' => $faqTableInfo[Database::getTablePrefix() . 'faqnews'],
    'adminDashboardInfoUser' => Translation::get('msgNews'),
    'adminDashboardInfoNumUser' => $faqTableInfo[Database::getTablePrefix() . 'faquser'] - 1,
    'hasUserTracking' => $faqConfig->get('main.enableUserTracking'),
    'adminDashboardHeaderInactiveFaqs' => Translation::get('ad_record_inactive'),
    'adminDashboardInactiveFaqs' => $faq->getInactiveFaqsData(),
    'hasPermissionEditConfig' => $user->perm->hasPermission($user->getUserId(), 'editconfig'),
    'showVersion' => $faqConfig->get('main.enableAutoUpdateHint'),
];

if (version_compare($faqConfig->getVersion(), System::getVersion(), '<')) {
    $templateVars = [
        ...$templateVars,
        'hasVersionConflict' => true,
        'currentVersionDatabase' => $faqConfig->getVersion()
    ];
}

if (System::isDevelopmentVersion()) {
    $templateVars = [
        ...$templateVars,
        'isDevelopmentVersion' => true
    ];
}

if ($faqConfig->get('main.enableUserTracking')) {
    $templateVars = [
        ...$templateVars,
        'adminDashboardHeaderVisits' => Translation::get('ad_stat_report_visits')
    ];
}

if ($user->perm->hasPermission($user->getUserId(), 'editconfig')) {
    $api = new Api($faqConfig);

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

    $getJson = Filter::filterInput(INPUT_POST, 'getJson', FILTER_SANITIZE_SPECIAL_CHARS);
    if ('verify' === $getJson) {
        $issues = [];
        $errorMessageVerification = '';
        try {
            $versionMismatch = false;
            if (!$api->isVerified()) {
                $versionMismatch = true;
            } else {
                $issues = $api->getVerificationIssues();
                if (1 < count($issues)) {
                    echo Alert::danger('ad_verification_notokay');
                    echo '<ul>';
                    foreach ($issues as $file => $hash) {
                        if ('created' === $file) {
                            continue;
                        }
                        printf(
                            '<li><span class="pmf-popover" data-bs-toggle="popover" data-bs-container="body" title="SHA-1" data-bs-content="%s">%s</span></li>',
                            $hash,
                            $file
                        );
                    }
                    echo '</ul>';
                } else {
                    echo Alert::success('ad_verification_okay');
                }
            }
        } catch (Exception $e) {
            $errorMessageVerification = $e->getMessage();
        }
        $templateVars = [
            ...$templateVars,
            'showVerificationResult' => true,
            'adminDashboardVersionMismatch' => $versionMismatch,
            'adminDashboardErrorMessageVerification' => $errorMessageVerification,
            'adminDashboardVerificationIssues' => $issues,
            'adminDashboardVerificationNotOkay' => Translation::get('ad_verification_notokay'),
        ];
    }

    $templateVars = [
        ...$templateVars,
        'showVersion' => $faqConfig->get('main.enableAutoUpdateHint') || ($version === 'version'),
        'adminDashboardHeaderOnlineInfo' => Translation::get('ad_online_info'),
        'adminDashboardButtonGetLatestVersion' => Translation::get('ad_xmlrpc_button'),
        'adminDashboardHeaderVerification' => Translation::get('ad_online_verification'),
        'adminDashboardButtonVerification' => Translation::get('ad_verification_button')
    ];
}

echo $template->render($templateVars);
