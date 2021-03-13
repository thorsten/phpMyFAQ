<?php

/**
 * Provides methods for password encryption using crypt().
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-18
 */

namespace phpMyFAQ\EncryptionTypes;

use phpMyFAQ\Encryption;

/**
 * Class Crypt
 *
 * @package phpMyFAQ\EncryptionTypes
 */
class Crypt extends Encryption
{
    /**
     * encrypts the string str and returns the result.
     *
     * @param string $string String
     * @return string
     */
    public function encrypt(string $string): string
    {
        return crypt($string, $this->salt);
    }
}
