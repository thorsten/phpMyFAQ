<?php

/**
 * API handler class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-03-27
 */

namespace phpMyFAQ;

use Exception;
use JsonException;
use stdClass;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class Api
 *
 * @package phpMyFAQ
 */
class Api
{
    private string $apiUrl = 'https://api.phpmyfaq.de/';

    private ?string $remoteHashes = null;

    private readonly HttpClientInterface $client;

    /**
     * Api constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
        $this->client = HttpClient::create([
            'max_redirects' => 2,
        ]);
    }

    /**
     * Returns the installed, the current available and the next version
     * as an array.
     *
     * @return string[]
     * @throws Core\Exception|DecodingExceptionInterface|TransportExceptionInterface
     */
    public function getVersions(): array
    {
        $response = $this->client->request(
            'GET',
            $this->apiUrl . 'versions'
        );

        if ($response->getStatusCode() === Response::HTTP_OK) {
            try {
                $content = $response->toArray();
                return [
                    'installed' => $this->config->getVersion(),
                    'current' => $content['stable'],
                    'next' => $content['development']
                ];
            } catch (
                ClientExceptionInterface |
                RedirectionExceptionInterface |
                ServerExceptionInterface |
                TransportExceptionInterface $e
            ) {
                throw new Core\Exception('phpMyFAQ Version API is not available: ' .  $e->getMessage());
            }
        } else {
            return [
                'installed' => $this->config->getVersion(),
                'current' => 'n/a',
                'next' => 'n/a'
            ];
        }
    }

    /**
     * Returns true, if an installed version can be verified. Otherwise, false.
     *
     * @throws Core\Exception|TransportExceptionInterface|\JsonException
     */
    public function isVerified(): bool
    {
        $response = $this->client->request(
            'GET',
            $this->apiUrl . 'verify/' . $this->config->getVersion()
        );

        try {
            $this->remoteHashes = $response->getContent();
            if (json_decode($this->remoteHashes, null, 512, JSON_THROW_ON_ERROR) instanceof stdClass) {
                if (!is_array(json_decode($this->remoteHashes, true, 512, JSON_THROW_ON_ERROR))) {
                    return false;
                }

                return true;
            }
        } catch (
            ClientExceptionInterface |
            RedirectionExceptionInterface |
            ServerExceptionInterface |
            TransportExceptionInterface $e
        ) {
            throw new Core\Exception('phpMyFAQ Verification API is not available: ' .  $e->getMessage());
        }

        return false;
    }

    /**
     * @return string[]
     * @throws JsonException
     * @throws Exception
     */
    public function getVerificationIssues(): array
    {
        $system = new System();
        return array_diff(
            json_decode($system->createHashes(), true, 512, JSON_THROW_ON_ERROR),
            json_decode($this->remoteHashes, true, 512, JSON_THROW_ON_ERROR)
        );
    }
}
