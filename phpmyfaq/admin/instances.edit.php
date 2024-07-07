<?php

/**
 * Frontend to edit an instance.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-16
 */

use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Instance;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if ($user->perm->hasPermission($user->getUserId(), PermissionType::INSTANCE_EDIT->value)) {
    $instanceId = Filter::filterInput(INPUT_GET, 'instance_id', FILTER_VALIDATE_INT);

    $instance = new Instance($faqConfig);
    $instanceData = $instance->getById($instanceId, 'array');

    $templateVars = [
        'ad_menu_instances' => Translation::get('ad_menu_instances'),
        'instanceConfig' => $instance->getInstanceConfig($instanceData->id),
        'ad_instance_url' => Translation::get('ad_instance_url'),
        'ad_instance_button' => Translation::get('ad_instance_button'),
        'ad_instance_path' => Translation::get('ad_instance_path'),
        'ad_instance_name' => Translation::get('ad_instance_name'),
        'ad_instance_config' => Translation::get('ad_instance_config'),
        'ad_entry_back' => Translation::get('ad_entry_back'),
        'instance' => $instanceData
    ];

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('./admin/configuration/instances.edit.twig');

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
