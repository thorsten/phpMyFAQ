<?php
/**
 * Test case for PMF_Linkverifier
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

/**
 * PMF_LinkverifierTest
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-29
 */
class PMF_LinkverifierTest extends PHPUnit_Framework_TestCase
{
    /** @var PMF_DB_Sqlite3  */
    private $dbHandle;

    /** @var  PMF_Linkverifier */
    private $linkVerifier;

    /** @var  PMF_Configuration */
    private $config;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();

        PMF_String::init('en');

        $config = new stdClass();
        $config->config_name = 'foo';
        $config->config_value = '';

        $this->dbHandle = $this->getMockBuilder('PMF_DB_Sqlite3')->disableOriginalConstructor()->getMock();
        $this->dbHandle->method('fetchAll')->willReturn([$config]);

        $this->config       = new PMF_Configuration($this->dbHandle);
        $this->linkVerifier = new PMF_Linkverifier($this->config);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->linkVerifier = null;
        parent::tearDown();
    }

    public function testIsReady()
    {
        $this->assertFalse($this->linkVerifier->isReady());
    }

    public function testParseString()
    {
        $html = '<a href="http://www.example.org">Test</a>';
        $this->assertEquals(1, $this->linkVerifier->parseString($html));

        $html .= '<img src="http://www.example.com/foo.png">';
        $this->assertEquals(2, $this->linkVerifier->parseString($html));
    }
}