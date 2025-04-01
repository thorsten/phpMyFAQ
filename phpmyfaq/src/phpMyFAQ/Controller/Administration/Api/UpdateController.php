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
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class UpdateController extends AbstractController
{
    /**
     * @throws Exception|\Exception
     */
    #[Route('admin/api/health-check')]
    public function healthCheck(): JsonResponse
    {
        $this->userIsAuthenticated();

        $dateTime = new DateTime();
        $dateLastChecked = $dateTime->format(DateTimeInterface::ATOM);
        $upgrade = $this->container->get('phpmyfaq.setup.upgrade');

        if (!$upgrade->isMaintenanceEnabled()) {
            return $this->json(
                [
                    'warning' => Translation::get('msgNotInMaintenanceMode'),
                    'dateLastChecked' => $dateLastChecked,
                ],
                Response::HTTP_CONFLICT
            );
        }

        try {
            $upgrade->checkFilesystem();
            return $this->json(
                [
                    'success' => Translation::get('healthCheckOkay'),
                    'dateLastChecked' => $dateLastChecked,
                ],
                Response::HTTP_OK
            );
        } catch (Exception $exception) {
            return $this->json(
                [
                    'error' => $exception->getMessage(),
                    'dateLastChecked' => $dateLastChecked,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('admin/api/versions')]
    public function versions(): JsonResponse
    {
        $this->userIsAuthenticated();

        try {
            $versions = HttpClient::create(['timeout' => 30])->request(
                'GET',
                'https://api.phpmyfaq.de/versions'
            );
            return $this->json($versions->getContent(), Response::HTTP_OK);
        } catch (
            TransportExceptionInterface |
            ClientExceptionInterface |
            ServerExceptionInterface |
            RedirectionExceptionInterface $exception
        ) {
            return $this->json($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route('admin/api/update-check')]
    public function updateCheck(): JsonResponse
    {
        $this->userIsAuthenticated();

        $dateTime = new DateTime();
        $dateLastChecked = $dateTime->format(DateTimeInterface::ATOM);
        $branch = $this->configuration->get('upgrade.releaseEnvironment');

        try {
            $versions = $this->container->get('phpmyfaq.admin.api')->getVersions();
            $this->configuration->set('upgrade.dateLastChecked', $dateLastChecked);

            if (version_compare($versions['installed'], $versions[$branch], '<')) {
                return $this->json(
                    [
                        'version' => $versions[$branch],
                        'message' => Translation::get('msgCurrentVersion') . $versions[$branch],
                        'dateLastChecked' => $dateLastChecked,
                    ],
                    Response::HTTP_OK
                );
            } else {
                return $this->json(
                    [
                        'version' => $versions['installed'],
                        'message' => Translation::get('versionIsUpToDate'),
                        'dateLastChecked' => $dateLastChecked,
                    ],
                    Response::HTTP_OK
                );
            }
        } catch (TransportExceptionInterface | DecodingExceptionInterface $e) {
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
    #[Route('admin/api/download-package')]
    public function downloadPackage(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $versionNumber = Filter::filterVar($request->get('versionNumber'), FILTER_SANITIZE_SPECIAL_CHARS);

        $upgrade = $this->container->get('phpmyfaq.setup.upgrade');
        $pathToPackage = $upgrade->downloadPackage($versionNumber);

        if ($pathToPackage === false) {
            return $this->json(['error' => Translation::get('downloadFailure')], Response::HTTP_BAD_GATEWAY);
        }

        if (!$upgrade->isNightly()) {
            $result = $upgrade->verifyPackage($pathToPackage, $versionNumber);
            if ($result === false) {
                return $this->json(['error' => Translation::get('verificationFailure')], Response::HTTP_BAD_GATEWAY);
            }
        }

        $this->configuration->set('upgrade.lastDownloadedPackage', urlencode($pathToPackage));

        return $this->json(['success' => Translation::get('downloadSuccessful')], Response::HTTP_OK);
    }

    #[Route('admin/api/extract-package')]
    public function extractPackage(): StreamedResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $upgrade = $this->container->get('phpmyfaq.setup.upgrade');
        $pathToPackage = urldecode((string) $this->configuration->get('upgrade.lastDownloadedPackage'));

        return new StreamedResponse(static function () use ($upgrade, $pathToPackage) {
            $progressCallback = static function ($progress) {
                echo json_encode(['progress' => $progress]) . "\n";
                ob_flush();
                flush();
            };
            if ($upgrade->extractPackage($pathToPackage, $progressCallback)) {
                echo json_encode(['message' => Translation::get('extractSuccessful')]);
            } else {
                echo json_encode(['message' => Translation::get('extractFailure')]);
            }
        });
    }

    #[Route('admin/api/create-temporary-backup')]
    public function createTemporaryBackup(): StreamedResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $upgrade = $this->container->get('phpmyfaq.setup.upgrade');
        $backupHash = md5(uniqid());

        return new StreamedResponse(static function () use ($upgrade, $backupHash) {
            $progressCallback = static function ($progress) {
                echo json_encode(['progress' => $progress]) . "\n";
                ob_flush();
                flush();
            };
            if ($upgrade->createTemporaryBackup($backupHash . '.zip', $progressCallback)) {
                echo json_encode(['message' => '✅ Backup successful']);
            } else {
                echo json_encode(['message' => 'Backup failed']);
            }
        });
    }

    #[Route('admin/api/install-package')]
    public function installPackage(): StreamedResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $upgrade = $this->container->get('phpmyfaq.setup.upgrade');
        $configurator = $this->container->get('phpmyfaq.setup.environment_configurator');
        return new StreamedResponse(static function () use ($upgrade, $configurator) {
            $progressCallback = static function ($progress) {
                echo json_encode(['progress' => $progress]) . "\n";
                ob_flush();
                flush();
            };
            if ($upgrade->installPackage($progressCallback) && $configurator->adjustRewriteBaseHtaccess()) {
                echo json_encode(['message' => '✅ Package successfully installed.']);
            } else {
                echo json_encode(['message' => 'Install package failed']);
            }
        });
    }

    #[Route('admin/api/update-database')]
    public function updateDatabase(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $update = $this->container->get('phpmyfaq.setup.update');
        $update->setVersion(System::getVersion());

        try {
            if ($update->applyUpdates()) {
                $this->configuration->set('main.maintenanceMode', 'false');
                return new JsonResponse(
                    ['success' => '✅ Database successfully updated.'],
                    Response::HTTP_OK
                );
            }

            return new JsonResponse(['error' => 'Update database failed.'], Response::HTTP_BAD_GATEWAY);
        } catch (Exception $exception) {
            return new JsonResponse(
                ['error' => 'Update database failed: ' . $exception->getMessage()],
                Response::HTTP_BAD_GATEWAY
            );
        }
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route('admin/api/cleanup')]
    public function cleanUp(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $upgrade = $this->container->get('phpmyfaq.setup.upgrade');
        $upgrade->cleanUp();

        return $this->json(['message' => '✅ Cleanup successful.'], Response::HTTP_OK);
    }
}
