<?php

/**
 * MUA (Mail User Agent) implementation using the PHP built-in mail() function.
 *
 * PHP Version 5.5
 *
 * This document is distributed under the MIT licence.
 *
 * @category  phpMyFAQ
 * @author    Christopher Andrews <http://arduino.land>
 * @copyright 2009-2019 Christopher Andrews, phpMyFAQ Team
 * @license   http://opensource.org/licenses/MIT MIT Public License
 * @link      https://www.phpmyfaq.de
 * @since     2014-10-10
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

require __DIR__ . '/../../libs/swiftmailer/swift_required.php';

/**
 * Class PMF_Mail_SwiftSMTP
 */
class PMF_Mail_SwiftSMTP implements PMF_Mail_IMUA
{
    private $user;
    private $mailer;

    /**
     * @param $server
     * @param $user
     * @param $pass
     */
    public function setAuthConfig($server, $user, $pass)
    {
        unset($this->mailer);

        $this->mailer = Swift_Mailer::newInstance(
            Swift_SmtpTransport::newInstance($server)
                ->setUsername($this->user = $user)
                ->setPassword($pass)
        );
    }

    /**
     * Send the message using SMTP with authorisation.
     *
     * @param string $recipients
     * @param array $headers
     * @param string $body
     *
     * @return bool True if successful, false otherwise.
     */
    public function send($recipients, Array $headers, $body)
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
