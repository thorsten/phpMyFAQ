<?php

error_reporting(E_ALL);

/**
 * provides methods for password encryption using md5().
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
// section 127-0-0-1-17ec9f7:1062544275a:-7ff3-includes begin
// section 127-0-0-1-17ec9f7:1062544275a:-7ff3-includes end

/* user defined constants */
// section 127-0-0-1-17ec9f7:1062544275a:-7ff3-constants begin
// section 127-0-0-1-17ec9f7:1062544275a:-7ff3-constants end

/**
 * provides methods for password encryption using md5().
 *
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */
class PMF_EncMd5
    extends PMF_Enc
{
    // --- ATTRIBUTES ---

    /**
     * Name of the encryption method.
     *
     * @access public
     * @var string
     */
    var $enc_method = 'md5';

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

        // section 127-0-0-1-17ec9f7:1062544275a:-7fe9 begin
        return md5($str);
        // section 127-0-0-1-17ec9f7:1062544275a:-7fe9 end

        return (string) $returnValue;
    }

    /**
     * constructor
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_EncMd5()
    {
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fc3 begin
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fc3 end
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
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fd2 begin
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fd2 end
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
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fd0 begin
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fd0 end
    }

} /* end of class PMF_EncMd5 */

?>