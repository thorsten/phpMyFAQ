<?php
/**
 * Provides methods for password encryption using crypt().
 *
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   PMF_Enc
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
 * @copyright 2005-2009 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-18
 */
class PMF_Enc_Crypt extends PMF_Enc
{
    /**
     * Name of the encryption method.
     *
     * @var string
     */
    public $enc_method = 'crypt';

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