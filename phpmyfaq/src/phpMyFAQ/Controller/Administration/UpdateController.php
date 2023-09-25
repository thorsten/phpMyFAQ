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
use phpMyFAQ\Setup\Upgrade;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class UpdateController
{
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
        } catch (TransportExceptionInterface $e) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData($e->getMessage());
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

    #[Route('admin/api/download-package')]
    public function downloadPackage(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $versionNumber = Filter::filterVar($request->get('versionNumber'), FILTER_SANITIZE_SPECIAL_CHARS);

        $upgrade = new Upgrade(new System(), Configuration::getConfigurationInstance());
        $upgrade->setIsNightly(true);
        $result = $upgrade->downloadPackage($versionNumber);

        if ($result !== false) {
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['success' => $result]);
        } else {
            $response->setStatusCode(Response::HTTP_BAD_GATEWAY);
        }

        return $response;
    }
}
