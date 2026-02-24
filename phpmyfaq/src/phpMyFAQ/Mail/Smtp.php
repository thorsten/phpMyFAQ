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
        #[\SensitiveParameter] string $password = '',
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
     * @param string[] $headers
     * @throws TransportExceptionInterface
     */
    public function send(string $recipients, array $headers, string $body): int
    {
        $sender = '';
        if (
            'WIN' !== strtoupper(string: substr(string: PHP_OS, offset: 0, length: 3))
            && !ini_get(option: 'safe_mode')
            && array_key_exists('Return-Path', $headers)
        ) {
            $sender = str_replace(search: ['<', '>'], replace: '', subject: $headers['Return-Path']);
            unset($headers['Return-Path']);
        }

        $email = new Email()
            ->from($sender === '' ? $this->user : $sender)
            ->to($recipients)
            ->subject($headers['Subject'])
            ->text($body)
            ->html($body);

        if (array_key_exists('CC', $headers) || array_key_exists('Cc', $headers)) {
            $cc = $headers['CC'] ?? $headers['Cc'];
            $email->cc($cc);
        }

        if (array_key_exists('Bcc', $headers)) {
            $email->bcc($headers['Bcc']);
        }

        if (array_key_exists('Reply-To', $headers)) {
            $email->replyTo($headers['Reply-To']);
        }

        $this->mailer->send($email);

        return 1;
    }
}
