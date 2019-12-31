<?php

/**
 * Provides methods for password encryption using md5().
 *
 * @deprecated This class will be removed in phpMyFAQ 3.1
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2020 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-18
 */

namespace phpMyFAQ\EncryptionTypes;

use phpMyFAQ\Encryption;

/**
 * Class Md5
 *
 * @package phpMyFAQ\EncryptionTypes
 */
class Md5 extends Encryption
{
    /**
     * encrypts the string str and returns the result.
     *
     * @param string $str String
     *
     * @return string
     */
    public function encrypt($str): string
    {
        return md5($str);
    }
}
