<?php

error_reporting(E_ALL);

/**
 * provides methods for password encryption using sha().
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
//require_once('PMF/Enc.php');

/* user defined includes */
// section 127-0-0-1-17ec9f7:1062544275a:-7ff1-includes begin
require_once dirname(__FILE__).'/Enc.php';
// section 127-0-0-1-17ec9f7:1062544275a:-7ff1-includes end

/* user defined constants */
// section 127-0-0-1-17ec9f7:1062544275a:-7ff1-constants begin
// section 127-0-0-1-17ec9f7:1062544275a:-7ff1-constants end

/**
 * provides methods for password encryption using sha().
 *
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */
class PMF_EncSha
    extends PMF_Enc
{
    // --- ATTRIBUTES ---

    /**
     * Name of the encryption method.
     *
     * @access public
     * @var string
     */
    var $enc_method = 'sha';

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

        // section 127-0-0-1-17ec9f7:1062544275a:-7feb begin
        return sha1($str);
        // section 127-0-0-1-17ec9f7:1062544275a:-7feb end

        return (string) $returnValue;
    }

    /**
     * constructor
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_EncSha()
    {
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fc5 begin
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fc5 end
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
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fce begin
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fce end
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
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fcc begin
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fcc end
    }

} /* end of class PMF_EncSha */

?>
