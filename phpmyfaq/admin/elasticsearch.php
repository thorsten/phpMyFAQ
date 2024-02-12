<?php

/**
 * phpMyFAQ Elasticsearch information.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-25
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Instance\Elasticsearch;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

if (
    $user->perm->hasPermission($user->getUserId(), PermissionType::CONFIGURATION_EDIT->value) &&
    $faqConfig->get('search.enableElasticsearch')
) {
    $elasticsearch = new Elasticsearch($faqConfig);
    $esConfigData = $faqConfig->getElasticsearchConfig();

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('./admin/configuration/elasticsearch.twig');

    $templateVars = [
        'adminHeaderElasticsearch' => Translation::get('ad_menu_elasticsearch'),
        'adminElasticsearchButtonCreate' => Translation::get('ad_es_create_index'),
        'adminElasticsearchButtonIndex' => Translation::get('ad_es_bulk_index'),
        'adminElasticsearchButtonDelete' => Translation::get('ad_es_drop_index'),
        'adminElasticsearchStats' => Translation::get('ad_menu_searchstats'),
    ];

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
