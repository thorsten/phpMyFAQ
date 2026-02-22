<?php

/**
 * Frontend WebAuthnController Test — security guards.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Controller\Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-22
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class WebAuthnControllerTest extends TestCase
{
    private WebAuthnController $controller;
    private Configuration $configurationMock;
    private ContainerBuilder $containerMock;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->configurationMock = $this->createMock(Configuration::class);
        $this->containerMock = $this->createMock(ContainerBuilder::class);

        // Create the controller without invoking the constructor (which requires DB, etc.)
        $reflection = new ReflectionClass(WebAuthnController::class);
        $this->controller = $reflection->newInstanceWithoutConstructor();

        // Inject mocked dependencies via reflection
        $configProp = $reflection->getParentClass()->getProperty('configuration');
        $configProp->setValue($this->controller, $this->configurationMock);

        $containerProp = $reflection->getParentClass()->getProperty('container');
        $containerProp->setValue($this->controller, $this->containerMock);
    }

    public function testPrepareReturns403WhenWebAuthnDisabled(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => false,
                default => null,
            });

        $request = Request::create('/api/webauthn/prepare', 'POST', [], [], [], [], json_encode([
            'username' => 'attacker',
        ]));

        $response = $this->controller->prepare($request);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('WebAuthn support is disabled', $response->getContent());
    }

    public function testPrepareReturns403WhenRegistrationDisabled(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => true,
                'security.enableRegistration' => false,
                default => null,
            });

        $request = Request::create('/api/webauthn/prepare', 'POST', [], [], [], [], json_encode([
            'username' => 'attacker',
        ]));

        $response = $this->controller->prepare($request);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('User registration is disabled', $response->getContent());
    }

    public function testPrepareReturns401WithMissingCsrfToken(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => true,
                'security.enableRegistration' => true,
                default => null,
            });

        $sessionMock = $this->createMock(\Symfony\Component\HttpFoundation\Session\SessionInterface::class);
        $this->containerMock
            ->method('get')
            ->with('session')
            ->willReturn($sessionMock);

        $request = Request::create('/api/webauthn/prepare', 'POST', [], [], [], [], json_encode([
            'username' => 'attacker',
        ]));

        $response = $this->controller->prepare($request);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testRegisterReturns403WhenWebAuthnDisabled(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => false,
                default => null,
            });

        $request = Request::create('/api/webauthn/register', 'POST', [], [], [], [], json_encode([
            'register' => 'some-data',
        ]));

        $response = $this->controller->register($request);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('WebAuthn support is disabled', $response->getContent());
    }

    public function testRegisterReturns401WithMissingCsrfToken(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => true,
                default => null,
            });

        $sessionMock = $this->createMock(\Symfony\Component\HttpFoundation\Session\SessionInterface::class);
        $this->containerMock
            ->method('get')
            ->with('session')
            ->willReturn($sessionMock);

        $request = Request::create('/api/webauthn/register', 'POST', [], [], [], [], json_encode([
            'register' => 'some-data',
        ]));

        $response = $this->controller->register($request);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testPrepareLoginReturns403WhenWebAuthnDisabled(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => false,
                default => null,
            });

        $request = Request::create('/api/webauthn/prepare-login', 'POST', [], [], [], [], json_encode([
            'username' => 'someone',
        ]));

        $response = $this->controller->prepareLogin($request);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('WebAuthn support is disabled', $response->getContent());
    }

    public function testLoginReturns403WhenWebAuthnDisabled(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => false,
                default => null,
            });

        $request = Request::create('/api/webauthn/login', 'POST', [], [], [], [], json_encode([
            'username' => 'someone',
            'login' => 'data',
        ]));

        $response = $this->controller->login($request);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('WebAuthn support is disabled', $response->getContent());
    }
}
