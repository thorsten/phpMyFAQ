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
class GlossaryControllerTest extends TestCase
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
    public function testFetchRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new GlossaryController();

        $this->expectException(\Exception::class);
        $controller->fetch($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'id' => 1, 'lang' => 'en']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new GlossaryController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testCreateRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrf' => 'test-token',
            'language' => 'en',
            'item' => 'Test',
            'definition' => 'Definition',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new GlossaryController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrf' => 'test-token',
            'id' => 1,
            'lang' => 'en',
            'item' => 'Test',
            'definition' => 'Definition',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new GlossaryController();

        $this->expectException(\Exception::class);
        $controller->update($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new GlossaryController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testCreateWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new GlossaryController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new GlossaryController();

        $this->expectException(\Exception::class);
        $controller->update($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode(['id' => 1, 'lang' => 'en']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new GlossaryController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }
}
