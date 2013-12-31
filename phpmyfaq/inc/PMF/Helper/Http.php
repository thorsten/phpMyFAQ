<?php
/**
 * HTTP Helper class for phpMyFAQ
 *
 * PHP Version 5.4.0
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Florian Anderiasch <florian@phpmyfaq.de>
 * @copyright 2009-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-13
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Helper_Http
 *
 * @category  phpMyFAQ
 * @package   PMF_Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Florian Anderiasch <florian@phpmyfaq.de>
 * @copyright 2009-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-13
 * @deprecated
 */
class PMF_Helper_Http extends PMF_Helper
{
    /**
     * Content type
     *
     * @var string
     */
    private $contentType = '';

    /**
     * Setter for content type
     *
     * @param string $contentType Content type
     *
     * @return void
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * Returns the Header
     *
     * @return void
     */
    public function addHeader()
    {
        header('Expires: Thu, 07 Apr 1977 14:47:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Vary: Negotiate,Accept');
        header('Content-type: ' . $this->contentType);
    }
}