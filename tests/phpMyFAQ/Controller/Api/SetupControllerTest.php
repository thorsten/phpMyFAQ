<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;use phpMyFAQ\Controller\Frontend\Api\SetupController;use phpMyFAQ\Core\Exception;use phpMyFAQ\Strings;use phpMyFAQ\Translation;use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;use PHPUnit\Framework\TestCase;use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class SetupControllerTest extends TestCase
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

    public function testCheckRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], '4.0.0');

        $controller = new SetupController();

        $this->expectException(\Exception::class);
        $controller->check($request);
    }

    public function testCheckWithEmptyVersion(): void
    {
        $request = new Request([], [], [], [], [], [], '');

        $controller = new SetupController();

        $this->expectException(\Exception::class);
        $controller->check($request);
    }

    public function testCheckReturnsJsonResponse(): void
    {
        $request = new Request([], [], [], [], [], [], '4.0.0');

        $controller = new SetupController();

        $this->expectException(\Exception::class);
        $controller->check($request);
    }

    public function testBackupRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], '4.0.0');

        $controller = new SetupController();

        $this->expectException(\Exception::class);
        $controller->backup($request);
    }

    public function testBackupWithEmptyVersion(): void
    {
        $request = new Request([], [], [], [], [], [], '');

        $controller = new SetupController();

        $this->expectException(\Exception::class);
        $controller->backup($request);
    }

    public function testUpdateDatabaseRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], '4.0.0');

        $controller = new SetupController();

        $this->expectException(\Exception::class);
        $controller->updateDatabase($request);
    }

    public function testUpdateDatabaseWithEmptyVersion(): void
    {
        $request = new Request([], [], [], [], [], [], '');

        $controller = new SetupController();

        $this->expectException(\Exception::class);
        $controller->updateDatabase($request);
    }

    public function testUpdateDatabaseReturnsJsonResponse(): void
    {
        $request = new Request([], [], [], [], [], [], '4.0.0');

        $controller = new SetupController();

        $this->expectException(\Exception::class);
        $controller->updateDatabase($request);
    }
}
