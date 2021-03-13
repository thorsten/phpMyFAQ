<?php

/**
 * Provides methods for password encryption using hash().
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
     * @param string $string String
     * @return string
     */
    public function encrypt(string $string): string
    {
        return hash('sha256', $string . $this->salt);
    }
}
