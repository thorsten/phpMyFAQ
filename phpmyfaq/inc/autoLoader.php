<?php
/**
 * Implements PHP autoloading capabilities to load any PMF class/interface:
 * - whose name can be splitted through "_";
 * - through a fixed lookup table (backward compatibility).
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
 * @package   Core
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramucia <matteo@phpmyfaq.de>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-01-07
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * phpMyFAQ __autoload() implementation
 * 
 * @category  phpMyFAQ
 * @package   Core
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramucia <matteo@phpmyfaq.de>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-01-07
 */
function __autoload($class)
{
    // Class/interface name paranoid cleanup i.e.:
    // - avoid path traversal issues;
    // - remove any *NIX/WIN filename reserved chars.
    $invalidChars = array(
        '.', '\\', '/', ':', '*', '?', '"', '<', '>', "'", '|'
    );
    
    $class   = str_replace($invalidChars, '', $class);
    $rootDir = defined('PMF_ROOT_DIR') ? PMF_ROOT_DIR : '.';

    // Try to load the class/interface declaration using its name if splittable by '_'
    // Note: using include instead of require give us the possibility to echo failures
    $classParts      = explode('_', $class);
    $classPartsCount = count($classParts); 
    $includeDir      = $rootDir . DIRECTORY_SEPARATOR . 'inc'. DIRECTORY_SEPARATOR;
    if (2 == $classPartsCount) {
        $path = $includeDir . $classParts[1] . '.php';
    } else {
        $path = $includeDir . 'PMF_'. $classParts[1];
        for ($i = 2; $i < $classPartsCount; $i++) {
            $path .= DIRECTORY_SEPARATOR . $classParts[$i];
        }
        $path .= '.php';
    }
    
    if (file_exists($path)) {
        include $path;
    } else {
        printf("<br /><b>PMF Autoloader</b>: unable to find a suitable file declaring '%s'.", $class);
    }

    // Sanity check
    if (!class_exists($class, false) && !interface_exists($class, false)) {
        printf("<br /><b>PMF Autoloader</b>: unable to define '%s' as a class/interface.<br />", $class);
    }
}
