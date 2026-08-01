<?php

/**
 * API RegistrationController Test — registration-enabled guard.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Controller\Api
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-08-01
 */

namespace phpMyFAQ\Controller\Api;

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
class RegistrationControllerTest extends TestCase
{
    private RegistrationController $controller;
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

        // Create the controller without invoking the constructor (which requires DB, API check, etc.)
        $reflection = new ReflectionClass(RegistrationController::class);
        $this->controller = $reflection->newInstanceWithoutConstructor();

        // Inject mocked dependencies via reflection
        $configProp = $reflection->getParentClass()->getProperty('configuration');
        $configProp->setValue($this->controller, $this->configurationMock);

        $containerProp = $reflection->getParentClass()->getProperty('container');
        $containerProp->setValue($this->controller, $this->containerMock);
    }

    public function testCreateReturns403WhenRegistrationDisabled(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'api.apiClientToken' => 'valid-token',
                'security.enableRegistration' => false,
                default => null,
            });

        // hasValidToken() reads the token from the PHP globals via Request::createFromGlobals().
        $_SERVER['HTTP_X_PMF_TOKEN'] = 'valid-token';

        $request = Request::create('/api/v3.1/register', 'POST', [], [], [], [], json_encode([
            'username' => 'regbypass',
            'fullname' => 'Reg Bypass',
            'email' => 'regbypass@example.com',
            'is-visible' => false,
        ]));

        try {
            $response = $this->controller->create($request);
        } finally {
            unset($_SERVER['HTTP_X_PMF_TOKEN']);
        }

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('User registration is disabled', $response->getContent());
    }
}
