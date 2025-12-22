<?php

/**
 * Provides methods for password encryption using PHP 5.5+ password_hash().
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-03-29
 */

declare(strict_types=1);

namespace phpMyFAQ\EncryptionTypes;

use phpMyFAQ\Encryption;
use SensitiveParameter;

/**
 * Class Bcrypt
 *
 * @package phpMyFAQ\EncryptionTypes
 */
class Bcrypt extends Encryption
{
    /**
     * Encrypts the passwords and returns the result.
     *
     * @param string $password String
     */
    #[\Override]
    public function encrypt(#[SensitiveParameter] string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
