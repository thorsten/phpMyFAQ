<?php

/**
 * The Plugin API Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-07
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Controller\Administration\AbstractAdministrationController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PluginController extends AbstractAdministrationController
{
    #[Route(path: '/api/plugin/toggle', methods: ['POST'])]
    public function toggleStatus(Request $request): JsonResponse
    {
        $pluginManager = $this->container->get(id: 'phpmyfaq.plugin.plugin-manager');
        $pluginManager->loadPlugins();

        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? null;
        $active = (bool) ($data['active'] ?? false);

        if ($name === null || !array_key_exists($name, $pluginManager->getPlugins())) {
            return new JsonResponse(['success' => false, 'message' => 'Plugin not found'], 404);
        }

        if ($active) {
            $pluginManager->activatePlugin($name);
        } else {
            $pluginManager->deactivatePlugin($name);
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route(path: '/api/plugin/config', methods: ['POST'])]
    public function saveConfig(Request $request): JsonResponse
    {
        $pluginManager = $this->container->get(id: 'phpmyfaq.plugin.plugin-manager');
        $pluginManager->loadPlugins();

        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? null;
        $config = (array) ($data['config'] ?? []);

        if ($name === null || !array_key_exists($name, $pluginManager->getPlugins())) {
            return new JsonResponse(['success' => false, 'message' => 'Plugin not found'], 404);
        }

        $pluginManager->savePluginConfig($name, $config);

        return new JsonResponse(['success' => true]);
    }
}
