<?php

/**
 * The category image class.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2016-09-08
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Category images.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2016-09-08
 */
class PMFTest_Category_ImageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PMF_Category_Image
     */
    protected $instance;

    protected function setUp()
    {
        $this->instance = new PMF_Category_Image();
    }

}