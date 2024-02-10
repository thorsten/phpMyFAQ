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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

namespace phpMyFAQ\Controller\Administration;

use DateTime;
use DateTimeInterface;
use phpMyFAQ\Administration\Api;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\Setup\Upgrade;
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
    #[Route('admin/api/health-check')]
    public function healthCheck(): JsonResponse
    {
        $this->userIsAuthenticated();

        $jsonResponse = new JsonResponse();
        $dateTime = new DateTime();
        $configuration = Configuration::getConfigurationInstance();
        $dateLastChecked = $dateTime->format(DateTimeInterface::ATOM);
        $upgrade = new Upgrade(new System(), $configuration);

        try {
            $upgrade->checkFilesystem();
            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData(
                [
                    'message' => Translation::get('healthCheckOkay'),
                    'dateLastChecked' => $dateLastChecked,
                ]
            );
        } catch (Exception $exception) {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(
                [
                    'message' => $exception->getMessage(),
                    'dateLastChecked' => $dateLastChecked,
                ]
            );
        }

        return $jsonResponse;
    }

    #[Route('admin/api/versions')]
    public function versions(): JsonResponse
    {
        $this->userIsAuthenticated();

        $httpClient = HttpClient::create();
        $jsonResponse = new JsonResponse();
        try {
            $versions = $httpClient->request(
                'GET',
                'https://api.phpmyfaq.de/versions'
            );
            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setContent($versions->getContent());
        } catch (
            TransportExceptionInterface |
            ClientExceptionInterface |
            ServerExceptionInterface |
            RedirectionExceptionInterface $exception
        ) {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData($exception->getMessage());
        }

        return $jsonResponse;
    }

    #[Route('admin/api/update-check')]
    public function updateCheck(): JsonResponse
    {
        $this->userIsAuthenticated();

        $jsonResponse = new JsonResponse();
        $dateTime = new DateTime();
        $configuration = Configuration::getConfigurationInstance();
        $dateLastChecked = $dateTime->format(DateTimeInterface::ATOM);
        $branch = $configuration->get('upgrade.releaseEnvironment');

        try {
            $api = new Api($configuration);
            $versions = $api->getVersions();
            $configuration->set('upgrade.dateLastChecked', $dateLastChecked);

            if (version_compare($versions['installed'], $versions[$branch], '<')) {
                $jsonResponse->setData(
                    [
                        'version' => $versions[$branch],
                        'message' => Translation::get('currentVersion') . $versions[$branch],
                        'dateLastChecked' => $dateLastChecked,
                    ]
                );
            } else {
                $jsonResponse->setData(
                    [
                        'version' => $versions['installed'],
                        'message' => Translation::get('versionIsUpToDate'),
                        'dateLastChecked' => $dateLastChecked,
                    ]
                );
            }

            $jsonResponse->setStatusCode(Response::HTTP_OK);
        } catch (Exception | TransportExceptionInterface | DecodingExceptionInterface $e) {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => $e->getMessage()]);
        }

        return $jsonResponse;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws Exception
     */
    #[Route('admin/api/download-package')]
    public function downloadPackage(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $versionNumber = Filter::filterVar($request->get('versionNumber'), FILTER_SANITIZE_SPECIAL_CHARS);

        $upgrade = new Upgrade(new System(), $configuration);

        $pathToPackage = $upgrade->downloadPackage($versionNumber);

        if ($pathToPackage === false) {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_GATEWAY);
            $jsonResponse->setData(['error' => Translation::get('downloadFailure')]);
            return $jsonResponse;
        }

        if (!$upgrade->isNightly()) {
            $result = $upgrade->verifyPackage($pathToPackage, $versionNumber);
            if ($result === false) {
                $jsonResponse->setStatusCode(Response::HTTP_BAD_GATEWAY);
                $jsonResponse->setData(['error' => Translation::get('verificationFailure')]);
                return $jsonResponse;
            }
        }

        $configuration->set('upgrade.lastDownloadedPackage', urlencode($pathToPackage));

        $jsonResponse->setStatusCode(Response::HTTP_OK);
        $jsonResponse->setData(['success' => Translation::get('downloadSuccessful')]);
        return $jsonResponse;
    }

    #[Route('admin/api/extract-package')]
    public function extractPackage(): StreamedResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $configuration = Configuration::getConfigurationInstance();
        $upgrade = new Upgrade(new System(), $configuration);

        $pathToPackage = urldecode((string) $configuration->get('upgrade.lastDownloadedPackage'));

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

        $configuration = Configuration::getConfigurationInstance();
        $upgrade = new Upgrade(new System(), $configuration);

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

        $configuration = Configuration::getConfigurationInstance();
        $upgrade = new Upgrade(new System(), $configuration);

        return new StreamedResponse(static function () use ($upgrade) {
            $progressCallback = static function ($progress) {
                echo json_encode(['progress' => $progress]) . "\n";
                ob_flush();
                flush();
            };
            if ($upgrade->installPackage($progressCallback)) {
                echo json_encode(['message' => '✅ Package successfully installed.']);
            } else {
                echo json_encode(['message' => 'Install package failed']);
            }
        });
    }

    #[Route('admin/api/update-database')]
    public function updateDatabase(): StreamedResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $configuration = Configuration::getConfigurationInstance();
        $update = new Update(new System(), $configuration);
        $update->setVersion(System::getVersion());

        return new StreamedResponse(static function () use ($update) {
            $progressCallback = static function ($progress) {
                echo json_encode(['progress' => $progress]) . "\n";
                ob_flush();
                flush();
            };
            try {
                if ($update->applyUpdates($progressCallback)) {
                    echo json_encode(['message' => '✅ Database successfully updated.']);
                }
            } catch (Exception $exception) {
                echo json_encode(['message' => 'Update database failed: ' . $exception->getMessage()]);
            }
        });
    }

    #[Route('admin/api/cleanup')]
    public function cleanUp(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $upgrade = new Upgrade(new System(), $configuration);

        try {
            $upgrade->cleanUp();
            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData(['message' => '✅ Cleanup successful.']);
        } catch (Exception $exception) {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['message' => 'Cleanup failed: ' . $exception->getMessage()]);
        }

        return $jsonResponse;
    }
}
