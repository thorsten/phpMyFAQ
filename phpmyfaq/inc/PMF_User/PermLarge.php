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
     * Short description of attribute context
     *
     * @access private
     * @var string
     */
    var $_context = '';

    /**
     * Short description of attribute context_id
     *
     * @access private
     * @var int
     */
    var $_context_id = 0;

    // --- OPERATIONS ---

    /**
     * Short description of method PMF_PermLarge
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @param int
     * @return void
     */
    function PMF_PermLarge($db, $user_id)
    {
        // section -64--88-1-5--efee334:10665989edf:-7fd2 begin
        // section -64--88-1-5--efee334:10665989edf:-7fd2 end
    }

    /**
     * Short description of method __destruct
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function __destruct()
    {
        // section -64--88-1-10--2ab496b6:106d484ef91:-7f96 begin
        // section -64--88-1-10--2ab496b6:106d484ef91:-7f96 end
    }

    /**
     * Short description of method getRightId
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return int
     */
    function getRightId($name)
    {
        $returnValue = (int) 0;

        // section -64--88-1-10--77ac1b05:106d99eac38:-7fb7 begin
        // section -64--88-1-10--77ac1b05:106d99eac38:-7fb7 end

        return (int) $returnValue;
    }

    /**
     * Short description of method setPerm
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @param int
     * @param string
     * @param int
     * @return void
     */
    function setPerm($db, $user_id = 0, $context = '', $context_id = 0)
    {
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fcf begin
        if (!PMF_User::checkDb($db))
            return false;
        $this->_db         = $db;
        $this->_user_id    = $user_id;
        $this->_context    = $context;
        $this->_context_id = $context_id;
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fcf end
    }

    /**
     * Short description of method addRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param array
     * @param array
     * @return int
     */
    function addRight($right_data, $context_data = array())
    {
        $returnValue = (int) 0;

        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fa8 begin
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fa8 end

        return (int) $returnValue;
    }

    /**
     * Short description of method changeRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param array
     * @param array
     * @return bool
     */
    function changeRight($right_id, $right_data, $context_data = array())
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fa4 begin
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fa4 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method deleteRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function deleteRight($right_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--61674be4:106dbb8e5aa:-7f9f begin
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7f9f end

        return (bool) $returnValue;
    }

} /* end of class PMF_PermLarge */

?>/* lost code following: 
    // section 127-0-0-1--6945df47:106df4af666:-7fcf begin
    // section 127-0-0-1--6945df47:106df4af666:-7fcf end
*/