<?php
/**
 * The PMF_Cache_Apc class implements the APC cache service functionality
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Cache_Apc
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-10-01
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Cache_Apc
 *
 * @category  phpMyFAQ
 * @package   PMF_Cache_Apc
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-10-01
 */
class PMF_Cache_Apc extends PMF_Cache_Service
{
    /**
     * @var PMF_Cache_Apc
     */
    protected $instance = NULL;

    /**
     * Constructor.
     *
     * @param array $config Cache configuration
     *
     * @return PMF_Cache_Apc
     */
    public function __construct(array $config)
    {

    }

    /**
     * Clear all cached article related items.
     *
     * @param intereg $id Article id
     *
     * @return void
     */
    public function clearArticle($id)
    {

    }

    public function clearAll()
    {
        
    }

}
