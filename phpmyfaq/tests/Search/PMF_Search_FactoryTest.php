<?php
/**
 * Test case for PMF_Search_Database
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
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
