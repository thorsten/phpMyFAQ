<?php
/**
 * Helper class for phpMyFAQ setup
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
 * @package   PMF_Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2012-02-15
 */

/**
 * PMF_Helper_Setup
 *
 * @category  phpMyFAQ
 * @package   PMF_Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2012-02-15
 */
class PMF_Helper_Setup
{
    /**
     * Copyright
     */
    const COPYRIGHT = '&copy; 2001-%d <a href="http://www.phpmyfaq.de">phpMyFAQ Team</a>';
    
    /**
     * Twitter
     */
    const TWITTER = 'Follow us on <a href="https://twitter.com/phpMyFAQ">Twitter</a>';

    /**
     * Renders the footer for the setup
     * 
     * @return string
     */
    public function renderFooter()
    {
        return sprintf(
            '%s | %s',
            sprintf(self::COPYRIGHT, date('Y')),
            self::TWITTER
        );
    }
}