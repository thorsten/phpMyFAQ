<?php
/**
 * Implements PHP autoloading capabilities to load any PMF class/interface:
 * - whose name can be splitted through "_";
 * - through a fixed lookup table (backward compatibility).
 *
 * PHP Version 5.3
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
 * phpMyFAQ spl_autoload_register() implementation
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
class PMF_Autoloader
{
    /**
     * Constructor
     *
     * @return PMF_Autoloader
     */
    public function __construct()
    {
        spl_autoload_register(array($this, 'loader'));
    }

    /**
     * Class loader
     *
     * @param string $className
     *
     * @return void
     */
    private function loader($className)
    {
        // Class/interface name paranoid cleanup i.e.:
        // - avoid path traversal issues;
        // - remove any *NIX/WIN filename reserved chars.
        $invalidChars = array(
            '.', '\\', '/', ':', '*', '?', '"', '<', '>', "'", '|'
        );

        $className = str_replace($invalidChars, '', $className);
        $rootDir   = defined('PMF_ROOT_DIR') ? PMF_ROOT_DIR : '.';

        // Try to load the class/interface declaration using its name if splittable by '_'
        // Note: using include instead of require give us the possibility to echo failures
        $classParts      = explode('_', $className);
        $classPartsCount = count($classParts);
        $includeDir      = $rootDir . DIRECTORY_SEPARATOR . 'inc'. DIRECTORY_SEPARATOR;
        if (2 == $classPartsCount) {
            $path = $includeDir . $classParts[1] . '.php';
        } else {
            $path = $includeDir . $classParts[1];
            for ($i = 2; $i < $classPartsCount; $i++) {
                $path .= DIRECTORY_SEPARATOR . $classParts[$i];
            }
            $path .= '.php';
        }

        if (file_exists($path)) {
            include $path;
        } else {
            printf(
                "<br /><b>PMF Autoloader</b>: unable to find a suitable file declaring '%s'.",
                $className
            );
        }

        // Sanity check
        if (!class_exists($className, false) && !interface_exists($className, false)) {
            printf(
                "<br /><b>PMF Autoloader</b>: unable to define '%s' as a class/interface.<br />",
                $className
            );
        }
    }
}

$faqAutoloader = new PMF_Autoloader();