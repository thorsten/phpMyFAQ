<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\Api;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Setup\EnvironmentConfigurator;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\Setup\Upgrade;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class UpdateControllerTest extends TestCase
{
    private Configuration $configuration;
    private Upgrade $upgrade;
    private Api $adminApi;
    private Update $update;
    private EnvironmentConfigurator $configurator;

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
        $this->upgrade = $this->createStub(Upgrade::class);
        $this->adminApi = $this->createStub(Api::class);
        $this->update = $this->createStub(Update::class);
        $this->configurator = $this->createStub(EnvironmentConfigurator::class);
    }

    /**
     * @throws \Exception
     */
    public function testHealthCheckRequiresAuthentication(): void
    {
        $controller = new UpdateController($this->upgrade, $this->adminApi, $this->update, $this->configurator);

        $this->expectException(\Exception::class);
        $controller->healthCheck();
    }

    /**
     * @throws \Exception
     */
    public function testVersionsRequiresAuthentication(): void
    {
        $controller = new UpdateController($this->upgrade, $this->adminApi, $this->update, $this->configurator);

        $this->expectException(\Exception::class);
        $controller->versions();
    }

    /**
     * @throws \Exception
     */
    public function testUpdateCheckRequiresAuthentication(): void
    {
        $controller = new UpdateController($this->upgrade, $this->adminApi, $this->update, $this->configurator);

        $this->expectException(\Exception::class);
        $controller->updateCheck();
    }

    /**
     * @throws \Exception
     */
    public function testDownloadPackageRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new UpdateController($this->upgrade, $this->adminApi, $this->update, $this->configurator);

        $this->expectException(\Exception::class);
        $controller->downloadPackage($request);
    }

    /**
     * @throws \Exception
     */
    public function testExtractPackageRequiresAuthentication(): void
    {
        $controller = new UpdateController($this->upgrade, $this->adminApi, $this->update, $this->configurator);

        $this->expectException(\Exception::class);
        $controller->extractPackage();
    }

    /**
     * @throws \Exception
     */
    public function testCreateTemporaryBackupRequiresAuthentication(): void
    {
        $controller = new UpdateController($this->upgrade, $this->adminApi, $this->update, $this->configurator);

        $this->expectException(\Exception::class);
        $controller->createTemporaryBackup();
    }

    /**
     * @throws \Exception
     */
    public function testInstallPackageRequiresAuthentication(): void
    {
        $controller = new UpdateController($this->upgrade, $this->adminApi, $this->update, $this->configurator);

        $this->expectException(\Exception::class);
        $controller->installPackage();
    }

    /**
     * @throws \Exception
     */
    public function testUpdateDatabaseRequiresAuthentication(): void
    {
        $controller = new UpdateController($this->upgrade, $this->adminApi, $this->update, $this->configurator);

        $this->expectException(\Exception::class);
        $controller->updateDatabase();
    }

    /**
     * @throws \Exception
     */
    public function testCleanUpRequiresAuthentication(): void
    {
        $controller = new UpdateController($this->upgrade, $this->adminApi, $this->update, $this->configurator);

        $this->expectException(\Exception::class);
        $controller->cleanUp();
    }
}
