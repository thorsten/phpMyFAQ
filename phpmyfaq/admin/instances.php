<?php

/**
 * The main multi-site instances frontend.
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
 * @since     2012-03-16
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Entity\InstanceEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filesystem;
use phpMyFAQ\Filter;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Client;
use phpMyFAQ\Session\Token;
use phpMyFAQ\System;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

if ($user->perm->hasPermission($user->getUserId(), PermissionType::INSTANCE_EDIT->value)) {
    $fileSystem = new Filesystem(PMF_ROOT_DIR);
    $instance = new Instance($faqConfig);
    $currentClient = new Client($faqConfig);
    $currentClient->setFileSystem($fileSystem);
    $instanceId = Filter::filterInput(INPUT_POST, 'instance_id', FILTER_VALIDATE_INT);

    $templateVars = [];

    // Update client instance
    if ('update-instance' === $action && is_integer($instanceId)) {
        $system = new System();
        $updatedClient = new Client($faqConfig);
        $moveInstance = false;
        $instance->setId($instanceId);

        // Collect updated data for database
        $updatedData = new InstanceEntity();
        $updatedData->setUrl(Filter::filterInput(INPUT_POST, 'url', FILTER_VALIDATE_URL));
        $updatedData->setInstance(Filter::filterInput(INPUT_POST, 'instance', FILTER_SANITIZE_SPECIAL_CHARS));
        $updatedData->setComment(Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_SPECIAL_CHARS));

        // Original data
        $originalData = $currentClient->getById($instanceId);

        if ($originalData->url !== $updatedData->getUrl() && !$instance->getConfig('isMaster')) {
            $moveInstance = true;
        }

        if (is_null($updatedData->getUrl())) {
            $templateVars = [
                ... $templateVars,
                'updateError' => $faqConfig->getDb()->error(),
            ];
        } else {
            if ($updatedClient->update($instanceId, $updatedData)) {
                if ($moveInstance) {
                    $updatedClient->moveClientFolder($originalData->url, $updatedData->getUrl());
                    $updatedClient->deleteClientFolder($originalData->url);
                }
                $templateVars = [
                    ... $templateVars,
                    'updateSuccess' => Translation::get('ad_config_saved'),
                ];
            } else {
                $templateVars = [
                    ... $templateVars,
                    'updateError' => $faqConfig->getDb()->error(),
                ];
            }
        }
    }

    $mainConfig = [];
    foreach ($instance->getAll() as $site) {
        $mainConfig[$site->id] = $instance->getInstanceConfig($site->id)['isMaster'];
    }

    $templateVars = [
        ... $templateVars,
        'userPermInstanceAdd' => $user->perm->hasPermission($user->getUserId(), PermissionType::INSTANCE_ADD->value),
        'multisiteFolderIsWritable' => is_writable(PMF_ROOT_DIR . DIRECTORY_SEPARATOR . 'multisite'),
        'ad_instance_add' => Translation::get('ad_instance_add'),
        'allInstances' => $instance->getAll(),
        'csrfTokenDeleteInstance' => Token::getInstance()->getTokenString('delete-instance'),
        'csrfTokenAddInstance' => Token::getInstance()->getTokenString('add-instance'),
        'mainConfig' => $mainConfig,
        'requestHost' => Request::createFromGlobals()->getHost(),
        'ad_instance_button' => Translation::get('ad_instance_button'),
        'ad_instance_hint' => Translation::get('ad_instance_hint'),
        'ad_instance_admin' => Translation::get('ad_instance_admin'),
        'ad_instance_password' => Translation::get('ad_instance_password'),
        'ad_instance_email' => Translation::get('ad_instance_email'),
        'ad_instance_name' => Translation::get('ad_instance_name'),
        'ad_instance_path' => Translation::get('ad_instance_path'),
        'ad_instance_url' => Translation::get('ad_instance_url'),
        'ad_instance_error_notwritable' => Translation::get('ad_instance_error_notwritable'),
        'ad_menu_instances' => Translation::get('ad_menu_instances')
    ];

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('@admin/configuration/instances.twig');

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
