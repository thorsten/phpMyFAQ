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
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-21
 */

namespace phpMyFAQ\Template;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UserNameTwigExtension extends AbstractExtension
{
    private User $user;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->user = new User(Configuration::getConfigurationInstance());
    }
    public function getFilters(): array
    {
        return [
            new TwigFilter('userName', $this->getUserName(...)),
            new TwigFilter('realName', $this->getRealName(...))
        ];
    }

    /**
     * @throws Exception
     */
    private function getUserName(int $userId): string
    {
        $this->user->getUserById($userId);
        return $this->user->getLogin();
    }

    /**
     * @throws Exception
     */
    private function getRealName(int $userId): string
    {
        $this->user->getUserById($userId);
        return $this->user->getUserData('display_name');
    }
}
