<?php

/**
 * The Plugin API Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-07
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Controller\Administration\AbstractAdministrationController;
use phpMyFAQ\Session\Token;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Exception;

final class PluginController extends AbstractAdministrationController
{
    #[Route(path: '/api/plugin/toggle', methods: ['POST'])]
    public function toggleStatus(Request $request): JsonResponse
    {
        $csrfToken = $request->headers->get('X-CSRF-Token');
        if (!Token::getInstance($this->session)->verifyToken($csrfToken, 'admin-plugins')) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }

        $pluginManager = $this->container->get(id: 'phpmyfaq.plugin.plugin-manager');
        $pluginManager->loadPlugins();

        $content = $request->getContent();
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid JSON payload: ' . json_last_error_msg()], 400);
        }

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
        $content = $request->getContent();
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid JSON payload: ' . json_last_error_msg()], 400);
        }

        $csrfToken = $data['csrf'] ?? $request->headers->get('X-CSRF-Token');
        if (!Token::getInstance($this->session)->verifyToken($csrfToken, 'admin-plugins')) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }

        $pluginManager = $this->container->get(id: 'phpmyfaq.plugin.plugin-manager');
        $pluginManager->loadPlugins();

        $name = $data['name'] ?? null;
        $config = (array) ($data['config'] ?? []);

        if ($name === null || !array_key_exists($name, $pluginManager->getPlugins())) {
            return new JsonResponse(['success' => false, 'message' => 'Plugin not found'], 404);
        }

        try {
            $pluginManager->savePluginConfig($name, $config);
        } catch (Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Failed to save configuration: ' . $e->getMessage()], 500);
        }

        return new JsonResponse(['success' => true]);
    }
}
