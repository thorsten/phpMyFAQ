<?php
/**
 * Provides methods for password encryption using sha().
 *
 * @package    phpMyFAQ 
 * @subpackage PMF_User
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @since      2005-09-18
 * @copyright  2005-2009 phpMyFAQ Team
 * @version    SVN: $Id$ 
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
 */

/**
 * PMF_User_EncSha
 *
 * @package    phpMyFAQ 
 * @subpackage PMF_User
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @since      2005-09-18
 * @copyright  2005-2009 phpMyFAQ Team
 * @version    SVN: $Id$ 
 */
class PMF_User_EncSha extends PMF_User_Enc
{
    // --- ATTRIBUTES ---

    /**
     * Name of the encryption method.
     *
     * @access public
     * @var string
     */
    public $enc_method = 'sha';

    
    /**
     * encrypts the string str and returns the result.
     *
     * @param  string $str String
     * @return string
     */
    public function encrypt($str)
    {
        return sha1($str);
    }
}
