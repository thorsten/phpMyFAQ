<?php

declare(strict_types=1);

/**
 * This class is a helper class for sending mails.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-02-23
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Mail;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\Utils;
use SensitiveParameter;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Class MailHelper
 *
 * @package phpMyFAQ\Helper
 */
readonly class MailHelper
{
    private Mail $mail;

    /**
     * MailHelper constructor.
     */
    public function __construct(
        private Configuration $configuration,
    ) {
        $this->mail = new Mail($this->configuration);
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function sendMailToNewUser(User $user, #[SensitiveParameter] string $password): bool
    {
        $text = sprintf(
            '<p>You have been registered as a new user:</p><p>Name: %s<br>Login name: %s<br>Password: %s</p>'
            . '<p><a href="%s">Check it out here</a></p>',
            $user->getUserData('display_name'),
            $user->getLogin(),
            $password,
            $this->configuration->getDefaultUrl(),
        );

        $this->mail->addTo($user->getUserData('email'), $user->getUserData('display_name'));
        $this->mail->subject = Utils::resolveMarkers(Translation::get(key: 'emailRegSubject'), $this->configuration);
        $this->mail->message = $text;

        return (bool) $this->mail->send();
    }
}
