<?php
/**
 * Implements PHP autoloading capabilities to load any PMF class/interface:
 * - whose name can be splitted through "_";
 * - through a fixed lookup table (backward compatibility).
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

function __autoload($class)
{
    // Class/interface name paranoid cleanup i.e.:
    // - avoid path traversal issues;
    // - remove any *NIX/WIN filename reserved chars.
    $invalidChars = array(
        '.', '\\', '/', ':', '*', '?', '"', '<', '>', "'", '|'
    );
    $class = str_replace($invalidChars, '', $class);

    $rootDir = defined('PMF_ROOT_DIR') ? PMF_ROOT_DIR : '.';

    // Note: supposing a class<->file approach we can avoid the use of "_once".
    switch($class) {
        /* Fixed lookup table (backward compatibility) - START HERE */
        case 'DocBook_XML_Export':
            @include $rootDir.'/inc/PMF_Export/DocBook.php';
            break;

        case 'FPDF':
            @include $rootDir.'/inc/libs/fpdf.php';
            break;

        case 'PDF':
            @include $rootDir.'/inc/PMF_Export/Pdf.php';
            break;

        case 'PMF_IDB_Driver':
            @include $rootDir.'/inc/PMF_DB/Driver.php';
            break;
        /* Fixed lookup table (backward compatibility) - END HERE */

        default:
            // Try to load the class/interface declaration using its name if splittable by '_'
            // Note: using include instead of require give us the possibility to echo failures
            $classParts = explode('_', $class);
            if (2 == count($classParts)) {
                @include $rootDir.'/inc/'. $classParts[1] . '.php';
            } else if (3 == count($classParts)) {
                @include $rootDir.'/inc/PMF_'. $classParts[1] . '/' . $classParts[2] . '.php';
            } else {
                echo("<br /><b>PMF Autoloader</b>: unable to find a suitable file declaring '$class'.");
            }
    }

    // Sanity check
    if (
            !class_exists($class, false)
        && !interface_exists($class, false)
        ) {
        echo("<br /><b>PMF Autoloader</b>: unable to define '$class' as a class/interface.<br />");
    }
}
