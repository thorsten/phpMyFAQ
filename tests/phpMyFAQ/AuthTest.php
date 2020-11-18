<?php

/**
 * Auth Tests
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2020-11-18
 */

use phpMyFAQ\Auth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    /** @var Auth */
    protected $Auth;

    /** @var Configuration */
    protected $Configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->Configuration = new Configuration($dbHandle);
        $this->Auth = new Auth($this->Configuration);
    }

    protected function tearDown(): void
    {
        $this->Configuration = null;
        parent::tearDown();
    }

    public function testSelectAuth()
    {
        $this->assertInstanceOf('phpMyFAQ\Auth\AuthDatabase', $this->Auth->selectAuth('database'));
        $this->assertInstanceOf('phpMyFAQ\Auth\AuthHttp', $this->Auth->selectAuth('http'));
        $this->assertInstanceOf('phpMyFAQ\Auth\AuthSso', $this->Auth->selectAuth('sso'));
    }
}
