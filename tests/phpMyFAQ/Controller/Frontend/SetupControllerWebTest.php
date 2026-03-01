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
    public function testSetupPageRenders(): void
    {
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
        $response = $this->requestPublic('GET', '/setup/install');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Installation', $response);
    }
}
