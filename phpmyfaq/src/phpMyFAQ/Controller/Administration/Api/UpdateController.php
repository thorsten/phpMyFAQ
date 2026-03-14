<?php

/**
 * The Admin Update Controller
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
 * @since     2023-07-29
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use DateTime;
use DateTimeInterface;
use phpMyFAQ\Administration\Api;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Setup\EnvironmentConfigurator;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\Setup\Upgrade;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class UpdateController extends AbstractController
{
    public function __construct(
        private readonly Upgrade $upgrade,
        private readonly Api $adminApi,
        private readonly Update $update,
        private readonly EnvironmentConfigurator $configurator,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route(path: 'health-check', name: 'admin.api.health-check', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        $this->userIsAuthenticated();

        $dateTime = new DateTime();
        $dateLastChecked = $dateTime->format(DateTimeInterface::ATOM);

        if (!$this->upgrade->isMaintenanceEnabled()) {
            return $this->json([
                'warning' => Translation::get(key: 'msgNotInMaintenanceMode'),
                'dateLastChecked' => $dateLastChecked,
            ], Response::HTTP_CONFLICT);
        }

        try {
            $this->upgrade->checkFilesystem();
            return $this->json([
                'success' => Translation::get(key: 'healthCheckOkay'),
                'dateLastChecked' => $dateLastChecked,
            ], Response::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json([
                'error' => $exception->getMessage(),
                'dateLastChecked' => $dateLastChecked,
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: 'versions', name: 'admin.api.versions', methods: ['GET'])]
    public function versions(): JsonResponse
    {
        $this->userIsAuthenticated();

        try {
            $versions = HttpClient::create(['timeout' => 30])->request('GET', 'https://api.phpmyfaq.de/versions');
            return $this->json($versions->getContent(), Response::HTTP_OK);
        } catch (
            TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface|RedirectionExceptionInterface $exception
        ) {
            return $this->json($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route(path: 'update-check', name: 'admin.api.update-check', methods: ['POST'])]
    public function updateCheck(): JsonResponse
    {
        $this->userIsAuthenticated();

        $dateTime = new DateTime();
        $dateLastChecked = $dateTime->format(DateTimeInterface::ATOM);
        $branch = $this->configuration->get(item: 'upgrade.releaseEnvironment');

        try {
            $versions = $this->adminApi->getVersions();
            $this->configuration->set('upgrade.dateLastChecked', $dateLastChecked);

            $installed = $versions['installed'];
            $available = $versions[$branch];

            if (version_compare($installed, $available, operator: '<')) {
                return $this->json([
                    'version' => $available,
                    'message' => Translation::get(key: 'msgCurrentVersion') . $available,
                    'dateLastChecked' => $dateLastChecked,
                ], Response::HTTP_OK);
            }

            if ($branch !== 'nightly' && version_compare($installed, $available, operator: '>')) {
                return $this->json([
                    'version' => $available,
                    'message' => Translation::get(key: 'msgInstalledNewerThanAvailable'),
                    'dateLastChecked' => $dateLastChecked,
                ], Response::HTTP_CONFLICT);
            }

            return $this->json([
                'version' => $installed,
                'message' => Translation::get(key: 'versionIsUpToDate'),
                'dateLastChecked' => $dateLastChecked,
            ], Response::HTTP_OK);
        } catch (TransportExceptionInterface|DecodingExceptionInterface $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws Exception|\Exception
     */
    #[Route(path: 'download-package/{versionNumber}', name: 'admin.api.download-package', methods: ['POST'])]
    public function downloadPackage(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $versionNumber = Filter::filterVar($request->attributes->get('versionNumber'), FILTER_SANITIZE_SPECIAL_CHARS);

        try {
            $pathToPackage = $this->upgrade->downloadPackage($versionNumber);
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        if ($pathToPackage === false) {
            return $this->json(['error' => Translation::get(key: 'downloadFailure')], Response::HTTP_BAD_GATEWAY);
        }

        if (!$this->upgrade->isNightly()) {
            $result = $this->upgrade->verifyPackage($pathToPackage, $versionNumber);
            if ($result === false) {
                return $this->json(['error' => Translation::get(
                    key: 'verificationFailure',
                )], Response::HTTP_BAD_GATEWAY);
            }
        }

        $this->configuration->set('upgrade.lastDownloadedPackage', urlencode($pathToPackage));

        return $this->json(['success' => Translation::get(key: 'downloadSuccessful')], Response::HTTP_OK);
    }

    #[Route(path: 'extract-package', name: 'admin.api.extract-package', methods: ['POST'])]
    public function extractPackage(): StreamedResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $pathToPackage = urldecode((string) $this->configuration->get(item: 'upgrade.lastDownloadedPackage'));

        return new StreamedResponse(function () use ($pathToPackage): void {
            $progressCallback = static function ($progress): void {
                echo json_encode(['progress' => $progress]) . "\n";
                ob_flush();
                flush();
            };
            $message = $this->upgrade->extractPackage($pathToPackage, $progressCallback)
                ? Translation::get(key: 'extractSuccessful')
                : Translation::get(key: 'extractFailure');
            echo json_encode(['message' => $message]);
        });
    }

    #[Route(path: 'create-temporary-backup', name: 'admin.api.create-temporary-backup', methods: ['POST'])]
    public function createTemporaryBackup(): StreamedResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $backupHash = md5(uniqid());

        return new StreamedResponse(function () use ($backupHash): void {
            $progressCallback = static function ($progress): void {
                echo json_encode(['progress' => $progress]) . "\n";
                ob_flush();
                flush();
            };
            $message = $this->upgrade->createTemporaryBackup($backupHash . '.zip', $progressCallback)
                ? 'Backup successful'
                : 'Backup failed';
            echo json_encode(['message' => $message]);
        });
    }

    #[Route(path: 'install-package', name: 'admin.api.install-package', methods: ['POST'])]
    public function installPackage(): StreamedResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        return new StreamedResponse(function (): void {
            $progressCallback = static function ($progress): void {
                echo json_encode(['progress' => $progress]) . "\n";
                ob_flush();
                flush();
            };
            $message = $this->upgrade->installPackage($progressCallback)
            && $this->configurator->adjustRewriteBaseHtaccess()
                ? 'Package successfully installed.'
                : 'Install package failed';
            echo json_encode(['message' => $message]);
        });
    }

    #[Route(path: 'update-database', name: 'admin.api.update-database', methods: ['POST'])]
    public function updateDatabase(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $this->update->version = $this->configuration->get('main.currentVersion');

        try {
            if ($this->update->applyUpdates()) {
                $this->configuration->set('main.maintenanceMode', 'false');
                return new JsonResponse(['success' => 'Database successfully updated.'], Response::HTTP_OK);
            }

            $this->configuration->set('main.maintenanceMode', 'false');
            return new JsonResponse(['error' => 'Update database failed.'], Response::HTTP_BAD_GATEWAY);
        } catch (Exception|\Exception $exception) {
            $this->configuration->set('main.maintenanceMode', 'false');
            return new JsonResponse([
                'error' => 'Update database failed: ' . $exception->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route(path: 'cleanup', name: 'admin.api.cleanup', methods: ['POST'])]
    public function cleanUp(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $this->upgrade->cleanUp();

        return $this->json(['message' => 'Cleanup successful.'], Response::HTTP_OK);
    }
}
