<?php

/**
 * Helper class for registrations
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @copyright 2020-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-10-28
 */

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Mail;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\UserData;
use phpMyFAQ\Utils;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Class RegistrationHelper
 *
 * @package phpMyFAQ\Helper
 */
class RegistrationHelper extends AbstractHelper
{
    /**
     * RegistrationHelper constructor.
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Creates a new user account and saves the user data.
     * If user generation was successful, account activation is sent via an email
     * error message as an array.
     * The password will be automatically generated and sent by email
     * as soon if admin switches user to "active"
     *
     * @throws Exception|TransportExceptionInterface
     */
    public function createUser(string $userName, string $fullName, string $email, bool $isVisible): array
    {
        $user = new User($this->configuration);

        // Check if email already exists in the userdata table (even if the username is different)
        if (!empty($email)) {
            if (!$user->userdata instanceof UserData) {
                $user->userdata = new UserData($this->configuration);
            }
            if ($user->userdata->emailExists($email)) {
                return [
                    'registered' => false,
                    'error' => User::ERROR_USER_EMAIL_NOT_UNIQUE,
                ];
            }
        }

        if (!$user->createUser($userName, '')) {
            return [
                'registered' => false,
                'error' => $user->error(),
            ];
        }

        $user->userdata->set(['display_name', 'email', 'is_visible'], [$fullName, $email, $isVisible ? 1 : 0]);
        $user->setStatus('blocked');

        $isNowActive = !$this->configuration->get(item: 'spam.manualActivation') && $user->activateUser();
        if ($isNowActive) {
            // @todo add translation strings
            $adminMessage =
                'This user has been automatically activated, you can still'
                . ' modify the users permissions or decline membership by visiting the admin section';
        } else {
            $adminMessage = 'To activate this user please use';
        }

        $text = sprintf(
            'A new user has been registered:<br><br>Name: %s<br>Login name: %s<br><br>%s the administration at %s.',
            $fullName,
            $userName,
            $adminMessage,
            $this->configuration->getDefaultUrl() . '/admin/',
        );
        $mail = new Mail($this->configuration);
        $mail->setReplyTo($email, $fullName);
        $mail->addTo($this->configuration->getAdminEmail());

        $mail->subject = Utils::resolveMarkers(Translation::get(key: 'emailRegSubject'), $this->configuration);
        $mail->message = $text;
        $mail->send();
        unset($mail);

        return [
            'registered' => true,
            'success' =>
                trim((string) Translation::get(key: 'successMessage'))
                . ' '
                . trim((string) Translation::get(key: 'msgRegThankYou')),
        ];
    }

    /**
     * Returns true if the hostname of the given email address is allowed.
     * otherwise false.
     */
    public function isDomainAllowed(string $email): bool
    {
        $whitelistedDomains = $this->configuration->get(item: 'security.domainWhiteListForRegistrations');

        if ($whitelistedDomains === null || Strings::strlen(trim((string) $whitelistedDomains)) === 0) {
            return true;
        }

        $whitelistedDomainList = explode(',', (string) $whitelistedDomains);

        // Robust: validate email and extract domain safely; invalid emails are not allowed
        $email = trim($email);
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $atPos = strrpos($email, '@');
        if ($atPos === false) {
            return false; // should not happen after filter_var, but double-check
        }
        $hostnameToCheck = trim(substr($email, $atPos + 1));

        foreach ($whitelistedDomainList as $hostname) {
            if ($hostnameToCheck !== trim($hostname)) {
                continue;
            }

            return true;
        }

        return false;
    }
}
