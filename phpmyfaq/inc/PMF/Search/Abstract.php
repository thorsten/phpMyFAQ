<?php
/**
 * Abstract class for phpMyFAQ search classes
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Search
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-06-06
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Search_Exception
 *
 * @category  phpMyFAQ
 * @package   Search
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-06-06
 */
class PMF_Search_Abstract
{
    /**
     * @var PMF_Configuration
     */
    protected $_config = null;
    
    /**
     * Resultset
     *
     * @var resource
     */
    protected $resultSet = false;
    
    /**
     * Constructor
     *
     * @param PMF_Configuration
     *
     * @return PMF_Search_Abstract
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
    }
}