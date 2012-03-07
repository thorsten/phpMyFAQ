<?php
/**
 * Test case for PMF_Search_Database
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-07-06
 */

require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_Search/Factory.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/Language.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/String.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_String/Abstract.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_String/Mbstring.php';

/**
 * PMF_Category test case
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-07-06
 */
class PMF_Search_FactoryTest extends PHPUnit_Framework_TestCase
{
    private $PMF_Language;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();

        PMF_String::init('en');

        $this->PMF_Language = new PMF_Language();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->PMF_Search_Database = null;
        parent::tearDown();
    }

    public function testCreate()
    {
        $search = PMF_Search_Factory::create($this->PMF_Language, array('database' => 'sqlite'));

        $this->assertEquals(new PMF_Search_Database_Sqlite($this->PMF_Language), $search);
        $this->assertInstanceOf('PMF_Search_Database_Sqlite', $search);
    }

}
