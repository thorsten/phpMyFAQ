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
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-01-23
 */

namespace phpMyFAQ\Mail;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

/**
 * Class SMTP
 */
class SMTP implements MailUserAgentInterface
{
    private string $user;

    private MailerInterface $mailer;

    public function setAuthConfig(
        string $server,
        string $user,
        string $password,
        int $port = 25,
        bool $disableTlsPeerVerification = false
    ): void {
        $dsn = sprintf('smtp://%s:%s@%s:%d', $this->user = $user, $password, $server, $port);

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
        if (('WIN' !== strtoupper(substr(PHP_OS, 0, 3))) && !ini_get('safe_mode')) {
            $sender = str_replace(['<', '>'], '', $headers['Return-Path']);
            unset($headers['Return-Path']);
        }

        $email = (new Email())
            ->from(empty($sender) ? $this->user : $sender)
            ->to($recipients)
            ->subject($headers['Subject'])
            ->text($body);

        if (isset($headers['CC'])) {
            $email->cc($headers['CC']);
        }
        if (isset($headers['Bcc'])) {
            $email->bcc($headers['Bcc']);
        }
        if (isset($headers['Reply-To'])) {
            $email->replyTo($headers['Reply-To']);
        }

        $this->mailer->send($email);

        return 1;
    }
}
