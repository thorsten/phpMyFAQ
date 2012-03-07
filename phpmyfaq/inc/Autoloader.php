<?php
/**
 * Implements PHP autoloading capabilities to load any PMF class/interface:
 * - whose name can be splitted through "_";
 * - through a fixed lookup table (backward compatibility).
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Core
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramucia <matteo@phpmyfaq.de>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-01-07
 */
function PMF_Autoloader($class)
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

spl_autoload_register('PMF_Autoloader');