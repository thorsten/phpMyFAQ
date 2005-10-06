<?php

error_reporting(E_ALL);

/**
 * The medium permission class provides group rights.
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
 * This class manages a single group.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-18
 * @version 0.1
 */
require_once('PMF/Group.php');

/**
 * The basic permission class provides user rights.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-17
 * @version 0.1
 */
require_once('PMF/PermBasic.php');

/* user defined includes */
// section 127-0-0-1-17ec9f7:105b52d5117:-7fde-includes begin
// section 127-0-0-1-17ec9f7:105b52d5117:-7fde-includes end

/* user defined constants */
// section 127-0-0-1-17ec9f7:105b52d5117:-7fde-constants begin
// section 127-0-0-1-17ec9f7:105b52d5117:-7fde-constants end

/**
 * The medium permission class provides group rights.
 *
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */
class PMF_PermMedium
    extends PMF_PermBasic
{
    // --- ATTRIBUTES ---

    /**
     * Short description of attribute group_container
     *
     * @access public
     * @var array
     */
    var $group_container = array();

    // --- OPERATIONS ---

    /**
     * Short description of method checkGroupRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param string
     * @param int
     * @return string
     */
    function checkGroupRight($action, $object, $object_id)
    {
        $returnValue = (string) '';

        // section -64--88-1-5-15e2075:10637248df4:-7ffa begin
        // section -64--88-1-5-15e2075:10637248df4:-7ffa end

        return (string) $returnValue;
    }

    /**
     * Short description of method addGroupRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @param string
     * @return void
     */
    function addGroupRight($right, $group)
    {
        // section -64--88-1-5-15e2075:10637248df4:-7fc8 begin
        // section -64--88-1-5-15e2075:10637248df4:-7fc8 end
    }

    /**
     * Short description of method deleteGroupRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param string
     * @param string
     * @param int
     * @return bool
     */
    function deleteGroupRight($group, $action, $object, $object_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-15e2075:10637248df4:-7fc3 begin
        // section -64--88-1-5-15e2075:10637248df4:-7fc3 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method getGroupRights
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return array
     */
    function getGroupRights()
    {
        $returnValue = array();

        // section -64--88-1-5-15e2075:10637248df4:-7fbe begin
        // section -64--88-1-5-15e2075:10637248df4:-7fbe end

        return (array) $returnValue;
    }

    /**
     * Short description of method saveGroupRights
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function saveGroupRights()
    {
        // section -64--88-1-5-15e2075:10637248df4:-7fb9 begin
        // section -64--88-1-5-15e2075:10637248df4:-7fb9 end
    }

    /**
     * Short description of method checkRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param string
     * @param int
     * @return bool
     */
    function checkRight($action, $object, $object_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-15e2075:10637248df4:-7fb4 begin
        // section -64--88-1-5-15e2075:10637248df4:-7fb4 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method getRights
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return array
     */
    function getRights()
    {
        $returnValue = array();

        // section -64--88-1-5-15e2075:10637248df4:-7fa3 begin
        // section -64--88-1-5-15e2075:10637248df4:-7fa3 end

        return (array) $returnValue;
    }

    /**
     * Short description of method saveRights
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function saveRights()
    {
        // section -64--88-1-5-15e2075:10637248df4:-7fa1 begin
        // section -64--88-1-5-15e2075:10637248df4:-7fa1 end
    }

    /**
     * Short description of method isInGroup
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return bool
     */
    function isInGroup($group_name)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-a522a6:10659761d86:-7ffe begin
        // section -64--88-1-5-a522a6:10659761d86:-7ffe end

        return (bool) $returnValue;
    }

    /**
     * Short description of method PMF_PermMedium
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_PermMedium()
    {
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd5 begin
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd5 end
    }

} /* end of class PMF_PermMedium */

?>