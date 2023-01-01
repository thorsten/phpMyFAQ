<?php

/**
 * Provides methods for password encryption using hash().
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-01-04
 */

namespace phpMyFAQ\EncryptionTypes;

use phpMyFAQ\Encryption;

/**
 * Class Hash
 *
 * @package phpMyFAQ\EncryptionTypes
 */
class Hash extends Encryption
{
    /**
     * encrypts the string str and returns the result.
     *
     * @param string $password String
     */
    public function encrypt(string $password): string
    {
        return hash('sha256', $password . $this->salt);
    }
}
