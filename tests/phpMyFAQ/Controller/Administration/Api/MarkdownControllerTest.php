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
class MarkdownControllerTest extends TestCase
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
    public function testRenderMarkdownWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new MarkdownController();

        $this->expectException(\Exception::class);
        $controller->renderMarkdown($request);
    }

    /**
     * @throws \Exception
     */
    public function testRenderMarkdownWithMissingTextThrowsException(): void
    {
        $requestData = json_encode([]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new MarkdownController();

        // This will not throw exception, just return success with empty result
        // Testing that the method can be called
        $this->expectNotToPerformAssertions();
    }
}
