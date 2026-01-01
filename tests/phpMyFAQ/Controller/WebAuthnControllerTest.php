<?php

/**
 * WebAuthnController Test.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Controller
 * @author    GitHub Copilot
 * @copyright 2009-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-04
 */

namespace phpMyFAQ\Controller;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Class WebAuthnControllerTest
 */
#[AllowMockObjectsWithoutExpectations]
class WebAuthnControllerTest extends TestCase
{
    private WebAuthnController $controller;
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->controller = new WebAuthnController();
        $this->request = new Request();
    }

    public function testConstructorCreatesInstance(): void
    {
        $controller = new WebAuthnController();

        $this->assertInstanceOf(WebAuthnController::class, $controller);
    }

    /**
     * @throws LoaderError
     * @throws Exception
     */
    public function testIndexReturnsResponse(): void
    {
        $response = $this->controller->index($this->request);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     */
    public function testIndexResponseHasCorrectStatusCode(): void
    {
        $response = $this->controller->index($this->request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @throws Exception
     * @throws LoaderError
     */
    public function testIndexResponseContainsContent(): void
    {
        $response = $this->controller->index($this->request);

        $this->assertNotEmpty($response->getContent());
    }
}
