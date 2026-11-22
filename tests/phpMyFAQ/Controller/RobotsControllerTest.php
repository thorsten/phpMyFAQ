<?php

namespace phpMyFAQ\Controller;

use phpMyFAQ\Setup\Installation\DefaultDataSeeder;
use phpMyFAQ\Strings;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(RobotsController::class)]
#[UsesNamespace('phpMyFAQ')]
class RobotsControllerTest extends TestCase
{
    use TestDatabaseTrait;

    private Environment $twig;
    private RobotsController $controller;

    /**
     * @throws Exception
     * @throws \phpMyFAQ\Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestConfiguration();
        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->twig = $this->createStub(Environment::class);
        $this->controller = new RobotsController();
    }

    /**
     * @throws \Exception
     */
    public function testIndex(): void
    {
        $response = $this->controller->index();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('text/plain', $response->headers->get('Content-Type'));
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        // The seeded text carries real line breaks; the former single-quoted value served one line.
        $this->assertSame(DefaultDataSeeder::ROBOTS_TEXT, $response->getContent());
        $this->assertStringContainsString("\nDisallow: /admin/\n", (string) $response->getContent());
    }
}
