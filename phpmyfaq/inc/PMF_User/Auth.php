<?php

error_reporting(E_ALL);

/**
 * This container class manages user authentication.
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
 * include PMF_CurrentUser
 *
 * @author Lars Tiedemann, <php@larstiedemann.de>
 */
require_once('PMF/CurrentUser.php');

/**
 * This class provides methods for password encryption.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-18
 * @version 0.1
 */
require_once('PMF/Enc.php');

/* user defined includes */
// section 127-0-0-1-17ec9f7:105b52d5117:-7fef-includes begin
// section 127-0-0-1-17ec9f7:105b52d5117:-7fef-includes end

/* user defined constants */
// section 127-0-0-1-17ec9f7:105b52d5117:-7fef-constants begin
// section 127-0-0-1-17ec9f7:105b52d5117:-7fef-constants end

/**
 * This container class manages user authentication.
 *
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */
class PMF_Auth
{
    // --- ATTRIBUTES ---

    /**
     * Short description of attribute enc_container
     *
     * @access public
     * @var string
     */
    var $enc_container = '';

    /**
     * Short description of attribute enc_typemap
     *
     * @access public
     * @var array
     */
    var $enc_typemap = array();

    /**
     * Short description of attribute connection
     *
     * @access public
     * @var mixed
     */
    var $connection = null;

    // --- OPERATIONS ---

    /**
     * Short description of method login
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param string
     * @return void
     */
    function login($login, $pass)
    {
        // section -64--88-1-10-1860038:10612dd0903:-7fe9 begin
        // section -64--88-1-10-1860038:10612dd0903:-7fe9 end
    }

    /**
     * Short description of method PMF_Auth
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_Auth()
    {
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd0 begin
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd0 end
    }

} /* end of class PMF_Auth */

?>