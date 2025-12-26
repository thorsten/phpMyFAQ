<?php

/**
 * The Setup Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-17
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Filter;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\System;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetupController extends AbstractController
{
    public function check(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        if (trim($request->getContent()) === '') {
            return $this->json(['message' => 'No version given.'], Response::HTTP_BAD_REQUEST);
        }

        $installedVersion = Filter::filterVar($request->getContent(), FILTER_SANITIZE_SPECIAL_CHARS);

        $update = new Update(new System(), $this->configuration);
        $update->setVersion($installedVersion);

        if (!$update->checkMaintenanceMode()) {
            return $this->json([
                'message' => 'Maintenance mode is not enabled. Please enable it first.',
            ], Response::HTTP_CONFLICT);
        }

        if (!$update->checkMinimumUpdateVersion($installedVersion)) {
            $message =
                'Your installed version is phpMyFAQ '
                . $installedVersion
                . '. Please update to at least phpMyFAQ 3.0 first.';
            return $this->json(['message' => $message], Response::HTTP_CONFLICT);
        }

        // Check hard requirements
        try {
            $update->checkPreUpgrade(Database::getType());
        } catch (Exception $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['message' => 'Installation check successful'], Response::HTTP_OK);
    }

    public function backup(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        if (trim($request->getContent()) === '') {
            return $this->json(['message' => 'No version given.'], Response::HTTP_BAD_REQUEST);
        }

        $update = new Update(new System(), $this->configuration);
        $update->setVersion($this->configuration->getVersion());

        $installedVersion = Filter::filterVar($request->getContent(), FILTER_SANITIZE_SPECIAL_CHARS);

        $configPath = PMF_ROOT_DIR . '/content/core/config';
        if (!version_compare(version1: $installedVersion, version2: '4.0.0-alpha') < 0) {
            $configPath = PMF_ROOT_DIR . '/config';
        }

        try {
            $pathToBackup = $update->createConfigBackup($configPath);
        } catch (Exception $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }

        return $this->json(['message' => 'Backup successful', 'backupFile' => $pathToBackup], Response::HTTP_OK);
    }

    public function updateDatabase(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        if (trim($request->getContent()) === '') {
            return $this->json(['message' => 'No version given.'], Response::HTTP_BAD_REQUEST);
        }

        $installedVersion = Filter::filterVar($request->getContent(), FILTER_SANITIZE_SPECIAL_CHARS);

        $update = new Update(new System(), $this->configuration);
        $update->setVersion($installedVersion);

        try {
            if ($update->applyUpdates()) {
                $this->configuration->set(key: 'main.maintenanceMode', value: 'false');
                return new JsonResponse(['success' => 'Database successfully updated.'], Response::HTTP_OK);
            }

            return new JsonResponse(['error' => 'Update database failed.'], Response::HTTP_BAD_GATEWAY);
        } catch (Exception|\Exception $exception) {
            return new JsonResponse([
                'error' => 'Update database failed: ' . $exception->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }
    }
}
