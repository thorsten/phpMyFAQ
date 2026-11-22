<?php

/**
 * MUA (Mail User Agent) implementation using the Symfony Mailer class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-01-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Mail;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Class SMTP
 */
class Smtp implements MailUserAgentInterface
{
    private string $user;

    private MailerInterface $mailer;

    public function setAuthConfig(
        string $server,
        string $user = '',
        #[\SensitiveParameter]
        string $password = '',
        int $port = 25,
        bool $disableTlsPeerVerification = false,
    ): void {
        $dsn = sprintf('smtp://%s:%s@%s:%d', $this->user = $user, urlencode($password), $server, $port);

        if ($disableTlsPeerVerification) {
            $dsn .= '?verify_peer=0';
        }

        $this->mailer = new Mailer(Transport::fromDsn($dsn));
    }

    /**
     * Unlike the built-in agent, which sends a prepared MIME body, the message is composed here.
     *
     * @param array<string, int|string|null> $headers
     * @param string $body Plain text: the whole message, or the alternative of an HTML message
     * @param string $htmlBody HTML message, empty for a plain text mail that is sent as text/plain only
     * @throws TransportExceptionInterface
     */
    public function send(string $recipients, array $headers, string $body, string $htmlBody = ''): int
    {
        $sender = '';
        if (
            'WIN' !== strtoupper(string: substr(string: PHP_OS, offset: 0, length: 3))
            && !ini_get(option: 'safe_mode')
            && array_key_exists('Return-Path', $headers)
        ) {
            $sender = str_replace(search: ['<', '>'], replace: '', subject: (string) $headers['Return-Path']);
            unset($headers['Return-Path']);
        }

        $from = (string) ($headers['From'] ?? '');
        if ($from === '') {
            $from = $sender === '' ? $this->user : $sender;
        }

        $email = new Email()
            ->from($this->createAddress($from))
            ->to(...$this->createAddresses($recipients))
            ->subject((string) ($headers['Subject'] ?? ''))
            ->text($body);

        if ($htmlBody !== '') {
            $email->html($htmlBody);
        }

        if ($sender !== '') {
            $email->returnPath($sender);
        }

        if (array_key_exists('CC', $headers) || array_key_exists('Cc', $headers)) {
            $email->cc(...$this->createAddresses((string) ($headers['CC'] ?? $headers['Cc'] ?? '')));
        }

        if (array_key_exists('BCC', $headers) || array_key_exists('Bcc', $headers)) {
            $email->bcc(...$this->createAddresses((string) ($headers['BCC'] ?? $headers['Bcc'] ?? '')));
        }

        if (array_key_exists('Reply-To', $headers)) {
            $email->replyTo($this->createAddress((string) $headers['Reply-To']));
        }

        $this->mailer->send($email);

        return 1;
    }

    /**
     * @return Address[]
     */
    private function createAddresses(string $addresses): array
    {
        return array_values(array_map($this->createAddress(...), $this->splitAddresses($addresses)));
    }

    /**
     * Mail prepares mailboxes as header text, with the display name quoted or RFC 2047 encoded.
     * Symfony Mime encodes the display name itself, so it has to get the decoded name.
     */
    private function createAddress(string $mailbox): Address
    {
        $address = Address::create($mailbox);
        $name = $address->getName();
        if ($name === '') {
            return $address;
        }

        $decodedName = iconv_mime_decode($name, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, encoding: 'UTF-8');

        return new Address($address->getAddress(), $decodedName === false ? $name : $decodedName);
    }

    /**
     * Split a comma-separated address list into single addresses,
     * as Symfony Mime accepts only one address per string.
     *
     * @return string[]
     */
    private function splitAddresses(string $addresses): array
    {
        return array_filter(
            array_map('trim', explode(',', $addresses)),
            static fn(string $address): bool => $address !== '',
        );
    }
}
