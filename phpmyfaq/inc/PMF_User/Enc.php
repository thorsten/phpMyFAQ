<?php

error_reporting(E_ALL);

/**
 * This class provides methods for password encryption.
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
 * This container class manages user authentication.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-18
 * @version 0.1
 */
require_once('PMF/Auth.php');

/* user defined includes */
// section 127-0-0-1-17ec9f7:1062544275a:-7ffd-includes begin
// section 127-0-0-1-17ec9f7:1062544275a:-7ffd-includes end

/* user defined constants */
// section 127-0-0-1-17ec9f7:1062544275a:-7ffd-constants begin
// section 127-0-0-1-17ec9f7:1062544275a:-7ffd-constants end

/**
 * This class provides methods for password encryption.
 *
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */
class PMF_Enc
{
    // --- ATTRIBUTES ---

    /**
     * Short description of attribute pass
     *
     * @access public
     * @var string
     */
    var $pass = '';

    /**
     * Short description of attribute pass_encrypted
     *
     * @access public
     * @var string
     */
    var $pass_encrypted = '';

    /**
     * Short description of attribute enc_method
     *
     * @access public
     * @var string
     */
    var $enc_method = '';

    // --- OPERATIONS ---

    /**
     * Short description of method encrypt
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function encrypt()
    {
        // section -64--88-1-5-a522a6:106564ad215:-7fde begin
        // section -64--88-1-5-a522a6:106564ad215:-7fde end
    }

    /**
     * Short description of method PMF_Enc
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_Enc()
    {
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fc9 begin
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fc9 end
    }

} /* end of class PMF_Enc */

?>