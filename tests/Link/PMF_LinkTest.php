<?php
/**
 * Test case for PMF_Link
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-29
 */


require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/DB/Driver.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/DB/Sqlite3.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/Exception.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/Configuration.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/Link.php';

/**
 * PMF_LinkTest
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-29
 */
class PMF_LinkTest extends PHPUnit_Framework_TestCase
{
    private $dbHandle;
    private $PMF_Link;
    private $PMF_Configuration;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();

        PMF_String::init('en');

        $_SERVER['HTTP_HOST'] = 'faq.example.org';

        $this->dbHandle = new PMF_DB_Sqlite3();
        $this->PMF_Configuration = new PMF_Configuration($this->dbHandle);
        $this->PMF_Configuration->config['security.useSslOnly'] = 'true';
        $this->PMF_Link = new PMF_Link('https://faq.example.org/my-test-faq/', $this->PMF_Configuration);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->PMF_Link = null;
        parent::tearDown();
    }

    public function testGetSystemScheme()
    {
        $this->assertEquals('https://', $this->PMF_Link->getSystemScheme());
    }

}
