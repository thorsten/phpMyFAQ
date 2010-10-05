<?php
/**
 * Factory class for phpMyFAQ search classes
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
 * @package   PMF_Search
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-07-06
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Search_Factory
 *
 * @category  phpMyFAQ
 * @package   PMF_Search
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-07-06
 */
class PMF_Search_Factory
{
    /**
     * Factory for generating search instances
     * 
     * @param PMF_Language $language      Language object
     * @param array        $searchHandler Array with informations about search
     *                                    handlers, e.g.
     *                                    array('database' => 'mysql')
     * 
     * @return PMF_Search_Abstract
     */
    public static function create(PMF_Language $language, Array $searchHandler)
    {
        $searchClass = sprintf('PMF_Search_%s_%s',
            ucfirst(key($searchHandler)),
            ucfirst(current($searchHandler)));
        
        return new $searchClass($language);
    }
}