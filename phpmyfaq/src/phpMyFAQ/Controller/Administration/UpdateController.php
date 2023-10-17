<?php

/**
 * The Update Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

namespace phpMyFAQ\Controller\Administration;

use DateTime;
use DateTimeInterface;
use phpMyFAQ\Administration\Api;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
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

class UpdateController
{
    #[Route('admin/api/health-check')]
    public function healthCheck(): JsonResponse
    {
        $response = new JsonResponse();
        $dateTime = new DateTime();
        $configuration = Configuration::getConfigurationInstance();
        $dateLastChecked = $dateTime->format(DateTimeInterface::ATOM);
        $upgrade = new Upgrade(new System(), $configuration);

        try {
            $upgrade->checkFilesystem();
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(
                [
                    'message' => Translation::get('healthCheckOkay'),
                    'dateLastChecked' => $dateLastChecked,
                ]
            );
        } catch (Exception $exception) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(
                [
                    'message' => $exception->getMessage(),
                    'dateLastChecked' => $dateLastChecked,
                ]
            );
        }

        return $response;
    }

    #[Route('admin/api/versions')]
    public function versions(): JsonResponse
    {
        $client = HttpClient::create();
        $response = new JsonResponse();
        try {
            $versions = $client->request(
                'GET',
                'https://api.phpmyfaq.de/versions'
            );
            $response->setStatusCode(Response::HTTP_OK);
            $response->setContent($versions->getContent());
        } catch (
            TransportExceptionInterface |
            ClientExceptionInterface |
            ServerExceptionInterface |
            RedirectionExceptionInterface $exception
        ) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData($exception->getMessage());
        }

        return $response;
    }

    #[Route('admin/api/update-check')]
    public function updateCheck(): JsonResponse
    {
        $response = new JsonResponse();
        $dateTime = new DateTime();
        $configuration = Configuration::getConfigurationInstance();
        $dateLastChecked = $dateTime->format(DateTimeInterface::ATOM);
        $branch = $configuration->get('upgrade.releaseEnvironment');

        try {
            $api = new Api($configuration);
            $versions = $api->getVersions();
            $configuration->set('upgrade.dateLastChecked', $dateLastChecked);

            if (version_compare($versions['installed'], $versions[$branch], '<')) {
                $response->setData(
                    [
                        'version' => $versions[$branch],
                        'message' => Translation::get('currentVersion') . $versions[$branch],
                        'dateLastChecked' => $dateLastChecked,
                    ]
                );
            } else {
                $response->setData(
                    [
                        'version' => $versions['installed'],
                        'message' => Translation::get('versionIsUpToDate'),
                        'dateLastChecked' => $dateLastChecked,
                    ]
                );
            }

            $response->setStatusCode(Response::HTTP_OK);
        } catch (Exception | TransportExceptionInterface | DecodingExceptionInterface $e) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    #[Route('admin/api/download-package')]
    public function downloadPackage(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $versionNumber = Filter::filterVar($request->get('versionNumber'), FILTER_SANITIZE_SPECIAL_CHARS);

        $upgrade = new Upgrade(new System(), $configuration);

        $pathToPackage = $upgrade->downloadPackage($versionNumber);

        if ($pathToPackage === false) {
            $response->setStatusCode(Response::HTTP_BAD_GATEWAY);
            $response->setData(['error' => Translation::get('downloadFailure')]);
            return $response;
        }

        if (!$upgrade->isNightly()) {
            $result = $upgrade->verifyPackage($pathToPackage, $versionNumber);
            if ($result === false) {
                $response->setStatusCode(Response::HTTP_BAD_GATEWAY);
                $response->setData(['error' => Translation::get('verificationFailure')]);
                return $response;
            }
        }

        $configuration->set('upgrade.lastDownloadedPackage', urlencode($pathToPackage));

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData(['success' => Translation::get('downloadSuccessful')]);

        return $response;
    }

    #[Route('admin/api/extract-package')]
    public function extractPackage(): StreamedResponse
    {
        $configuration = Configuration::getConfigurationInstance();
        $upgrade = new Upgrade(new System(), $configuration);

        $pathToPackage = urldecode($configuration->get('upgrade.lastDownloadedPackage'));

        return new StreamedResponse(function () use ($upgrade, $pathToPackage) {
            $progressCallback = function ($progress) {
                echo json_encode(['progress' => $progress]) . "\n";
                ob_flush();
                flush();
            };

            if ($upgrade->extractPackage($pathToPackage, $progressCallback)) {
                echo json_encode(['message' => '✅ Package successfully extracted.']);
            } else {
                echo json_encode(['message' => 'Extract package failed']);
            }
        });
    }

    #[Route('admin/api/create-temporary-backup')]
    public function createTemporaryBackup(): StreamedResponse
    {
        $configuration = Configuration::getConfigurationInstance();
        $upgrade = new Upgrade(new System(), $configuration);

        $backupHash = md5(uniqid());

        return new StreamedResponse(function () use ($upgrade, $backupHash) {
            $progressCallback = function ($progress) {
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
        $configuration = Configuration::getConfigurationInstance();
        $upgrade = new Upgrade(new System(), $configuration);

        return new StreamedResponse(function () use ($upgrade) {
            $progressCallback = function ($progress) {
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
        $configuration = Configuration::getConfigurationInstance();
        $update = new Update(new System(), $configuration);
        $update->setVersion(System::getVersion());

        return new StreamedResponse(function () use ($update) {
            $progressCallback = function ($progress) {
                echo json_encode(['progress' => $progress]) . "\n";
                ob_flush();
                flush();
            };

            try {
                if ($update->applyUpdates($progressCallback)) {
                    echo json_encode(['message' => '✅ Database successfully updated.']);
                }
            } catch (Exception $e) {
                echo json_encode(['message' => 'Update database failed: ' . $e->getMessage()]);
            }
        });
    }
}
