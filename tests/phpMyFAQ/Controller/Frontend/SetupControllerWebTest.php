<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(SetupController::class)]
#[UsesNamespace('phpMyFAQ')]
final class SetupControllerWebTest extends ControllerWebTestCase
{
    private string $databaseConfigFile;
    private string $databaseConfigBackup;
    private bool $databaseConfigHidden = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseConfigFile = PMF_ROOT_DIR . '/content/core/config/database.php';
        $this->databaseConfigBackup = $this->databaseConfigFile . '.bak';
    }

    protected function tearDown(): void
    {
        $this->restoreDatabaseConfig();

        parent::tearDown();
    }

    public function testSetupPageRenders(): void
    {
        $this->hideDatabaseConfig();
        $response = $this->requestPublic('GET', '/setup');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Setup', $response);
    }

    public function testUpdatePageRenders(): void
    {
        $response = $this->requestPublic('GET', '/update?step=1');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Update', $response);
    }

    public function testUpdatePageDefaultsToStepOneWhenStepIsMissing(): void
    {
        $response = $this->requestPublic('GET', '/update');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Please create a full backup of your database', $response);
    }

    public function testUpdatePageStepTwoRendersBackupStage(): void
    {
        $response = $this->requestPublic('GET', '/update?step=2');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('<h4 class="alert-heading">Backup</h4>', $response);
    }

    public function testUpdatePageStepThreeRendersDatabaseUpdateStage(): void
    {
        $response = $this->requestPublic('GET', '/update?step=3');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="result-update"', $response);
    }

    public function testUpdatePageFallsBackToStepOneForInvalidStep(): void
    {
        $response = $this->requestPublic('GET', '/update?step=not-a-number');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Please create a full backup of your database', $response);
    }

    public function testUpdatePageFallsBackToStepOneForZeroStep(): void
    {
        $response = $this->requestPublic('GET', '/update?step=0');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Please create a full backup of your database', $response);
    }

    public function testUpdatePageFallsBackToStepOneForNegativeStep(): void
    {
        $response = $this->requestPublic('GET', '/update?step=-5');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Please create a full backup of your database', $response);
    }

    public function testUpdatePageFallsBackToStepOneForOutOfRangeStep(): void
    {
        $response = $this->requestPublic('GET', '/update?step=99');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Please create a full backup of your database', $response);
    }

    public function testSetupInstallPageRenders(): void
    {
        $this->hideDatabaseConfig();
        $response = $this->requestPublic('GET', '/setup/install');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Installation', $response);
    }

    public function testSetupPageReturnsForbiddenWhenAlreadyInstalled(): void
    {
        $this->createDatabaseConfig();
        $response = $this->requestPublic('GET', '/setup');

        self::assertResponseStatusCodeSame(403, $response);
        self::assertResponseContains('phpMyFAQ is already installed.', $response);
    }

    public function testSetupInstallPageReturnsForbiddenWhenAlreadyInstalled(): void
    {
        $this->createDatabaseConfig();
        $response = $this->requestPublic('GET', '/setup/install');

        self::assertResponseStatusCodeSame(403, $response);
        self::assertResponseContains('phpMyFAQ is already installed.', $response);
    }

    private bool $databaseConfigCreated = false;

    private function hideDatabaseConfig(): void
    {
        if ($this->databaseConfigHidden || !is_file($this->databaseConfigFile)) {
            return;
        }

        rename($this->databaseConfigFile, $this->databaseConfigBackup);
        $this->databaseConfigHidden = true;
    }

    private function createDatabaseConfig(): void
    {
        if (is_file($this->databaseConfigFile)) {
            return;
        }

        $dir = dirname($this->databaseConfigFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($this->databaseConfigFile, "<?php\n// test stub\n");
        $this->databaseConfigCreated = true;
    }

    private function restoreDatabaseConfig(): void
    {
        if ($this->databaseConfigCreated && is_file($this->databaseConfigFile)) {
            unlink($this->databaseConfigFile);
            $this->databaseConfigCreated = false;
        }

        if ($this->databaseConfigHidden && is_file($this->databaseConfigBackup)) {
            rename($this->databaseConfigBackup, $this->databaseConfigFile);
            $this->databaseConfigHidden = false;
        }
    }
}
