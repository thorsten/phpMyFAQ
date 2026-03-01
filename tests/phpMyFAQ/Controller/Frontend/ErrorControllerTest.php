<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
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
    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->configuration = Configuration::getConfigurationInstance();
        } catch (\TypeError) {
            $dbHandle = new Sqlite3();
            $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
            $this->configuration = new Configuration($dbHandle);
        }

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
}
