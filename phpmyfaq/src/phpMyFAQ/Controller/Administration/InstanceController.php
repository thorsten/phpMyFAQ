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
 * @copyright 2024-2026 phpMyFAQ Team
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

final class InstanceController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/instances', name: 'admin.instances', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::INSTANCE_ADD);

        return $this->render('@admin/configuration/instances.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/instance/edit/{id}', name: 'admin.instance.edit', methods: ['GET'])]
    public function edit(Request $request): Response
    {
        $this->userHasPermission(PermissionType::INSTANCE_EDIT);

        $instanceId = (int) Filter::filterVar($request->attributes->get('id'), FILTER_VALIDATE_INT);

        $instance = $this->container->get(id: 'phpmyfaq.instance');
        $instanceData = $instance->getById($instanceId, 'array');

        return $this->render('@admin/configuration/instances.edit.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'ad_menu_instances' => Translation::get(key: 'ad_menu_instances'),
            'instanceConfig' => $instance->getInstanceConfig((int) $instanceData->id),
            'ad_instance_url' => Translation::get(key: 'ad_instance_url'),
            'ad_instance_button' => Translation::get(key: 'ad_instance_button'),
            'ad_instance_path' => Translation::get(key: 'ad_instance_path'),
            'ad_instance_name' => Translation::get(key: 'ad_instance_name'),
            'ad_instance_config' => Translation::get(key: 'ad_instance_config'),
            'ad_entry_back' => Translation::get(key: 'ad_entry_back'),
            'instance' => $instanceData,
        ]);
    }

    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/instance/update', name: 'admin.instance.update', methods: ['POST'])]
    public function update(Request $request): Response
    {
        $this->userHasPermission(PermissionType::INSTANCE_EDIT);

        $instanceId = (int) Filter::filterVar($request->attributes->get('id'), FILTER_VALIDATE_INT);

        $fileSystem = new Filesystem(PMF_ROOT_DIR);
        $currentClient = $this->container->get(id: 'phpmyfaq.instance.client');
        $currentClient->setFileSystem($fileSystem);

        $instance = $this->container->get(id: 'phpmyfaq.instance');
        $updatedClient = $this->container->get(id: 'phpmyfaq.instance.client');

        $moveInstance = false;
        $instance->setId($instanceId);

        // Collect updated data for database
        $instanceEntity = new InstanceEntity();
        $instanceEntity->setUrl(Filter::filterVar($request->attributes->get('url'), FILTER_VALIDATE_URL));
        $instanceEntity->setInstance(Filter::filterVar(
            $request->attributes->get('instance'),
            FILTER_SANITIZE_SPECIAL_CHARS,
        ));
        $instanceEntity->setComment(Filter::filterVar(
            $request->attributes->get('comment'),
            FILTER_SANITIZE_SPECIAL_CHARS,
        ));

        // Original data
        $originalData = $currentClient->getById($instanceId);

        if ($originalData->url !== $instanceEntity->getUrl() && !$instance->getConfig('isMaster')) {
            $moveInstance = true;
        }

        $result = [];
        if (is_null($instanceEntity->getUrl())) {
            $result = ['updateError' => $this->configuration->getDb()->error()];
        }

        if ($updatedClient->update($instanceId, $instanceEntity)) {
            if ($moveInstance) {
                $updatedClient->moveClientFolder($originalData->url, $instanceEntity->getUrl());
                $updatedClient->deleteClientFolder($originalData->url);
            }

            $result = ['updateSuccess' => Translation::get(key: 'ad_config_saved')];
        }

        if (!$updatedClient->update($instanceId, $instanceEntity)) {
            $result = ['updateError' => $this->configuration->getDb()->error()];
        }

        return $this->render('@admin/configuration/instances.twig', [
            ...$result,
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
        ]);
    }

    /**
     * @return array<string, string>
     * @throws \Exception
     * @throws LoaderError
     * @throws Exception
     */
    private function getBaseTemplateVars(): array
    {
        $userPermInstanceAdd = $this->currentUser->perm->hasPermission(
            $this->currentUser->getUserId(),
            PermissionType::INSTANCE_ADD->value,
        );

        $instance = $this->container->get(id: 'phpmyfaq.instance');

        $mainConfig = [];
        foreach ($instance->getAll() as $site) {
            $mainConfig[$site->id] = $instance->getInstanceConfig((int) $site->id)['isMaster'];
        }

        return [
            'userPermInstanceAdd' => $userPermInstanceAdd,
            'multisiteFolderIsWritable' => is_writable(PMF_ROOT_DIR . DIRECTORY_SEPARATOR . 'multisite'),
            'ad_instance_add' => Translation::get(key: 'ad_instance_add'),
            'allInstances' => $instance->getAll(),
            'csrfTokenDeleteInstance' => Token::getInstance($this->session)->getTokenString('delete-instance'),
            'csrfTokenAddInstance' => Token::getInstance($this->session)->getTokenString('add-instance'),
            'mainConfig' => $mainConfig,
            'requestHost' => Request::createFromGlobals()->getHost(),
            'ad_instance_button' => Translation::get(key: 'ad_instance_button'),
            'ad_instance_hint' => Translation::get(key: 'ad_instance_hint'),
            'ad_instance_admin' => Translation::get(key: 'ad_instance_admin'),
            'ad_instance_password' => Translation::get(key: 'ad_instance_password'),
            'ad_instance_email' => Translation::get(key: 'ad_instance_email'),
            'ad_instance_name' => Translation::get(key: 'ad_instance_name'),
            'ad_instance_path' => Translation::get(key: 'ad_instance_path'),
            'ad_instance_url' => Translation::get(key: 'ad_instance_url'),
            'ad_instance_error_notwritable' => Translation::get(key: 'ad_instance_error_notwritable'),
            'ad_menu_instances' => Translation::get(key: 'ad_menu_instances'),
        ];
    }
}
