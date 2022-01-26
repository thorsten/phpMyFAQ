<?php

/**
 * Auth Tests
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2020-11-18
 */

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

/**
 * Class AuthTest
 *
 * @testdox Authentication should
 * @package phpMyFAQ
 */
class AuthTest extends TestCase
{
    /** @var Auth */
    protected $auth;

    /** @var Configuration */
    protected $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->auth = new Auth($this->configuration);
    }

    /**
     * @testdox return the instance of the selected encryption type class
     */
    public function testSelectEncType()
    {
        $encryptionType = $this->auth->selectEncType('bcrypt');
        $this->assertInstanceOf('phpMyFAQ\EncryptionTypes\Bcrypt', $encryptionType);
    }

    /**
     * @testdox return an empty string if no error occurred.
     */
    public function testErrorWithNoError()
    {
        $this->auth->selectEncType('bcrypt');

        $this->assertEquals('', $this->auth->error());
    }

    /**
     * @testdox return an error message if an error occurred.
     */
    public function testErrorWithError()
    {
        $this->auth->selectEncType('foobar');

        $this->assertEquals("EncryptionTypes method could not be found.\n", $this->auth->error());
    }

    /**
     * @testdox be possible for databases, HTTP and SSO
     */
    public function testSelectAuth()
    {
        $this->assertInstanceOf('phpMyFAQ\Auth\AuthDatabase', $this->auth->selectAuth('database'));
        $this->assertInstanceOf('phpMyFAQ\Auth\AuthHttp', $this->auth->selectAuth('http'));
        $this->assertInstanceOf('phpMyFAQ\Auth\AuthSso', $this->auth->selectAuth('sso'));
    }

    /**
     * @testdox sets the read only flag correctly
     */
    public function testSetReadOnly()
    {
        $this->assertFalse($this->auth->setReadOnly());
        $this->assertFalse($this->auth->setReadOnly(true));
        $this->assertTrue($this->auth->setReadOnly());
    }

    /**
     * @testdox returns an encrypted password hash
     */
    public function testEncrypt()
    {
        $this->auth->selectEncType('bcrypt');
        $hash = $this->auth->encrypt('foobar');

        $this->assertIsString($hash);
        $this->assertStringNotContainsString('foobar', $hash);
    }

    protected function tearDown(): void
    {
        $this->configuration = null;
        parent::tearDown();
    }
}
