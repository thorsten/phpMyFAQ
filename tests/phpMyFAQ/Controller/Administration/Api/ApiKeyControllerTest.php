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
class ApiKeyControllerTest extends TestCase
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
    public function testListRequiresAuthentication(): void
    {
        $controller = new ApiKeyController();

        $this->expectException(\Exception::class);
        $controller->list();
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    public function testCreateRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrf' => 'token'], JSON_THROW_ON_ERROR));
        $controller = new ApiKeyController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    public function testUpdateRequiresAuthentication(): void
    {
        $request = new Request([], [], ['id' => 1], [], [], [], json_encode(['csrf' => 'token'], JSON_THROW_ON_ERROR));
        $controller = new ApiKeyController();

        $this->expectException(\Exception::class);
        $controller->update($request);
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    public function testDeleteRequiresAuthentication(): void
    {
        $request = new Request([], [], ['id' => 1], [], [], [], json_encode(['csrf' => 'token'], JSON_THROW_ON_ERROR));
        $controller = new ApiKeyController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }
}
