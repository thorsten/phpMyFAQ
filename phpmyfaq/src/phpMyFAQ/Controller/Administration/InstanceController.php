<?php

/**
 * The Administration Instances Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\InstanceEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filesystem\Filesystem;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

class InstanceController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/instances', name: 'admin.instances', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::INSTANCE_ADD);

        return $this->render(
            '@admin/configuration/instances.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                ... $this->getBaseTemplateVars()
            ]
        );
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/instance/edit/:id', name: 'admin.instance.edit', methods: ['GET'])]
    public function edit(Request $request): Response
    {
        $this->userHasPermission(PermissionType::INSTANCE_EDIT);

        $instanceId = Filter::filterVar($request->get('id'), FILTER_VALIDATE_INT);

        $instance = $this->container->get('phpmyfaq.instance');
        $instanceData = $instance->getById($instanceId, 'array');

        return $this->render(
            '@admin/configuration/instances.edit.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'ad_menu_instances' => Translation::get('ad_menu_instances'),
                'instanceConfig' => $instance->getInstanceConfig((int) $instanceData->id),
                'ad_instance_url' => Translation::get('ad_instance_url'),
                'ad_instance_button' => Translation::get('ad_instance_button'),
                'ad_instance_path' => Translation::get('ad_instance_path'),
                'ad_instance_name' => Translation::get('ad_instance_name'),
                'ad_instance_config' => Translation::get('ad_instance_config'),
                'ad_entry_back' => Translation::get('ad_entry_back'),
                'instance' => $instanceData
            ]
        );
    }

    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    public function update(Request $request): Response
    {
        $this->userHasPermission(PermissionType::INSTANCE_EDIT);

        $instanceId = Filter::filterVar($request->get('id'), FILTER_VALIDATE_INT);

        $fileSystem = new Filesystem(PMF_ROOT_DIR);
        $currentClient = $this->container->get('phpmyfaq.instance.client');
        $currentClient->setFileSystem($fileSystem);

        $instance = $this->container->get('phpmyfaq.instance');
        $updatedClient = $this->container->get('phpmyfaq.instance.client');

        $moveInstance = false;
        $instance->setId($instanceId);

        // Collect updated data for database
        $updatedData = new InstanceEntity();
        $updatedData->setUrl(Filter::filterVar($request->get('url'), FILTER_VALIDATE_URL));
        $updatedData->setInstance(Filter::filterVar($request->get('instance'), FILTER_SANITIZE_SPECIAL_CHARS));
        $updatedData->setComment(Filter::filterVar($request->get('comment'), FILTER_SANITIZE_SPECIAL_CHARS));

        // Original data
        $originalData = $currentClient->getById($instanceId);

        if ($originalData->url !== $updatedData->getUrl() && !$instance->getConfig('isMaster')) {
            $moveInstance = true;
        }

        if (is_null($updatedData->getUrl())) {
            $result = ['updateError' => $this->configuration->getDb()->error()];
        } else {
            if ($updatedClient->update($instanceId, $updatedData)) {
                if ($moveInstance) {
                    $updatedClient->moveClientFolder($originalData->url, $updatedData->getUrl());
                    $updatedClient->deleteClientFolder($originalData->url);
                }
                $result = ['updateSuccess' => Translation::get('ad_config_saved')];
            } else {
                $result = ['updateError' => $this->configuration->getDb()->error()];
            }
        }

        return $this->render(
            '@admin/configuration/instances.twig',
            [
                ... $result,
                ... $this->getHeader($request),
                ... $this->getFooter(),
                ... $this->getBaseTemplateVars(),
            ]
        );
    }

    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     * @return string[]
     */
    private function getBaseTemplateVars(): array
    {
        $userPermInstanceAdd = $this->currentUser->perm->hasPermission(
            $this->currentUser->getUserId(),
            PermissionType::INSTANCE_ADD->value
        );

        $instance = $this->container->get('phpmyfaq.instance');
        $session = $this->container->get('session');
        $mainConfig = [];
        foreach ($instance->getAll() as $site) {
            $mainConfig[$site->id] = $instance->getInstanceConfig((int) $site->id)['isMaster'];
        }

        return [
            'userPermInstanceAdd' => $userPermInstanceAdd,
            'multisiteFolderIsWritable' => is_writable(PMF_ROOT_DIR . DIRECTORY_SEPARATOR . 'multisite'),
            'ad_instance_add' => Translation::get('ad_instance_add'),
            'allInstances' => $instance->getAll(),
            'csrfTokenDeleteInstance' => Token::getInstance($session)->getTokenString('delete-instance'),
            'csrfTokenAddInstance' => Token::getInstance($session)->getTokenString('add-instance'),
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
    }
}
