<?php

/**
 * This class manages user permissions and group memberships.
 *
 * There are three possible extensions of this class: basic, medium and large
 * by the classes PMF_PermBasic, PMF_PermMedium and PMF_PermLarge. The classes
 * to allow for scalability. This means that PMF_PermMedium is an extend of
 * and PMF_PermLarge is an extend of PMF_PermMedium.
 *
 * The permission type can be selected by calling $perm = Perm(perm_type) or
 * static method $perm = Perm::selectPerm(perm_type) where perm_type is
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
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 *
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-17
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Perm.
 *
 * @category  phpMyFAQ 
 *
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-17
 */
class PMF_Perm
{
    /**
     * Configuration object.
     *
     * @var PMF_Configuration
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * selectPerm() returns an instance of a subclass of Perm. $permLevel
     * which subclass is returned.
     *
     * @param string            $permLevel Permission level
     * @param PMF_Configuration $config
     *
     * @return PMF_Perm
     */
    public static function selectPerm($permLevel, PMF_Configuration $config)
    {
        if (isset($permLevel)) {
            $permclass = 'PMF_Perm_'.ucfirst(strtolower($permLevel));
            if (class_exists($permclass)) {
                return new $permclass($config);
            }
        }

        return new self($config);
    }

    /**
     * Renders a select box for permission types.
     *
     * @param string $current Selected option
     *
     * @return string
     */
    public static function permOptions($current)
    {
        $options = array('basic', 'medium');
        $output = '';

        foreach ($options as $value) {
            $output .= sprintf('<option value="%s"%s>%s</option>',
                $value,
                ($value == $current) ? ' selected="selected"' : '',
                $value);
        }

        return $output;
    }
}
