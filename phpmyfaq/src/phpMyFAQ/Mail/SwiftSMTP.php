<?php

/**
 * MUA (Mail User Agent) implementation using the PHP built-in mail() function.
 *
 * This document is distributed under the MIT licence.
 *
 * @package   phpMyFAQ
 * @author    Christopher Andrews <http://arduino.land>
 * @copyright 2009-2022 Christopher Andrews, phpMyFAQ Team
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
    private string $user;

    /**
     * @var Swift_Mailer
     */
    private Swift_Mailer $mailer;

    /**
     * @param string $server
     * @param string $user
     * @param string $pass
     * @param int    $port
     * @param null   $security
     */
    public function setAuthConfig(string $server, string $user, string $pass, int $port = 25, $security = null): void
    {
        unset($this->mailer);

        $transport = (new Swift_SmtpTransport($server, $port, $security))
            ->setUsername($this->user = $user)
            ->setPassword($pass);

        $this->mailer = new Swift_Mailer($transport);
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

        $message = (new Swift_Message($headers['Subject']))
            ->setFrom([empty($sender) ? $this->user : $sender])
            ->setTo($recipients)->setBody($body);

        unset($headers['Subject']);
        $mailHeaders = $message->getHeaders();

        if (isset($headers['CC'])) {
            $mailHeaders->addTextHeader('Cc', $headers['CC']);
        }
        if (isset($headers['Bcc'])) {
            $mailHeaders->addTextHeader('Bcc', $headers['Bcc']);
        }
        if (isset($headers['Reply-To'])) {
            $mailHeaders->addTextHeader('Reply-To', $headers['Reply-To']);
        }

        // Send mail and return result.
        return $this->mailer->send($message);
    }
}
