<?php

namespace phpMyFAQ\EncryptionTypes;

/**
 * Provides methods for password encryption using PHP 5.5+ password_hash().
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2014-03-29
 */

use phpMyFAQ\Encryption;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Enc_Bcrypt.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2014-03-29
 */
class Bcrypt extends Encryption
{
    /**
     * Encrypts the passwords and returns the result.
     *
     * @param string $password String
     *
     * @return string
     */
    public function encrypt($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
