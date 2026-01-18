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
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
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
    /**
     * @throws Exception|\Exception
     */
    #[Route(path: 'admin/api/health-check', name: 'admin.api.health-check', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        $this->userIsAuthenticated();

        $dateTime = new DateTime();
        $dateLastChecked = $dateTime->format(DateTimeInterface::ATOM);
        $upgrade = $this->container->get(id: 'phpmyfaq.setup.upgrade');

        if (!$upgrade->isMaintenanceEnabled()) {
            return $this->json([
                'warning' => Translation::get(key: 'msgNotInMaintenanceMode'),
                'dateLastChecked' => $dateLastChecked,
            ], Response::HTTP_CONFLICT);
        }

        try {
            $upgrade->checkFilesystem();
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

    #[Route(path: 'admin/api/versions', name: 'admin.api.versions', methods: ['GET'])]
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
    #[Route(path: 'admin/api/update-check', name: 'admin.api.update-check', methods: ['GET'])]
    public function updateCheck(): JsonResponse
    {
        $this->userIsAuthenticated();

        $dateTime = new DateTime();
        $dateLastChecked = $dateTime->format(DateTimeInterface::ATOM);
        $branch = $this->configuration->get(item: 'upgrade.releaseEnvironment');

        try {
            $versions = $this->container->get(id: 'phpmyfaq.admin.api')->getVersions();
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
    #[Route(path: 'admin/api/download-package', name: 'admin.api.download-package', methods: ['POST'])]
    public function downloadPackage(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $versionNumber = Filter::filterVar($request->attributes->get('versionNumber'), FILTER_SANITIZE_SPECIAL_CHARS);

        $upgrade = $this->container->get(id: 'phpmyfaq.setup.upgrade');
        try {
            $pathToPackage = $upgrade->downloadPackage($versionNumber);
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        if ($pathToPackage === false) {
            return $this->json(['error' => Translation::get(key: 'downloadFailure')], Response::HTTP_BAD_GATEWAY);
        }

        if (!$upgrade->isNightly()) {
            $result = $upgrade->verifyPackage($pathToPackage, $versionNumber);
            if ($result === false) {
                return $this->json(['error' => Translation::get(
                    key: 'verificationFailure',
                )], Response::HTTP_BAD_GATEWAY);
            }
        }

        $this->configuration->set('upgrade.lastDownloadedPackage', urlencode($pathToPackage));

        return $this->json(['success' => Translation::get(key: 'downloadSuccessful')], Response::HTTP_OK);
    }

    #[Route(path: 'admin/api/extract-package', name: 'admin.api.extract-package', methods: ['POST'])]
    public function extractPackage(): StreamedResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $upgrade = $this->container->get(id: 'phpmyfaq.setup.upgrade');
        $pathToPackage = urldecode((string) $this->configuration->get(item: 'upgrade.lastDownloadedPackage'));

        return new StreamedResponse(static function () use ($upgrade, $pathToPackage): void {
            $progressCallback = static function ($progress): void {
                echo json_encode(['progress' => $progress]) . "\n";
                ob_flush();
                flush();
            };
            $message = $upgrade->extractPackage($pathToPackage, $progressCallback)
                ? Translation::get(key: 'extractSuccessful')
                : Translation::get(key: 'extractFailure');
            echo json_encode(['message' => $message]);
        });
    }

    #[Route(path: 'admin/api/create-temporary-backup', name: 'admin.api.create-temporary-backup', methods: ['POST'])]
    public function createTemporaryBackup(): StreamedResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $upgrade = $this->container->get(id: 'phpmyfaq.setup.upgrade');
        $backupHash = md5(uniqid());

        return new StreamedResponse(static function () use ($upgrade, $backupHash): void {
            $progressCallback = static function ($progress): void {
                echo json_encode(['progress' => $progress]) . "\n";
                ob_flush();
                flush();
            };
            $message = $upgrade->createTemporaryBackup($backupHash . '.zip', $progressCallback)
                ? 'Backup successful'
                : 'Backup failed';
            echo json_encode(['message' => $message]);
        });
    }

    #[Route(path: 'admin/api/install-package', name: 'admin.api.install-package', methods: ['POST'])]
    public function installPackage(): StreamedResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $upgrade = $this->container->get(id: 'phpmyfaq.setup.upgrade');
        $configurator = $this->container->get(id: 'phpmyfaq.setup.environment_configurator');
        return new StreamedResponse(static function () use ($upgrade, $configurator): void {
            $progressCallback = static function ($progress): void {
                echo json_encode(['progress' => $progress]) . "\n";
                ob_flush();
                flush();
            };
            $message = $upgrade->installPackage($progressCallback) && $configurator->adjustRewriteBaseHtaccess()
                ? 'Package successfully installed.'
                : 'Install package failed';
            echo json_encode(['message' => $message]);
        });
    }

    #[Route(path: 'admin/api/update-database', name: 'admin.api.update-database', methods: ['POST'])]
    public function updateDatabase(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $update = $this->container->get(id: 'phpmyfaq.setup.update');
        $update->setVersion(System::getVersion());

        try {
            if ($update->applyUpdates()) {
                $this->configuration->set('main.maintenanceMode', 'false');
                return new JsonResponse(['success' => 'Database successfully updated.'], Response::HTTP_OK);
            }

            return new JsonResponse(['error' => 'Update database failed.'], Response::HTTP_BAD_GATEWAY);
        } catch (Exception $exception) {
            return new JsonResponse([
                'error' => 'Update database failed: ' . $exception->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route(path: 'admin/api/cleanup', name: 'admin.api.cleanup', methods: ['POST'])]
    public function cleanUp(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $upgrade = $this->container->get(id: 'phpmyfaq.setup.upgrade');
        $upgrade->cleanUp();

        return $this->json(['message' => 'Cleanup successful.'], Response::HTTP_OK);
    }
}
