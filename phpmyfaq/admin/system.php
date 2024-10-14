<?php

/**
 * phpMyFAQ System Information page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2013-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-01-02
 */

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Transport\Exception\NoNodeAvailableException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\System;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();
$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$template = $twig->loadTemplate('./admin/configuration/system.twig');

if ($user->perm->hasPermission($user->getUserId(), PermissionType::CONFIGURATION_EDIT->value)) {
    $faqSystem = new System();

    if ($faqConfig->get('search.enableElasticsearch')) {
        try {
            $esFullInformation = $faqConfig->getElasticsearch()->info();
            $esInformation = $esFullInformation['version']['number'];
        } catch (ClientResponseException | ServerResponseException | NoNodeAvailableException $e) {
            $faqConfig->getLogger()->error('Error while fetching Elasticsearch information', [$e->getMessage()]);
            $esInformation = 'n/a';
        }
    } else {
        $esInformation = 'n/a';
    }

    $templateVars = [
        'adminHeaderSystemInfo' => Translation::get('ad_system_info'),
        'systemInformation' => [
            'phpMyFAQ Version' => $faqSystem->getVersion(),
            'phpMyFAQ API Version' => $faqSystem->getApiVersion(),
            'phpMyFAQ Plugin API Version' => $faqSystem->getPluginVersion(),
            'phpMyFAQ Installation Path' => dirname((string) $request->server->get('SCRIPT_FILENAME'), 2),
            'Web server software' => $request->server->get('SERVER_SOFTWARE'),
            'Web server document root' => $request->server->get('DOCUMENT_ROOT'),
            'Web server Interface' => strtoupper(PHP_SAPI),
            'PHP Version' => PHP_VERSION,
            'PHP Extensions' => implode(', ', get_loaded_extensions()),
            'PHP Session path' => session_save_path(),
            'Database Server' => Database::getType(),
            'Database Server Version' => $faqConfig->getDb()->serverVersion(),
            'Database Client Version' => $faqConfig->getDb()->clientVersion(),
            'Elasticsearch Version' => $esInformation
        ]
    ];

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
