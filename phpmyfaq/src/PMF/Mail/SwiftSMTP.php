<?php

/**
 * MUA (Mail User Agent) implementation using the PHP built-in mail() function.
 *
 * PHP Version 5.3
 *
 * This document is distributed under the MIT licence.
 *
 * @category  phpMyFAQ
 *
 * @author    Christopher Andrews <http://arduino.land>
 * @copyright 2009-2016 Christopher Andrews, phpMyFAQ Team
 * @license   http://opensource.org/licenses/MIT MIT Public License
 *
 * @link      http://www.phpmyfaq.de
 * @since     2014-10-10
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}
require_once 'inc/libs/swiftmailer/swift_required.php';

class PMF_Mail_SwiftSMTP implements PMF_Mail_IMUA
{
    private $user;
    private $mailer;

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

        //Send mail and return result.
        return $this->mailer->send($message);
    }
}
