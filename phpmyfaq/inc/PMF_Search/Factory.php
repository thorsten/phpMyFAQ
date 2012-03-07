<?php
/**
 * Factory class for phpMyFAQ search classes
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Search
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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