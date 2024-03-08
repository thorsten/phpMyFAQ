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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-17
 */

namespace phpMyFAQ\Controller\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Filter;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\System;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SetupController extends AbstractController
{
    public function check(Request $request): JsonResponse
    {
        if (empty($request->getContent())) {
            return $this->json(['message' => 'No version given.'], Response::HTTP_BAD_REQUEST);
        }

        $configuration = Configuration::getConfigurationInstance();

        $installedVersion = Filter::filterVar($request->getContent(), FILTER_SANITIZE_SPECIAL_CHARS);

        $update = new Update(new System(), $configuration);
        $update->setVersion($installedVersion);

        if (!$update->checkMaintenanceMode()) {
            return $this->json(
                ['message' => 'Maintenance mode is not enabled. Please enable it first.'],
                Response::HTTP_CONFLICT
            );
        }

        if (!$update->checkMinimumUpdateVersion($installedVersion)) {
            $message = sprintf(
                'Your installed version is phpMyFAQ %s. Please update to the latest phpMyFAQ 3.0 version first.',
                $installedVersion
            );
            return $this->json(['message' => $message], Response::HTTP_CONFLICT);
        }

        // Check hard requirements
        try {
            $update->checkPreUpgrade(Database::getType());
        } catch (Exception $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['message' => '✅ Installation check successful'], Response::HTTP_OK);
    }

    public function backup(Request $request): JsonResponse
    {
        if (empty($request->getContent())) {
            return $this->json(['message' => 'No version given.'], Response::HTTP_BAD_REQUEST);
        }

        $update = new Update(new System(), Configuration::getConfigurationInstance());
        $update->setVersion(System::getVersion());

        $installedVersion = Filter::filterVar($request->getContent(), FILTER_SANITIZE_SPECIAL_CHARS);

        $configPath = PMF_ROOT_DIR . DIRECTORY_SEPARATOR . 'content';
        if (!version_compare($installedVersion, '4.0.0-dev') < 0) {
            $configPath = PMF_ROOT_DIR . DIRECTORY_SEPARATOR . 'config';
        }

        try {
            $pathToBackup = $update->createConfigBackup($configPath);
        } catch (Exception $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }

        return $this->json(['message' => '✅ Backup successful', 'backupFile' => $pathToBackup], Response::HTTP_OK);
    }

    public function updateDatabase(Request $request): StreamedResponse|JsonResponse
    {
        $configuration = Configuration::getConfigurationInstance();

        if (empty($request->getContent())) {
            $response = new JsonResponse();
            $response->setStatusCode(Response::HTTP_CONFLICT);
            $response->setData(['message' => 'No version given.']);
            return $response;
        }

        $installedVersion = Filter::filterVar($request->getContent(), FILTER_SANITIZE_SPECIAL_CHARS);

        $update = new Update(new System(), $configuration);
        $update->setVersion($installedVersion);

        $response = new StreamedResponse();
        $response->setCallback(static function () use ($update, $configuration) {
            $progressCallback = static function ($progress) {
                echo json_encode(['progress' => $progress]) . "\n";
                ob_flush();
                flush();
            };
            try {
                if ($update->applyUpdates($progressCallback)) {
                    $configuration->set('main.maintenanceMode', 'false');
                    echo json_encode(['success' => '✅ Database successfully updated.']);
                }
            } catch (Exception $exception) {
                echo json_encode(['error' => 'Update database failed: ' . $exception->getMessage()]);
            }
        });
        return $response;
    }
}
