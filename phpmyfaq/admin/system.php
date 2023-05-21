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
 * @copyright 2013-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-01-02
 */

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Transport\Exception\NoNodeAvailableException;
use phpMyFAQ\Component\Alert;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();
$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

$loader = new FilesystemLoader('./assets/templates');
$twig = new Environment($loader);
$template = $twig->load('./configuration/system.twig');

if ($user->perm->hasPermission($user->getUserId(), 'editconfig')) {
    $faqSystem = new System();

    if ($faqConfig->get('search.enableElasticsearch')) {
        try {
            $esFullInformation = $faqConfig->getElasticsearch()->info();
            $esInformation = $esFullInformation['version']['number'];
        } catch (ClientResponseException | ServerResponseException | NoNodeAvailableException $e) {
            $faqConfig->getLogger()->error('Error while fetching Elasticsearch information', [$e->getMessage()]);
            $esInformation = 'n/a';
            echo Alert::danger('ad_entryins_fail');
        }
    } else {
        $esInformation = 'n/a';
    }

    $templateVars = [
        'adminHeaderSystemInfo' => Translation::get('ad_system_info'),
        'systemInformation' => [
            'phpMyFAQ Version' => $faqSystem->getVersion(),
            'phpMyFAQ API Version' => $faqSystem->getApiVersion(),
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
    echo Translation::get('err_NotAuth');
}
