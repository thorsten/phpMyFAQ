<?php

/**
 * Amazon SES mail provider.
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
 * @since     2026-02-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Mail\Provider;

use Aws\Ses\SesClient;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Mail\MailProviderInterface;
use Throwable;

final class SesProvider implements MailProviderInterface
{
    private ?SesClient $sesClient;

    public function __construct(
        private readonly Configuration $configuration,
        ?SesClient $sesClient = null,
    ) {
        $this->sesClient = $sesClient;
    }

    /**
     * @param array<string, string> $headers
     * @throws Exception
     */
    public function send(string $recipients, array $headers, string $body): int
    {
        $source = $this->extractEmailAddress($headers['From'] ?? '');
        if ($source === '') {
            throw new Exception('Missing valid From header for SES provider.');
        }

        $toAddresses = $this->parseRecipients($recipients);
        if ($toAddresses === []) {
            throw new Exception('No valid recipients for SES provider.');
        }

        try {
            $this->getSesClient()->sendEmail([
                'Source' => $source,
                'Destination' => [
                    'ToAddresses' => $toAddresses,
                ],
                'Message' => [
                    'Subject' => [
                        'Data' => $headers['Subject'] ?? '',
                        'Charset' => 'UTF-8',
                    ],
                    'Body' => [
                        'Text' => [
                            'Data' => $body,
                            'Charset' => 'UTF-8',
                        ],
                        'Html' => [
                            'Data' => $body,
                            'Charset' => 'UTF-8',
                        ],
                    ],
                ],
            ]);
        } catch (Throwable $throwable) {
            throw new Exception('SES mail delivery failed: ' . $throwable->getMessage());
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

    /**
     * @throws Exception
     */
    private function getSesClient(): SesClient
    {
        if ($this->sesClient instanceof SesClient) {
            return $this->sesClient;
        }

        $accessKey = (string) ($this->configuration->get('mail.sesAccessKeyId') ?? '');
        $secretKey = (string) ($this->configuration->get('mail.sesSecretAccessKey') ?? '');
        $region = (string) ($this->configuration->get('mail.sesRegion') ?? 'us-east-1');

        if ($accessKey === '' || $secretKey === '') {
            throw new Exception('SES credentials are not configured.');
        }

        $this->sesClient = new SesClient([
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
        ]);

        return $this->sesClient;
    }
}
