<?php

error_reporting(E_ALL);

/**
 * The userdata class provides methods to manage user information.
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
 * Creates a new user object.
 *
 * A user are recognized by the session-id using getUserBySessionId(), by his
 * using getUserById() or by his nickname (login) using getUserByLogin(). New
 * are created using createNewUser().
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-17
 * @version 0.1
 */
require_once('PMF/User.php');

/* user defined includes */
// section -64--88-1-10-1860038:10612dd0903:-7fde-includes begin
// section -64--88-1-10-1860038:10612dd0903:-7fde-includes end

/* user defined constants */
// section -64--88-1-10-1860038:10612dd0903:-7fde-constants begin
// section -64--88-1-10-1860038:10612dd0903:-7fde-constants end

/**
 * The userdata class provides methods to manage user information.
 *
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */
class PMF_UserData
{
    // --- ATTRIBUTES ---

    /**
     * Short description of attribute db
     *
     * @access public
     * @var object
     */
    var $db = null;

    /**
     * Short description of attribute userdata_entries
     *
     * @access public
     * @var array
     */
    var $userdata_entries = array();

    // --- OPERATIONS ---

    /**
     * Short description of method getUserData
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return mixed
     */
    function getUserData($field)
    {
        $returnValue = null;

        // section -64--88-1-5-15e2075:1064c3e1ce5:-7ff3 begin
        // section -64--88-1-5-15e2075:1064c3e1ce5:-7ff3 end

        return $returnValue;
    }

    /**
     * Short description of method setUserData
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param mixed
     * @return bool
     */
    function setUserData($field, $value)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-15e2075:1064c3e1ce5:-7fef begin
        // section -64--88-1-5-15e2075:1064c3e1ce5:-7fef end

        return (bool) $returnValue;
    }

    /**
     * Short description of method PMF_UserData
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return void
     */
    function PMF_UserData($user_id = 0)
    {
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd3 begin
        if ($user_id > 0) {
        	$this->db->query("SELECT * FROM ".SQLPREFIX." WHERE user_id = $user_id");
        }
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd3 end
    }

} /* end of class PMF_UserData */

?>