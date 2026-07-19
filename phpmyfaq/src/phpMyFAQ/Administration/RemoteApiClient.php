<?php

/**
 * Remote API client handler class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-03-27
 */

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use Exception;
use phpMyFAQ\Configuration;
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
 * Class RemoteApiClient
 *
 * @package phpMyFAQ
 */
class RemoteApiClient
{
    private string $apiUrl = 'https://api.phpmyfaq.de/';

    private ?string $remoteHashes = null;

    private HttpClientInterface $httpClient;

    /**
     * Api constructor.
     */
    public function __construct(
        private readonly Configuration $configuration,
        private readonly System $system,
    ) {
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
        $response = $this->httpClient->request('GET', $this->apiUrl . 'versions');

        if ($response->getStatusCode() === Response::HTTP_OK) {
            try {
                $content = $response->toArray();
                return [
                    'installed' => $this->configuration->getVersion(),
                    'stable' => (string) ($content['stable'] ?? ''),
                    'development' => (string) ($content['development'] ?? ''),
                    'nightly' => (string) ($content['nightly'] ?? ''),
                ];
            } catch (
                ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $exception
            ) {
                throw new Exception(
                    'phpMyFAQ Verification API is not available: ' . $exception->getMessage(),
                    (int) $exception->getCode(),
                    $exception,
                );
            }
        }

        return [
            'installed' => $this->configuration->getVersion(),
            'stable' => 'n/a',
            'development' => 'n/a',
            'nightly' => 'n/a',
        ];
    }

    /**
     * Returns true, if an installed version can be verified. Otherwise, false.
     *
     * @throws Exception|TransportExceptionInterface|\JsonException
     */
    public function isVerified(): bool
    {
        $response = $this->httpClient->request('GET', $this->apiUrl . 'verify/' . $this->configuration->getVersion());

        try {
            $this->remoteHashes = $response->getContent();
            if (
                json_decode(json: $this->remoteHashes, associative: null, depth: 512, flags: JSON_THROW_ON_ERROR)
                instanceof stdClass
            ) {
                return is_array(json_decode(
                    json: $this->remoteHashes,
                    associative: true,
                    depth: 512,
                    flags: JSON_THROW_ON_ERROR,
                ));
            }
        } catch (
            ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $exception
        ) {
            throw new Exception(
                'phpMyFAQ Verification API is not available: ' . $exception->getMessage(),
                (int) $exception->getCode(),
                $exception,
            );
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
        $localHashes = json_decode(
            json: $this->system->createHashes(),
            associative: true,
            depth: 512,
            flags: JSON_THROW_ON_ERROR,
        );
        $remoteHashes = json_decode(
            json: (string) $this->remoteHashes,
            associative: true,
            depth: 512,
            flags: JSON_THROW_ON_ERROR,
        );

        $issues = array_diff(is_array($localHashes) ? $localHashes : [], is_array($remoteHashes) ? $remoteHashes : []);

        $verificationIssues = [];
        foreach ($issues as $issueKey => $issueValue) {
            $verificationIssues[$issueKey] = (string) $issueValue;
        }

        return $verificationIssues;
    }

    public function setHttpClient(HttpClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    public function setRemoteHashes(?string $remoteHashes): RemoteApiClient
    {
        $this->remoteHashes = $remoteHashes;
        return $this;
    }
}
