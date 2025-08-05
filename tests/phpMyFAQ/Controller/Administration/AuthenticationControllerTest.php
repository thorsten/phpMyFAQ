<?php

/**
 * AuthenticationController Test.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Controller\Administration
 * @author    GitHub Copilot
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-04
 */

namespace phpMyFAQ\Controller\Administration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthenticationControllerTest
 */
class AuthenticationControllerTest extends TestCase
{
    protected function setUp(): void
    {
        // Set up proper server environment for testing
        $_SERVER['REQUEST_TIME'] = time();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/authenticate';
    }

    protected function tearDown(): void
    {
        // Clean up server variables
        unset($_SERVER['REQUEST_TIME']);
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['HTTP_USER_AGENT']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['REQUEST_URI']);
    }

    public function testConstructorCreatesInstance(): void
    {
        $controller = new AuthenticationController();

        $this->assertInstanceOf(AuthenticationController::class, $controller);
    }

    public function testAuthenticateWithPostRequest(): void
    {
        $controller = new AuthenticationController();

        $request = Request::create(
            '/authenticate',
            'POST',
            [
                'faqusername' => 'testuser',
                'faqpassword' => 'testpass',
                'faqrememberme' => false
            ],
            [], // cookies
            [], // files
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'REQUEST_TIME' => time(),
                'HTTP_USER_AGENT' => 'PHPUnit Test'
            ]
        );

        $response = $controller->authenticate($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testAuthenticateWithEmptyCredentials(): void
    {
        $controller = new AuthenticationController();

        $request = Request::create(
            '/authenticate',
            'POST',
            [
                'faqusername' => '',
                'faqpassword' => '',
                'faqrememberme' => false
            ],
            [], // cookies
            [], // files
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'REQUEST_TIME' => time()
            ]
        );

        $response = $controller->authenticate($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testAuthenticateWithRememberMeOption(): void
    {
        $controller = new AuthenticationController();

        $request = Request::create(
            '/authenticate',
            'POST',
            [
                'faqusername' => 'testuser',
                'faqpassword' => 'testpass',
                'faqrememberme' => true
            ],
            [], // cookies
            [], // files
            [
                'REMOTE_ADDR' => '192.168.1.100',
                'REQUEST_TIME' => time()
            ]
        );

        $response = $controller->authenticate($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testAuthenticateHandlesInvalidCredentials(): void
    {
        $controller = new AuthenticationController();

        $request = Request::create(
            '/authenticate',
            'POST',
            [
                'faqusername' => 'invalid',
                'faqpassword' => 'wrong',
                'faqrememberme' => false
            ],
            [], // cookies
            [], // files
            [
                'REMOTE_ADDR' => '10.0.0.1',
                'REQUEST_TIME' => time()
            ]
        );

        $response = $controller->authenticate($request);

        $this->assertInstanceOf(Response::class, $response);
    }
}
