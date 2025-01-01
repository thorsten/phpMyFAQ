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
 * @copyright 2018-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-03-27
 */

namespace phpMyFAQ\Administration;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core;
use phpMyFAQ\System;
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

    private HttpClientInterface $httpClient;

    /**
     * Api constructor.
     */
    public function __construct(private readonly Configuration $configuration, private readonly System $system)
    {
        $this->setHttpClient(HttpClient::create(['max_redirects' => 2, 'timeout' => 30]));
    }

    /**
     * Returns the installed, the current available and the next version
     * as an array.
     *
     * @return string[]
     * @throws Exception|DecodingExceptionInterface|TransportExceptionInterface
     */
    public function getVersions(): array
    {
        $response = $this->httpClient->request(
            'GET',
            $this->apiUrl . 'versions'
        );

        if ($response->getStatusCode() === Response::HTTP_OK) {
            try {
                $content = $response->toArray();
                return [
                    'installed' => $this->configuration->getVersion(),
                    'stable' => $content['stable'],
                    'development' => $content['development'],
                    'nightly' => $content['nightly']
                ];
            } catch (
                ClientExceptionInterface |
                RedirectionExceptionInterface |
                ServerExceptionInterface |
                TransportExceptionInterface $e
            ) {
                throw new Core\Exception('phpMyFAQ Version API is not available: ' .  $e->getMessage());
            }
        }

        return [
            'installed' => $this->configuration->getVersion(),
            'stable' => 'n/a',
            'development' => 'n/a',
            'nightly' => 'n/a'
        ];
    }

    /**
     * Returns true, if an installed version can be verified. Otherwise, false.
     *
     * @throws Exception|TransportExceptionInterface|\JsonException
     */
    public function isVerified(): bool
    {
        $response = $this->httpClient->request(
            'GET',
            $this->apiUrl . 'verify/' . $this->configuration->getVersion()
        );

        try {
            $this->remoteHashes = $response->getContent();
            if (json_decode((string) $this->remoteHashes, null, 512, JSON_THROW_ON_ERROR) instanceof stdClass) {
                return is_array(json_decode((string) $this->remoteHashes, true, 512, JSON_THROW_ON_ERROR));
            }
        } catch (
            ClientExceptionInterface |
            RedirectionExceptionInterface |
            ServerExceptionInterface |
            TransportExceptionInterface $e
        ) {
            throw new Exception('phpMyFAQ Verification API is not available: ' .  $e->getMessage());
        }

        return false;
    }

    /**
     * @return string[]
     * @throws \JsonException
     * @throws Exception
     */
    public function getVerificationIssues(): array
    {
        return array_diff(
            json_decode($this->system->createHashes(), true, 512, JSON_THROW_ON_ERROR),
            json_decode((string) $this->remoteHashes, true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function setHttpClient(HttpClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    public function setRemoteHashes(?string $remoteHashes): Api
    {
        $this->remoteHashes = $remoteHashes;
        return $this;
    }
}
