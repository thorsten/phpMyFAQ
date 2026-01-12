<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class TagControllerTest extends TestCase
{
    private Configuration $configuration;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->configuration = Configuration::getConfigurationInstance();
    }

    /**
     * @throws \Exception
     */
    public function testUpdateRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'id' => 1, 'tag' => 'test']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new TagController();

        $this->expectException(\Exception::class);
        $controller->update($request);
    }

    /**
     * @throws \Exception
     */
    public function testSearchRequiresAuthentication(): void
    {
        $request = new Request(['search' => 'test']);
        $controller = new TagController();

        $this->expectException(\Exception::class);
        $controller->search($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new TagController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new TagController();

        $this->expectException(\Exception::class);
        $controller->update($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode(['id' => 1, 'tag' => 'test']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new TagController();

        $this->expectException(\Exception::class);
        $controller->update($request);
    }
}
