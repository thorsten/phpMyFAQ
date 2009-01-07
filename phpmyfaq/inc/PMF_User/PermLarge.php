<?php
/**
 * The large permission class is not yet implemented in phpMyFAQ.
 *
 * @package     phpMyFAQ 
 * @author      Lars Tiedemann <php@larstiedemann.de>
 * @since       2005-09-18
 * @copyright   (c) 2005-2009 phpMyFAQ Team
 * @version     SVN: $Id$ 
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */
class PMF_User_PermLarge extends PMF_User_PermMedium
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
                ".SQLPREFIX."faqright.right_id AS right_id
            FROM
                ".SQLPREFIX."faqright,
                ".SQLPREFIX."faqrightcontext
            WHERE
                ".SQLPREFIX."faqright.name              = '".$name."' AND
                ".SQLPREFIX."faqrightcontext.context    = '".$this->_context."' AND
                ".SQLPREFIX."faqrightcontext.context_id = ".$this->_context_id
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

}
