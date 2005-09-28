<?php

error_reporting(E_ALL);

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
 * @package PMF
 * @since 2005-09-17
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
// section -64--88-1-5-15e2075:10637248df4:-7fd0-includes begin
// section -64--88-1-5-15e2075:10637248df4:-7fd0-includes end

/* user defined constants */
// section -64--88-1-5-15e2075:10637248df4:-7fd0-constants begin
// section -64--88-1-5-15e2075:10637248df4:-7fd0-constants end

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
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-17
 * @version 0.1
 */
class PMF_Perm
{
    // --- ATTRIBUTES ---

    /**
     * Short description of attribute db
     *
     * @access private
     * @var object
     */
    var $_db = null;

    // --- OPERATIONS ---

    /**
     * Short description of method PMF_Perm
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_Perm()
    {
        // section -64--88-1-5-5e0b50c5:10665348267:-7fd1 begin
        // section -64--88-1-5-5e0b50c5:10665348267:-7fd1 end
    }

} /* end of class PMF_Perm */

?>