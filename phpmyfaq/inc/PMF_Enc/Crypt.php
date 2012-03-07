<?php
/**
 * Provides methods for password encryption using crypt().
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Enc
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-18
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Enc_Crypt
 *
 * @category  phpMyFAQ
 * @package   PMF_Enc
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-18
 */
class PMF_Enc_Crypt extends PMF_Enc
{
    /**
     * encrypts the string str and returns the result.
     *
     * @param  string $str String
     * @return string
     */
    public function encrypt($str)
    {
        return crypt($str);
    }
}