<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class InstallationRunnerTest extends TestCase
{
    public function testRunnerCanBeConstructed(): void
    {
        $system = $this->createStub(System::class);
        $runner = new InstallationRunner($system);

        $this->assertInstanceOf(InstallationRunner::class, $runner);
    }

    public function testRunMethodExists(): void
    {
        $system = $this->createStub(System::class);
        $runner = new InstallationRunner($system);

        $this->assertTrue(method_exists($runner, 'run'));
    }

    public function testRunAcceptsInstallationInput(): void
    {
        $reflectionMethod = new \ReflectionMethod(InstallationRunner::class, 'run');
        $parameters = $reflectionMethod->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('input', $parameters[0]->getName());
        $this->assertEquals(InstallationInput::class, $parameters[0]->getType()->getName());
    }

    public function testRunnerHasAllExpectedSteps(): void
    {
        $reflectionClass = new \ReflectionClass(InstallationRunner::class);
        $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PRIVATE);
        $stepMethods = array_filter($methods, fn($m) => str_starts_with($m->getName(), 'step'));

        $expectedSteps = [
            'stepValidateConnectivity',
            'stepCreateConfigFiles',
            'stepEstablishDbConnection',
            'stepCreateDatabaseTables',
            'stepInsertStopwords',
            'stepSeedConfiguration',
            'stepCreateAdminUser',
            'stepGrantPermissions',
            'stepInsertFormInputs',
            'stepCreateAnonymousUser',
            'stepCreateInstance',
            'stepInitializeSearchEngine',
            'stepAdjustHtaccess',
        ];

        $actualStepNames = array_map(fn($m) => $m->getName(), $stepMethods);
        sort($actualStepNames);
        sort($expectedSteps);

        $this->assertEquals($expectedSteps, $actualStepNames);
    }

    public function testRunCallsStepsInCorrectOrder(): void
    {
        $reflectionMethod = new \ReflectionMethod(InstallationRunner::class, 'run');
        $fileName = $reflectionMethod->getFileName();
        $startLine = $reflectionMethod->getStartLine();
        $endLine = $reflectionMethod->getEndLine();

        $lines = array_slice(file($fileName), $startLine - 1, $endLine - $startLine + 1);
        $body = implode('', $lines);

        // Verify steps are called in the correct order
        $stepOrder = [
            'stepValidateConnectivity',
            'stepCreateConfigFiles',
            'stepEstablishDbConnection',
            'stepCreateDatabaseTables',
            'stepInsertStopwords',
            'stepSeedConfiguration',
            'stepCreateAdminUser',
            'stepGrantPermissions',
            'stepInsertFormInputs',
            'stepCreateAnonymousUser',
            'stepCreateInstance',
            'stepInitializeSearchEngine',
            'stepAdjustHtaccess',
        ];

        $lastPos = 0;
        foreach ($stepOrder as $step) {
            $pos = strpos($body, $step);
            $this->assertNotFalse($pos, "Step $step should be called in run()");
            $this->assertGreaterThan($lastPos, $pos, "Step $step should come after previous step");
            $lastPos = $pos;
        }
    }
}
