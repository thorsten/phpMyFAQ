<?php

/**
 * Helper class for registrations
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @copyright 2020-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-10-28
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Helper;
use phpMyFAQ\Mail;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\Utils;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Class RegistrationHelper
 *
 * @package phpMyFAQ\Helper
 */
class RegistrationHelper extends Helper
{
    /**
     * RegistrationHelper constructor.
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Creates a new user account, sends mail and returns success or
     * error message as array.
     * The password will be automatically generated and sent by email
     * as soon if admin switch user to "active"
     *
     * @throws Exception|TransportExceptionInterface
     */
    public function createUser(string $userName, string $fullName, string $email, bool $isVisible): array
    {
        $user = new User($this->config);

        if (!$user->createUser($userName, '')) {
            return [
                'registered' => false,
                'error' => $user->error()
            ];
        } else {
            $user->userdata->set(
                ['display_name', 'email', 'is_visible'],
                [$fullName, $email, $isVisible === 'on' ? 1 : 0]
            );
            $user->setStatus('blocked');

            if (!$this->config->get('spam.manualActivation')) {
                $isNowActive = $user->activateUser();
            } else {
                $isNowActive = false;
            }

            if ($isNowActive) {
                // @todo add translation strings
                $adminMessage = 'This user has been automatically activated, you can still' .
                    ' modify the users permissions or decline membership by visiting the admin section';
            } else {
                $adminMessage = 'To activate this user please use';
            }

            $text = sprintf(
                "New user has been registered:\n\nName: %s\nLogin name: %s\n\n" . '%s the administration at %s.',
                $fullName,
                $userName,
                $adminMessage,
                $this->config->getDefaultUrl()
            );

            $mailer = new Mail($this->config);
            $mailer->setReplyTo($email, $fullName);
            $mailer->addTo($this->config->getAdminEmail());
            $mailer->subject = Utils::resolveMarkers(Translation::get('emailRegSubject'), $this->config);
            $mailer->message = $text;
            $mailer->send();
            unset($mailer);

            return [
                'registered' => true,
                'success' => trim(Translation::get('successMessage')) . ' ' .
                    trim(Translation::get('msgRegThankYou')),
            ];
        }
    }

    /**
     * Returns true, if hostname of the given email address is whitelisted,
     * otherwise false.
     */
    public function isDomainWhitelisted(string $email): bool
    {
        $whitelistedDomains = $this->config->get('security.domainWhiteListForRegistrations');

        if (Strings::strlen($whitelistedDomains) === 0) {
            return true;
        }

        $whitelistedDomainList = explode(',', (string) $whitelistedDomains);
        $hostnameToCheck = trim(substr(strstr($email, '@'), 1));

        foreach ($whitelistedDomainList as $hostname) {
            if ($hostnameToCheck === trim($hostname)) {
                return true;
            }
        }

        return false;
    }
}
