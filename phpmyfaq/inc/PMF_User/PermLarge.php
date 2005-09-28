<?php

error_reporting(E_ALL);

/**
 * The large permission class is not yet implemented.
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
 * The medium permission class provides group rights.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-18
 * @version 0.1
 */
require_once('PMF/PermMedium.php');

/* user defined includes */
// section 127-0-0-1-17ec9f7:105b52d5117:-7fdd-includes begin
// section 127-0-0-1-17ec9f7:105b52d5117:-7fdd-includes end

/* user defined constants */
// section 127-0-0-1-17ec9f7:105b52d5117:-7fdd-constants begin
// section 127-0-0-1-17ec9f7:105b52d5117:-7fdd-constants end

/**
 * The large permission class is not yet implemented.
 *
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */
class PMF_PermLarge
    extends PMF_PermMedium
{
    // --- ATTRIBUTES ---

    /**
     * Short description of attribute newAttr
     *
     * @access public
     * @var int
     */
    var $newAttr = 0;

    // --- OPERATIONS ---

    /**
     * Short description of method PMF_PermLarge
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_PermLarge()
    {
        // section -64--88-1-5--efee334:10665989edf:-7fd2 begin
        // section -64--88-1-5--efee334:10665989edf:-7fd2 end
    }

} /* end of class PMF_PermLarge */

?>