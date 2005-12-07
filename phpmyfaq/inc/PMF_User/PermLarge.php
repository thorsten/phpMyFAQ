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
//require_once('PMF/PermMedium.php');

/* user defined includes */
// section 127-0-0-1-17ec9f7:105b52d5117:-7fdd-includes begin
require_once dirname(__FILE__).'/PermMedium.php';
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
     * @return void
     */
    function PMF_PermLarge()
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
        if (!$this->_initialized)
        	return false;
        // get right id
        $res = $this->_db->query("
            SELECT
                ".SQLPREFIX."right.right_id AS right_id
            FROM
                ".SQLPREFIX."right,
                ".SQLPREFIX."rightcontext
            WHERE 
                ".SQLPREFIX."right.name              = '".$name."' AND
                ".SQLPREFIX."rightcontext.context    = '".$this->_context."' AND
                ".SQLPREFIX."rightcontext.context_id = '".$this->_context_id."'
        ");
        // return result
        if ($this->_db->num_rows($res) != 1)
            return 0;
        $row = $this->_db->fetch_assoc($res);
        return $row['right_id'];
        // section -64--88-1-10--77ac1b05:106d99eac38:-7fb7 end

        return (int) $returnValue;
    }

    /**
     * Short description of method setContext
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param int
     * @return void
     */
    function setContext($context, $context_id = 0)
    {
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fcf begin
        if (!PMF_User::checkDb($db))
            return false;
        $this->_db         = $db;
        $this->_user_id    = $user_id;
        $this->_context    = $context;
        $this->_context_id = $context_id;
        $this->_initialized = true;
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fcf end
    }

    /**
     * Short description of method resetContext
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function resetContext()
    {
        // section -64--88-1-10--73b3bdb4:106e40c6470:-7fdd begin
        $this->_db = null;
        $this->_user_id = 0;
        $this->_context    = '';
        $this->_context_id = 0;
        $this->_initialized = false;
        // section -64--88-1-10--73b3bdb4:106e40c6470:-7fdd end
    }

} /* end of class PMF_PermLarge */

?>
