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
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ 
 * @package   PMF_Perm
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-17
 */

/**
 * PMF_Perm
 * 
 * @category  phpMyFAQ 
 * @package   PMF_Perm
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
     * Allowed classnames for subclasses for Perm::selectPerm()
     *
     * @var array
     */
    private $perm_typemap = array(
        'basic'  => 'PermBasic',
        'medium' => 'PermMedium',
        'large'  => 'PermLarge');

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
     * @param  string $perm_level Permission level
     * @return PMF_Perm
     */
    public static function selectPerm($perm_level)
    {
        // verify selected database
        $perm       = new PMF_Perm();
        $perm_level = strtolower($perm_level);
        
        if (!isset($perm->perm_typemap[$perm_level])) {
            return $perm;
        }
        
        $classfile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'PMF_Perm' . DIRECTORY_SEPARATOR . $perm->perm_typemap[$perm_level].".php";
        if (!file_exists($classfile)) {
            return $perm;
        }
        
        // instantiate 
        $permclass = 'PMF_Perm_' . $perm->perm_typemap[$perm_level];
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
