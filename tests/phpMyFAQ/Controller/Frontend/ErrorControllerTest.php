<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Language;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[CoversClass(ErrorController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ErrorControllerTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = $this->createTestConfiguration();

        $language = new Language($this->configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    public function testRenderBootstrapErrorReturnsHtml500Response(): void
    {
        $response = ErrorController::renderBootstrapError('Test bootstrap failure');

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $response->headers->get('Content-Type'));
        self::assertStringContainsString('Service Unavailable', (string) $response->getContent());
        self::assertStringContainsString('Test bootstrap failure', (string) $response->getContent());
    }

    public function testInternalServerErrorReturnsHtml500Response(): void
    {
        $this->assertInstanceOf(Configuration::class, $this->configuration);

        $controller = new ErrorController();
        $response = $controller->internalServerError(Request::create('/error-test'), 'Test internal failure');

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('500', (string) $response->getContent());
        self::assertStringContainsString('Test internal failure', (string) $response->getContent());
    }

    public function testRenderMinimalErrorReturnsHtml500Response(): void
    {
        $controller = new ErrorController();
        $method = new \ReflectionMethod($controller, 'renderMinimalError');

        $response = $method->invoke($controller, 'Minimal failure');

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $response->headers->get('Content-Type'));
        self::assertStringContainsString('Minimal failure', (string) $response->getContent());
    }
}
