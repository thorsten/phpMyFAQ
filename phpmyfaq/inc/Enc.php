<?php
/**
 * Provides methods for password encryption.
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
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
     * Public array that contains error messages.
     *
     * @var array
     */
    public $errors = array();

    /**
     * Salt
     *
     * @var string
     */
    protected $salt = '';

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
     * This method is called statically. The parameter enctype specifies the
     * of encryption method for the encryption object. Supported
     * are 'crypt', 'md5', 'sha' and 'none'.
     *
     * $enc = Enc::selectEnc('md5');
     *
     * $enc is an instance of the class PMF_Enc_Md5.
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
        $enctype = ucfirst(strtolower($enctype));

        if (!isset($enctype)) {
            $enc->errors[] = self::PMF_ERROR_USER_NO_ENCTYPE;
            return $enc;
        }

        $classfile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'PMF_Enc' . DIRECTORY_SEPARATOR . $enctype . '.php';

        if (!file_exists($classfile)) {
            $enc->errors[] = self::PMF_ERROR_USER_NO_ENCTYPE;
            return $enc;
        }
        
        $newclass = 'PMF_Enc_' . $enctype;

        if (!class_exists($newclass)) {
            $enc->errors[] = self::PMF_ERROR_USER_NO_ENCTYPE;
            return $enc;
        }

        $enc = new $newclass();
        return $enc;
    }

    /**
     * The string returned by error() contains messages for all errors that
     * during object processing. Messages are separated by new lines.
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

    /**
     * Setter for salt
     *
     * @param string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = PMF_Configuration::getInstance()->get('main.referenceURL') . $salt;

        return $this;
    }
}
