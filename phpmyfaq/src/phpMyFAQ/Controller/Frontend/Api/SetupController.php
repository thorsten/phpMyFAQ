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
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-17
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\System;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SetupController extends AbstractController
{
    /**
     * Run pre-upgrade checks for the installed phpMyFAQ version provided in the request body.
     *
     * Validates the request content as the installed version, ensures maintenance mode is enabled,
     * verifies the installed version meets the minimum upgradable version, and checks hard requirements.
     *
     * @param Request $request The HTTP request whose body must contain the installed phpMyFAQ version string.
     * @return JsonResponse A JSON response containing a `message` and an HTTP status code:
     *                      - 200: Installation check successful.
     *                      - 400: No version given or pre-upgrade requirement failure (exception message).
     *                      - 409: Maintenance mode not enabled or installed version below minimum required (advice message).
     */
    #[Route(path: 'setup/check', name: 'api.private.setup.check', methods: ['POST'])]
    public function check(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        if (trim($request->getContent()) === '') {
            return $this->json(['message' => 'No version given.'], Response::HTTP_BAD_REQUEST);
        }

        $installedVersion = Filter::filterVar($request->getContent(), FILTER_SANITIZE_SPECIAL_CHARS);

        $update = new Update(new System(), $this->configuration);
        $update->version = $installedVersion;

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

    /**
     * Create a configuration backup for the provided installed version.
     *
     * Validates the request body for an installed version, determines the configuration
     * directory based on that version, and attempts to create a backup file.
     * Returns a JSON response with HTTP 200 and `backupFile` on success,
     * HTTP 400 if no version is provided, or HTTP 502 if the backup fails.
     *
     * @param Request $request HTTP request whose body contains the installed version string.
     * @return JsonResponse JSON containing a message and, on success, a `backupFile` path.
     */
    #[Route(path: 'setup/backup', name: 'api.private.setup.backup', methods: ['POST'])]
    public function backup(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        if (trim($request->getContent()) === '') {
            return $this->json(['message' => 'No version given.'], Response::HTTP_BAD_REQUEST);
        }

        $update = new Update(new System(), $this->configuration);
        $update->version = $this->configuration->getVersion();

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

    /**
     * Performs the database upgrade for a given installed phpMyFAQ version and updates maintenance mode.
     *
     * Attempts to apply database updates for the installed version provided in the request body. On success, disables maintenance mode and returns a success message; on failure returns an error message describing the problem.
     *
     * @param Request $request HTTP request whose body must contain the installed phpMyFAQ version.
     * @return JsonResponse JSON with `success` on success, or `error` with an explanatory message on failure.
     */
    #[Route(path: 'setup/update-database', name: 'api.private.setup.update-database', methods: ['POST'])]
    public function updateDatabase(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        if (trim($request->getContent()) === '') {
            return $this->json(['message' => 'No version given.'], Response::HTTP_BAD_REQUEST);
        }

        $installedVersion = Filter::filterVar($request->getContent(), FILTER_SANITIZE_SPECIAL_CHARS);

        $update = new Update(new System(), $this->configuration);
        $update->version = $installedVersion;

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