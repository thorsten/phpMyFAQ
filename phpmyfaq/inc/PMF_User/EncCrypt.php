<?php

error_reporting(E_ALL);

/**
 * provides methods for password encryption using crypt().
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */

if (0 > version_compare(PHP_VERSION, '4')) {
    die('This file was generated for PHP 4');
}

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
require_once('PMF/Enc.php');

/* user defined includes */
// section 127-0-0-1-17ec9f7:1062544275a:-7fef-includes begin
// section 127-0-0-1-17ec9f7:1062544275a:-7fef-includes end

/* user defined constants */
// section 127-0-0-1-17ec9f7:1062544275a:-7fef-constants begin
// section 127-0-0-1-17ec9f7:1062544275a:-7fef-constants end

/**
 * provides methods for password encryption using crypt().
 *
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */
class PMF_EncCrypt
    extends PMF_Enc
{
    // --- ATTRIBUTES ---

    /**
     * Name of the encryption method.
     *
     * @access public
     * @var string
     */
    var $enc_method = 'crypt';

    // --- OPERATIONS ---

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
        $returnValue = (string) '';

        // section 127-0-0-1-17ec9f7:1062544275a:-7fe7 begin
        return crypt($str);
        // section 127-0-0-1-17ec9f7:1062544275a:-7fe7 end

        return (string) $returnValue;
    }

    /**
     * constructor
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_EncCrypt()
    {
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fc7 begin
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fc7 end
    }

    /**
     * constructor
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function __construct()
    {
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fca begin
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fca end
    }

    /**
     * destructor
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function __destruct()
    {
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fc8 begin
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fc8 end
    }

} /* end of class PMF_EncCrypt */

?>