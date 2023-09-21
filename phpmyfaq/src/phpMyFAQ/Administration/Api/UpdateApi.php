<?php

/**
 * The main Admin API Updates class.
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
 * @since     2023-07-12
 */

namespace phpMyFAQ\Administration\Api;

use DateTime;
use DateTimeInterface;
use phpMyFAQ\Administration\Api;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Translation;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class UpdateApi
{
    public function __construct(private readonly Configuration $configuration)
    {
    }

    public function updates(): void
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
            $response->send();
        } catch (TransportExceptionInterface $e) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData($e->getMessage());
            $response->send();
        }
    }

    public function updateCheck(): void
    {
        $response = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();
        $dateTime = new DateTime();
        $dateLastChecked = $dateTime->format(DateTimeInterface::ATOM);
        try {
            $api = new Api($this->configuration);
            $versions = $api->getVersions();
            $response->setStatusCode(Response::HTTP_OK);
            $faqConfig->set('upgrade.dateLastChecked', $dateLastChecked);
            if (version_compare($versions['installed'], $versions['current'], '<')) {
                $response->setData(
                    [
                        'version' => $versions['current'],
                        'message' => Translation::get('currentVersion') . $versions['current'],
                        'dateLastChecked' => $dateLastChecked,
                    ]
                );
            } else {
                $response->setData(
                    [
                        'version' => 'current',
                        'message' => Translation::get('versionIsUpToDate'),
                        'dateLastChecked' => $dateLastChecked,
                    ]
                );
            }
            $response->send();
        } catch (Exception | TransportExceptionInterface | DecodingExceptionInterface $e) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => $e->getMessage()]);
            $response->send();
        }
    }
}
