<?php

/**
 * Provides methods for password encryption using crypt().
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-18
 */

namespace phpMyFAQ\EncryptionTypes;

use phpMyFAQ\Encryption;

/**
 * Class Crypt
 *
 * @package phpMyFAQ\EncryptionTypes
 * @deprecated will be removed with v3.3
 */
class Crypt extends Encryption
{
    /**
     * encrypts the string str and returns the result.
     *
     * @param string $password String
     */
    public function encrypt(string $password): string
    {
        return crypt($password, $this->salt);
    }
}
