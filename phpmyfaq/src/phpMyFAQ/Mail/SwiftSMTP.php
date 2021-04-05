<?php

/**
 * MUA (Mail User Agent) implementation using the PHP built-in mail() function.
 *
 * This document is distributed under the MIT licence.
 *
 * @package   phpMyFAQ
 * @author    Christopher Andrews <http://arduino.land>
 * @copyright 2009-2021 Christopher Andrews, phpMyFAQ Team
 * @license   http://opensource.org/licenses/MIT MIT Public License
 * @link      https://www.phpmyfaq.de
 * @since     2014-10-10
 */

namespace phpMyFAQ\Mail;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

/**
 * Class SwiftSMTP
 *
 * @package phpMyFAQ\Mail
 */
class SwiftSMTP implements MailUserAgentInterface
{
    /**
     * @var string
     */
    private $user;

    /**
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * @param string $server
     * @param string $user
     * @param string $pass
     * @param int    $port
     * @param null   $security
     */
    public function setAuthConfig(string $server, string $user, string $pass, $port = 25, $security = null): void
    {
        unset($this->mailer);

        // @phpstan-ignore-next-line
        $this->mailer = Swift_Mailer::newInstance(
            // @phpstan-ignore-next-line
            Swift_SmtpTransport::newInstance($server, $port, $security)
                ->setUsername($this->user = $user)
                ->setPassword($pass)
        );
    }

    /**
     * Send the message using SMTP with authorisation.
     *
     * @param string|string[] $recipients
     * @param string[]        $headers
     * @param string          $body
     * @return int The number of successful recipients. Can be 0 which indicates failure
     */
    public function send($recipients, array $headers, string $body): int
    {
        $sender = '';
        if (('WIN' !== strtoupper(substr(PHP_OS, 0, 3))) && !ini_get('safe_mode')) {
            $sender = str_replace(['<', '>'], '', $headers['Return-Path']);
            unset($headers['Return-Path']);
        }

        // @phpstan-ignore-next-line
        $message = Swift_Message::newInstance($headers['Subject'])->setFrom(
            [empty($sender) ? $this->user : $sender]
        )->setTo($recipients)->setBody($body);

        // Prepare the headers for the e-mail
        unset($headers['Subject']);
        $headers = $message->getHeaders();
        foreach ($headers as $key => $value) {
            $headers->addTextHeader($key, $value);
        }

        // Send mail and return result.
        return $this->mailer->send($message);
    }
}
