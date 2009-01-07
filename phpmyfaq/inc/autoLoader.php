<?php
/**
 * Implements PHP autoloading capabilities to load any PMF class/interface whose name can be splitted through "_".
 *
 * @package     phpMyFAQ
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Matteo Scaramucia <matteo@phpmyfaq.de>
 * @since       2009-01-07
 * @copyright   (c) 2009 phpMyFAQ Team
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

if (
       !defined('IS_VALID_PHPMYFAQ')
    && !defined('IS_VALID_PHPMYFAQ_ADMIN')
    ) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

function __autoload($class)
{
    $classParts = explode('_', $class);
    $rootDir = defined('PMF_ROOT_DIR') ? PMF_ROOT_DIR : '.';

    // Try to load the class/interface declaration
    // Note: supposing a class<->file approach we can avoid the use of "_once".
    // Note: using include instead of require give us the possibility to echo failures
    if (2 == count($classParts)) {
        @include $rootDir.'/inc/'. $classParts[1] . '.php';
    } else if (3 == count($classParts)) {
        @include $rootDir.'/inc/PMF_'. $classParts[1] . '/' . $classParts[2] . '.php';
    } else {
        echo("<br /><b>PMF Autoloader</b>: unable to find a suitable file declaring '$class'.");
    }

    // Sanity check
    if (
            !class_exists($class, false)
        && !interface_exists($class, false)
        ) {
        echo("<br /><b>PMF Autoloader</b>: unable to define '$class' as a class/interface.<br />");
    }
}
