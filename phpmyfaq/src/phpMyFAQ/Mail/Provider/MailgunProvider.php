<?php

/**
 * Mailgun mail provider.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-14
 */

declare(strict_types=1);

namespace phpMyFAQ\Mail\Provider;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Mail\MailProviderInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class MailgunProvider implements MailProviderInterface
{
    private HttpClientInterface $httpClient;

    public function __construct(
        private Configuration $configuration,
        ?HttpClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    /**
     * @param array<string, string> $headers
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function send(string $recipients, array $headers, string $body): int
    {
        $apiKey = (string) ($this->configuration->get('mail.mailgunApiKey') ?? '');
        if ($apiKey === '') {
            throw new Exception('Mailgun API key is not configured.');
        }

        $domain = (string) ($this->configuration->get('mail.mailgunDomain') ?? '');
        if ($domain === '') {
            throw new Exception('Mailgun domain is not configured.');
        }

        $fromAddress = $this->extractEmailAddress($headers['From'] ?? '');
        if ($fromAddress === '') {
            throw new Exception('Missing valid From header for Mailgun provider.');
        }

        $toAddresses = $this->parseRecipients($recipients);
        if ($toAddresses === []) {
            throw new Exception('No valid recipients for Mailgun provider.');
        }

        $region = strtolower((string) ($this->configuration->get('mail.mailgunRegion') ?? 'us'));
        $baseUrl = $region === 'eu' ? 'https://api.eu.mailgun.net' : 'https://api.mailgun.net';

        $response = $this->httpClient->request('POST', $baseUrl . '/v3/' . $domain . '/messages', [
            'auth_basic' => ['api', $apiKey],
            'body' => [
                'from' => $fromAddress,
                'to' => implode(',', $toAddresses),
                'subject' => $headers['Subject'] ?? '',
                'text' => $body,
                'html' => $body,
            ],
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new Exception(sprintf(
                'Mailgun request failed with status %d: %s',
                $statusCode,
                $response->getContent(false),
            ));
        }

        return count($toAddresses);
    }

    /**
     * @return array<int, string>
     */
    private function parseRecipients(string $recipients): array
    {
        $addresses = [];
        foreach (explode(',', $recipients) as $recipient) {
            $address = $this->extractEmailAddress($recipient);
            if ($address !== '') {
                $addresses[] = $address;
            }
        }

        return array_values(array_unique($addresses));
    }

    private function extractEmailAddress(string $rawAddress): string
    {
        if (preg_match('/<([^>]+)>/', $rawAddress, $matches) === 1) {
            return trim($matches[1]);
        }

        return trim($rawAddress);
    }
}
