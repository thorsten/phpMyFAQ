<?php
/**
 * The main phpMyFAQ instances class
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
 * @package   PMF_Instance
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2012-02-20
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Instance
 *
 * @category  phpMyFAQ
 * @package   PMF_Instance
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2012-02-20
 */
class PMF_Configuration_Instance extends PMF_Configuration
{
    /**
     * Tablename
     */
    const TABLE_FAQINSTANCES_CONFIG = 'faqinstances_config';

    /**
     * Constructor
     *
     * @return PMF_Instance
     */
    public function __construct(PMF_DB_Driver $database)
    {
        $this->_db = $database;
    }


}