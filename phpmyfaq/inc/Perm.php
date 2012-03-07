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
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 * @package   PMF_Perm
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-17
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Perm
 *
 * @category  phpMyFAQ 
 * @package   PMF_Perm
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-17
 */
class PMF_Perm
{
    /**
     * Database object created by PMF_Db
     *
     * @var PMF_Db_Driver
     */
    protected $db = null;

    /**
     * Constructor
     *
     * @return void
     */
    protected function __construct()
    {
        $this->db = PMF_Db::getInstance(); 
    }
    
    /**
     * Selects a subclass of PMF_Perm. 
     *
     * selectPerm() returns an instance of a subclass of PMF_Perm. perm_level
     * which subclass is returned. Allowed values and corresponding classnames
     * defined in perm_typemap.
     *
     * @param  string $permLevel Permission level
     * @return PMF_Perm
     */
    public static function selectPerm($permLevel)
    {
        // verify selected database
        $perm      = new PMF_Perm();
        $permLevel = ucfirst(strtolower($permLevel));
        
        if (!isset($permLevel)) {
            return $perm;
        }
        
        $classfile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'PMF_Perm' . DIRECTORY_SEPARATOR . $permLevel . '.php';
        if (!file_exists($classfile)) {
            return $perm;
        }

        $permclass = 'PMF_Perm_' . $permLevel;
        $perm      = new $permclass();

        return $perm;
    }
    
    /**
     * Renders a select box for permission types
     *
     * @param  string $current Selected option
     * @return string
     */
    public static function permOptions($current)
    {
        $options = array('basic', 'medium');
        $output  = '';

        foreach ($options as $value) {
            $output .= sprintf('<option value="%s"%s>%s</option>',
                $value,
                ($value == $current) ? ' selected="selected"' : '',
                $value);
        }

        return $output;
    }
}
