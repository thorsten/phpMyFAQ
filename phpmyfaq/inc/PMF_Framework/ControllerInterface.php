<?php
/**
 * Controller Interface for phpMyFAQ Framework
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Framework
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-09-30
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Framework_ControllerInterface
 *
 * @category  phpMyFAQ
 * @package   PMF_Framework
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-09-30
 */
interface PMF_Framework_ControllerInterface
{
    /**
     * @abstract
     *
     * @param PMF_Framework_Request $request
     * @param PMF_Framework_Response $response
     *
     * @return void
     */
    public function run(PMF_Framework_Request $request, PMF_Framework_Response $response);
}