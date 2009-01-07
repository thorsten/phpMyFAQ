<?php
/**
 * This class manages user permissions and group memberships.
 *
 * There are three possible extensions of this class: basic, medium and large
 * by the classes PMF_PermBasic, PMF_PermMedium and PMF_PermLarge. The classes
 * to allow for scalability. This means that PMF_PermMedium is an extend of
 * and PMF_PermLarge is an extend of PMF_PermMedium.
 *
 * The permission type can be selected by calling $perm = PMF_Perm(perm_type) or
 * static method $perm = PMF_Perm::selectPerm(perm_type) where perm_type is
 * 'medium' or 'large'. Both ways, a PMF_PermBasic, PMF_PermMedium or
 * is returned.
 *
 * Before calling any method, the object $perm needs to be initialised calling
 * user_id, context, context_id). The parameters context and context_id are
 * accepted, but do only matter in PMF_PermLarge. In other words, if you have a
 * or PMF_PermMedium, it does not matter if you pass context and context_id or
 * But in PMF_PermLarge, they do make a significant difference if passed, thus
 * for up- and downwards-compatibility.
 *
 * Perhaps the most important method is $perm->checkRight(right_name). This
 * checks whether the user having the user_id set with $perm->setPerm()
 *
 * The permission object is added to a user using the user's addPerm() method.
 * a single permission-object is allowed for each user. The permission-object is
 * in the user's $perm variable. Permission methods are performed using the
 * variable (e.g. $user->perm->method() ).
 *
 * @package     phpMyFAQ 
 * @author      Lars Tiedemann <php@larstiedemann.de>
 * @since       2005-09-17
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
class PMF_User_Perm
{
    // --- ATTRIBUTES ---

    /**
     * Database object created by PMF_Db
     *
     * @access private
     * @var object
     */
    var $_db = null;

    /**
     * Allowed classnames for subclasses for Perm::selectPerm()
     *
     * @access private
     * @var array
     */
    var $_perm_typemap = array(
        'basic'     => 'PermBasic',
        'medium'    => 'PermMedium',
        'large'     => 'PermLarge'
    );

    /**
     * Set TRUE if valid database and user-ID are given
     *
     * @access private
     * @var bool
     */
    var $_initialized = false;

    /**
     * Selects a subclass of PMF_Perm. 
     *
     * selectPerm() returns an instance of a subclass of PMF_Perm. perm_level
     * which subclass is returned. Allowed values and corresponding classnames
     * defined in perm_typemap.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return object
     */
    static function selectPerm($perm_level)
    {
        // verify selected database
        $perm = new PMF_User_Perm();
        $perm_level = strtolower($perm_level);
        if (!isset($perm->_perm_typemap[$perm_level])) {
            return $perm;
        }
        $classfile = dirname(__FILE__)."/".$perm->_perm_typemap[$perm_level].".php";
        if (!file_exists($classfile)) {
            return $perm;
        }
        // instantiate 
        $permclass = "PMF_User_".$perm->_perm_typemap[$perm_level];
        if (!class_exists($permclass))
            require_once $classfile;
        $perm = new $permclass();
        return $perm;
    }

    /**
     * converts a boolean into a corresponding integer: 0 or 1.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param bool
     * @return int
     */
    function bool_to_int($val)
    {
        if (!$val) 
            return (int) 0;
        return (int) 1;
    }

    /**
     * converts an integer into the corresponding boolean value: true or false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function int_to_bool($val)
    {
        if (!$val or $val == 0) 
            return false;
        return true;
    }

    /**
     * initalizes the object. 
     *
     * PMF_Perm needs a database access in order to work. db must be a valid
     * object.
     *
     * User specific permissions can only be checked and set, if a valid user-ID
     * given. However, for administration purposes, user_id may be omitted.
     *
     * Context information context and context_id only work with
     * See the documentation of PMF_PermLarge for context description.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @param string
     * @param int
     * @return bool
     */
    function addDb(PMF_IDB_Driver $db, $context = '', $context_id = 0)
    {
        $this->_db = $db;
        $this->_initialized = true;
        return true;
    }

} /* end of class PMF_Perm */
