<?php

error_reporting(E_ALL);

/**
 * This class provides methods for password encryption using md5.
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
 * This class provides methods for password encryption.
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
 * This class provides methods for password encryption using md5.
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
     * Short description of attribute enc_method
     *
     * @access public
     * @var string
     */
    var $enc_method = 'md5';

    // --- OPERATIONS ---

    /**
     * Short description of method encrypt
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return string
     */
    function encrypt()
    {
        $returnValue = (string) '';

        // section 127-0-0-1-17ec9f7:1062544275a:-7fe9 begin
        // section 127-0-0-1-17ec9f7:1062544275a:-7fe9 end

        return (string) $returnValue;
    }

    /**
     * Short description of method PMF_EncMd5
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

} /* end of class PMF_EncMd5 */

?>