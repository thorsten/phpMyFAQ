<?php

error_reporting(E_ALL);

/**
 * The basic permission class provides user rights.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-17
 * @version 0.1
 */

if (0 > version_compare(PHP_VERSION, '4')) {
    die('This file was generated for PHP 4');
}

/**
 * This class manages user permissions and group memberships.
 *
 * There are three possible extensions of this class: basic, medium and large
 * by the classes PMF_PermBasic, PMF_PermMedium and PMF_PermLarge. The classes
 * to allow for scalability. This means that PMF_PermMedium is an extend of
 * and PMF_PermLarge is an extend of PMF_PermMedium. The PMF_Perm class itself
 * not provide any methods, but a single property: the database object
 * Using this database connection, the permission-object may perform database
 * The permission object is added to a user using the user's addPerm() method.
 * a single permission-object is allowed for each user. The permission-object is
 * in the user's $perm variable. Permission methods are performed using the
 * variable (e.g. $user->perm->method() ).
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-17
 * @version 0.1
 */
require_once('PMF/Perm.php');

/**
 * This class provides methods to manage single permissions.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-18
 * @version 0.1
 */
require_once('PMF/Right.php');

/* user defined includes */
// section 127-0-0-1-17ec9f7:105b52d5117:-7fe2-includes begin
// section 127-0-0-1-17ec9f7:105b52d5117:-7fe2-includes end

/* user defined constants */
// section 127-0-0-1-17ec9f7:105b52d5117:-7fe2-constants begin
// section 127-0-0-1-17ec9f7:105b52d5117:-7fe2-constants end

/**
 * The basic permission class provides user rights.
 *
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-17
 * @version 0.1
 */
class PMF_PermBasic
    extends PMF_Perm
{
    // --- ATTRIBUTES ---

    /**
     * Short description of attribute right_container
     *
     * @access private
     * @var array
     */
    var $_right_container = array();

    // --- OPERATIONS ---

    /**
     * Short description of method checkUserRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param string
     * @param int
     * @return bool
     */
    function checkUserRight($action, $object, $object_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-15e2075:10637248df4:-7ffe begin
        // section -64--88-1-5-15e2075:10637248df4:-7ffe end

        return (bool) $returnValue;
    }

    /**
     * Short description of method addUserRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param string
     * @param int
     * @return bool
     */
    function addUserRight($action, $object, $object_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-15e2075:10637248df4:-7fe0 begin
        // section -64--88-1-5-15e2075:10637248df4:-7fe0 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method deleteUserRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param string
     * @param int
     * @return bool
     */
    function deleteUserRight($action, $object, $object_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-15e2075:10637248df4:-7fdc begin
        // section -64--88-1-5-15e2075:10637248df4:-7fdc end

        return (bool) $returnValue;
    }

    /**
     * Short description of method getUserRights
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return array
     */
    function getUserRights()
    {
        $returnValue = array();

        // section -64--88-1-5-15e2075:10637248df4:-7fa5 begin
        // section -64--88-1-5-15e2075:10637248df4:-7fa5 end

        return (array) $returnValue;
    }

    /**
     * Short description of method saveUserRights
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function saveUserRights()
    {
        // section -64--88-1-5-a522a6:106564ad215:-7fe8 begin
        // section -64--88-1-5-a522a6:106564ad215:-7fe8 end
    }

    /**
     * Short description of method PMF_PermBasic
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_PermBasic()
    {
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd7 begin
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd7 end
    }

} /* end of class PMF_PermBasic */

?>