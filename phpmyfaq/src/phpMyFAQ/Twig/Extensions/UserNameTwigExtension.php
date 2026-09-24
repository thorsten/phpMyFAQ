<?php

/**
 * Twig extension to return the login name of a user
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Template
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-21
 */

declare(strict_types=1);

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use Twig\Attribute\AsTwigFilter;
use Twig\Extension\AbstractExtension;

class UserNameTwigExtension extends AbstractExtension
{
    /**
     * Returns the login name of the given user. Falls back to a neutral placeholder
     * if the user cannot be resolved anymore, e.g. because the account was deleted.
     *
     * @throws Exception
     */
    #[AsTwigFilter(name: 'userName')]
    public static function getUserName(int $userId): string
    {
        $user = new User(Configuration::getConfigurationInstance());
        if (!$user->getUserById($userId, allowBlockedUsers: true)) {
            return self::getUnknownUserPlaceholder($userId);
        }

        return $user->getLogin();
    }

    /**
     * Returns the display name of the given user. Falls back to the login name if no
     * display name is stored and to a neutral placeholder if the user cannot be
     * resolved anymore, e.g. because the account was deleted.
     *
     * @throws Exception
     */
    #[AsTwigFilter(name: 'realName')]
    public static function getRealName(int $userId): string
    {
        $user = new User(Configuration::getConfigurationInstance());
        if (!$user->getUserById($userId, allowBlockedUsers: true)) {
            return self::getUnknownUserPlaceholder($userId);
        }

        $displayName = $user->getUserData(field: 'display_name');
        if (is_string($displayName) && $displayName !== '') {
            return $displayName;
        }

        return $user->getLogin();
    }

    private static function getUnknownUserPlaceholder(int $userId): string
    {
        $translation = Translation::get('msgUnknownUser');

        return sprintf(is_string($translation) && $translation !== '' ? $translation : 'Unknown user (#%d)', $userId);
    }
}
