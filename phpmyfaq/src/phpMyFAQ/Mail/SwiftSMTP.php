<?php

/**
 * MUA (Mail User Agent) implementation using the PHP built-in mail() function.
 *
 * This document is distributed under the MIT licence.
 *
 * @package   phpMyFAQ
 * @author    Christopher Andrews <http://arduino.land>
 * @copyright 2009-2020 Christopher Andrews, phpMyFAQ Team
 * @license   http://opensource.org/licenses/MIT MIT Public License
 * @link      https://www.phpmyfaq.de
 * @since     2014-10-10
 */

namespace phpMyFAQ\Mail;

// phpcs:disable
require __DIR__ . '/../../libs/swiftmailer/swiftmailer/lib/swift_required.php';
// phpcs:enable

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
    public function setAuthConfig($server, $user, $pass, $port = 25, $security = null)
    {
        unset($this->mailer);

        $this->mailer = Swift_Mailer::newInstance(
            Swift_SmtpTransport::newInstance($server, $port, $security)
                ->setUsername($this->user = $user)
                ->setPassword($pass)
        );
    }

    /**
     * Send the message using SMTP with authorisation.
     *
     * @param string $recipients
     * @param array  $headers
     * @param string $body
     *
     * @return bool True if successful, false otherwise.
     */
    public function send($recipients, array $headers, $body)
    {
        $sender = '';
        if (('WIN' !== strtoupper(substr(PHP_OS, 0, 3))) && !ini_get('safe_mode')) {
            $sender = str_replace(array('<', '>'), '', $headers['Return-Path']);
            unset($headers['Return-Path']);
        }

        $message = Swift_Message::newInstance($headers['Subject'])
            ->setFrom(array(empty($sender) ? $this->user : $sender))
            ->setTo($recipients)
            ->setBody($body);

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
