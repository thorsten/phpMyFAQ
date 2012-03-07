<?php
/**
 * Helper class for phpMyFAQ setup
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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