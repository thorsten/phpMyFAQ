<?php
/**
 * $Id: EncMd5.php,v 1.7 2008-05-31 14:41:23 thorstenr Exp $
 *
 * provides methods for password encryption using md5().
 *
 * @author      Lars Tiedemann <php@larstiedemann.de>
 * @since       2005-09-18
 * @copyright   (c) 2005-2007 phpMyFAQ Team
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
 * provides methods for password encryption. 
 *
 * Subclasses (extends) of this class provide the encrypt() method that returns
 * encrypted string. For special encryption methods, just create a new class as
 * extend of this class and has the method encrypt().
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-18
 * @version 0.1
 */
 
class PMF_User_EncMd5 extends PMF_User_Enc
{
    /**
     * Name of the encryption method.
     *
     * @access public
     * @var string
     */
    var $enc_method = 'md5';

    /**
     * encrypts the string str and returns the result.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return string
     */
    function encrypt($str)
    {
        return md5($str);
    }
}

