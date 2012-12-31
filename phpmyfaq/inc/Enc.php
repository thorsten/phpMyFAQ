<?php
/**
 * Provides methods for password encryption.
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
 * @copyright 2005-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-18
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Enc
 *
 * @category  phpMyFAQ
 * @package   PMF_Enc
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-18
 */ 
class PMF_Enc
{
	/**
	 * Error constant
	 * 
	 * @const
	 */
	const PMF_ERROR_USER_NO_ENCTYPE = 'Specified encryption method could not be found.';
	
    /**
     * Encryption methods
     *
     * @var array
     */
    private $enc_typemap = array(
        'none'  => 'Enc', 
        'crypt' => 'EncCrypt', 
        'sha'   => 'EncSha', 
        'md5'   => 'EncMd5');

    /**
     * Public array that contains error messages.
     *
     * @var array
     */
    public $errors = array();

    /**
     * Encrypts the string str and returns the result.
     *
     * @param string $str String
     * 
     * @return string
     */
    public function encrypt($str)
    {
        return $str;
    }

    /**
     * returns an encryption object with the specified encryption method. 
     *
     * This method is called statically. The parameter enctype specifies the
     * of encryption method for the encryption object. Supported
     * are 'crypt', 'md5', 'sha' and 'none'.
     *
     * $enc = Enc::selectEnc('md5');
     *
     * $enc is an instance of the class EncMd5. 
     *
     * If the given encryption-type is not supported, selectEnc() will return an
     * object without database access and with an error message. See the
     * of the error() method for further details.
     *
     * @param  string
     * @return PMF_Enc
     */
    public static function selectEnc($enctype)
    {
        $enc     = new PMF_Enc();
        $enctype = strtolower($enctype);
        if (!isset($enc->enc_typemap[$enctype])) {
        	$enc->errors[] = self::PMF_ERROR_USER_NO_ENCTYPE;
        	return $enc;
        }
        $classfile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'PMF_Enc' . DIRECTORY_SEPARATOR . $enc->enc_typemap[$enctype].".php";
        if (!file_exists($classfile)) {
        	$enc->errors[] = self::PMF_ERROR_USER_NO_ENCTYPE;
        	return $enc;
        }
        
        $newclass = "PMF_Enc_".$enc->enc_typemap[$enctype];
        if (!class_exists($newclass)) {
        	$enc->errors[] = self::PMF_ERROR_USER_NO_ENCTYPE;
        	return $enc;
        }
        $enc = new $newclass();
        return $enc;
    }

    /**
     * Returns a string with error messages. 
     *
     * The string returned by error() contains messages for all errors that
     * during object procesing. Messages are separated by new lines.
     *
     * Error messages are stored in the public array errors.
     *
     * @return string
     */
    public function error()
    {
        if (!is_array($this->errors)) {
        	$this->errors = array((string) $this->errors);
        }
        $message = '';
        foreach ($this->errors as $error) {
        	$message .= $error."\n";
        }
        return $message;
    }
}
