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

use Closure;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\Setup\UpdateToken;
use phpMyFAQ\System;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SetupController extends AbstractController
{
    public const string TOKEN_HEADER = 'x-pmf-update-token';

    /**
     * @param ?Closure(System, \phpMyFAQ\Configuration): Update $updateFactory
     */
    public function __construct(
        private readonly ?Closure $updateFactory = null,
    ) {
        parent::__construct();
    }

    /**
     * Setup endpoints cannot rely on the regular login, so we override the security
     * check from AbstractController. Every endpoint checks for itself that the caller
     * is allowed to run the update, see isAuthorizedForUpdate().
     */
    protected function isSecured(): void
    {
        // No-op: authorization is handled per endpoint by isAuthorizedForUpdate()
    }

    #[Route(path: 'setup/check', name: 'api.private.setup.check', methods: ['POST'])]
    public function check(Request $request): JsonResponse
    {
        $unauthorized = $this->denyUnauthorizedRequest($request);
        if ($unauthorized instanceof JsonResponse) {
            return $unauthorized;
        }

        if (trim($request->getContent()) === '') {
            return $this->json(['message' => 'No version given.'], Response::HTTP_BAD_REQUEST);
        }

        $installedVersion = Filter::filterVar($request->getContent(), FILTER_SANITIZE_SPECIAL_CHARS, '');

        $update = $this->createUpdate($installedVersion);

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

    #[Route(path: 'setup/backup', name: 'api.private.setup.backup', methods: ['POST'])]
    public function backup(Request $request): JsonResponse
    {
        $unauthorized = $this->denyUnauthorizedRequest($request);
        if ($unauthorized instanceof JsonResponse) {
            return $unauthorized;
        }

        if (trim($request->getContent()) === '') {
            return $this->json(['message' => 'No version given.'], Response::HTTP_BAD_REQUEST);
        }

        $update = $this->createUpdate($this->configuration->getVersion());

        if (!$update->checkMaintenanceMode()) {
            return $this->json([
                'message' => 'Maintenance mode is not enabled. Please enable it first.',
            ], Response::HTTP_CONFLICT);
        }

        $installedVersion = Filter::filterVar($request->getContent(), FILTER_SANITIZE_SPECIAL_CHARS, '');

        $configPath = (string) PMF_ROOT_DIR . '/content/core/config';
        if (version_compare(version1: $installedVersion, version2: '4.0.0-alpha', operator: '<')) {
            $configPath = (string) PMF_ROOT_DIR . '/config';
        }

        try {
            $pathToBackup = $update->createConfigBackup($configPath);
        } catch (Exception $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }

        // The archive contains the database credentials, so we only report its name
        // and never a URL that could be used to download it.
        return $this->json([
            'message' => 'Backup successful',
            'backupFile' => basename($pathToBackup),
        ], Response::HTTP_OK);
    }

    #[Route(path: 'setup/update-database', name: 'api.private.setup.update-database', methods: ['POST'])]
    public function updateDatabase(Request $request): JsonResponse
    {
        $unauthorized = $this->denyUnauthorizedRequest($request);
        if ($unauthorized instanceof JsonResponse) {
            return $unauthorized;
        }

        if (trim($request->getContent()) === '') {
            return $this->json(['message' => 'No version given.'], Response::HTTP_BAD_REQUEST);
        }

        $installedVersion = Filter::filterVar($request->getContent(), FILTER_SANITIZE_SPECIAL_CHARS, '');

        $update = $this->createUpdate($installedVersion);

        if (!$update->checkMaintenanceMode()) {
            return $this->json([
                'message' => 'Maintenance mode is not enabled. Please enable it first.',
            ], Response::HTTP_CONFLICT);
        }

        try {
            if ($update->applyUpdates()) {
                $this->configuration->set(key: 'main.maintenanceMode', value: 'false');
                // The update is done, so the token must not authorize another run
                $this->getUpdateToken()->delete();
                return new JsonResponse(['success' => 'Database successfully updated.'], Response::HTTP_OK);
            }

            return new JsonResponse(['error' => 'Update database failed.'], Response::HTTP_BAD_GATEWAY);
        } catch (Exception|\Exception $exception) {
            return new JsonResponse([
                'error' => 'Update database failed: ' . $exception->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }
    }

    private function createUpdate(string $version): Update
    {
        $system = new System();
        $update = ($this->updateFactory
        ?? static fn(System $system, \phpMyFAQ\Configuration $configuration): Update => new Update(
            $system,
            $configuration,
        ))($system, $this->configuration);
        $update->version = $version;

        return $update;
    }

    /**
     * Returns a 401 response if the caller is not allowed to run the update, otherwise null.
     */
    private function denyUnauthorizedRequest(Request $request): ?JsonResponse
    {
        if ($this->isAuthorizedForUpdate($request)) {
            return null;
        }

        return $this->json([
            'message' =>
                'You are not allowed to run the update. Please log in as an administrator or provide the '
                    . 'update token from '
                    . UpdateToken::TOKEN_FILENAME
                    . ' in the configuration directory.',
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * The update may run either for a logged-in administrator, or for someone who can
     * prove access to the file system of the server by sending the update token. The
     * second way is needed because the login can be broken until the migration has run.
     */
    private function isAuthorizedForUpdate(Request $request): bool
    {
        if ($this->isAuthenticatedAdministrator()) {
            return true;
        }

        return $this->getUpdateToken()->isValid($request->headers->get(self::TOKEN_HEADER));
    }

    private function isAuthenticatedAdministrator(): bool
    {
        try {
            if (!$this->currentUser->isLoggedIn()) {
                return false;
            }

            if ($this->currentUser->isSuperAdmin()) {
                return true;
            }

            return $this->currentUser->perm->hasPermission(
                $this->currentUser->getUserId(),
                PermissionType::CONFIGURATION_EDIT->value,
            );
        } catch (\Throwable) {
            // A database that is not migrated yet can break the permission lookup,
            // in that case the update token is the only way in.
            return false;
        }
    }

    private function getUpdateToken(): UpdateToken
    {
        return new UpdateToken((string) PMF_CONFIG_DIR);
    }
}
