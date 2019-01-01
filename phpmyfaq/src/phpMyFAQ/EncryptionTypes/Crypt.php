<?php

namespace phpMyFAQ\EncryptionTypes;

/**
 * Provides methods for password encryption using crypt().
 *
 * 
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-18
 */

use phpMyFAQ\Encryption;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Enc_Crypt.
 *
 * @category  phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-18
 */
class Crypt extends Encryption
{
    /**
     * encrypts the string str and returns the result.
     *
     * @param string $str String
     *
     * @return string
     */
    public function encrypt($str)
    {
        return crypt($str, $this->salt);
    }
}
