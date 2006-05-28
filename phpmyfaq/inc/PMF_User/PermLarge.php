<?php

/**
 * The large permission class is not yet implemented in phpMyFAQ.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */

/* user defined includes */

/**
 * The medium permission class provides group rights.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-18
 * @version 0.1
 */
require_once dirname(__FILE__).'/PermMedium.php';

/* user defined constants */

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
     * PMF_PermLarge
     *
     * Constructor.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_PermLarge()
    {
    }

    /**
     * __destruct
     *
     * Destructor
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function __destruct()
    {
    }

    /**
     * getRightId
     *
     * Returns the right-ID of the right with the name $name.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return int
     */
    function getRightId($name)
    {
        if (!$this->_initialized)
        	return false;
        // get right id
        $res = $this->_db->query("
            SELECT
                ".PMF_USER_SQLPREFIX."right.right_id AS right_id
            FROM
                ".PMF_USER_SQLPREFIX."right,
                ".PMF_USER_SQLPREFIX."rightcontext
            WHERE 
                ".PMF_USER_SQLPREFIX."right.name              = '".$name."' AND
                ".PMF_USER_SQLPREFIX."rightcontext.context    = '".$this->_context."' AND
                ".PMF_USER_SQLPREFIX."rightcontext.context_id = ".$this->_context_id
        );
        // return result
        if ($this->_db->num_rows($res) != 1)
            return 0;
        $row = $this->_db->fetch_assoc($res);
        return $row['right_id'];
    }

    /**
     * setContext
     *
     * Sets the context.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param int
     * @return void
     */
    function setContext($context, $context_id = 0)
    {
        $this->_context    = $context;
        $this->_context_id = $context_id;
    }

    /**
     * resetContext
     *
     * Resets the context.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function resetContext()
    {
        $this->_context    = '';
        $this->_context_id = 0;
    }

} /* end of class PMF_PermLarge */

?>
