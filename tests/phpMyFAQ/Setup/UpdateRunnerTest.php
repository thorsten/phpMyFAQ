<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\System;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class UpdateRunnerTest extends TestCase
{
    public function testClassNamespaceAndConstruction(): void
    {
        $runner = new UpdateRunner(
            Configuration::getConfigurationInstance(),
            new System(),
        );

        $this->assertInstanceOf(UpdateRunner::class, $runner);

        $reflection = new ReflectionClass($runner);
        $this->assertEquals('phpMyFAQ\\Setup', $reflection->getNamespaceName());
    }

    public function testVersionPropertyExists(): void
    {
        $runner = new UpdateRunner(
            Configuration::getConfigurationInstance(),
            new System(),
        );

        $reflection = new ReflectionClass($runner);
        $this->assertTrue($reflection->hasProperty('version'));

        $property = $reflection->getProperty('version');
        $this->assertTrue($property->isPrivate());
        $type = $property->getType();
        $this->assertNotNull($type);
        $this->assertEquals('string', $type->getName());
    }

    public function testAllTaskMethodsExistAndArePrivate(): void
    {
        $runner = new UpdateRunner(
            Configuration::getConfigurationInstance(),
            new System(),
        );

        $reflection = new ReflectionClass($runner);

        $expectedMethods = [
            'taskHealthCheck',
            'taskUpdateCheck',
            'taskDownloadPackage',
            'taskExtractPackage',
            'taskCreateTemporaryBackup',
            'taskInstallPackage',
            'taskUpdateDatabase',
            'taskCleanup',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue($reflection->hasMethod($methodName), sprintf('Method %s should exist', $methodName));
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPrivate(), sprintf('Method %s should be private', $methodName));

            $parameters = $method->getParameters();
            $this->assertCount(1, $parameters, sprintf('Method %s should have exactly one parameter', $methodName));
            $this->assertEquals('io', $parameters[0]->getName(), sprintf('Parameter name of %s should be io', $methodName));

            $returnType = $method->getReturnType();
            $this->assertNotNull($returnType, sprintf('Method %s should have a return type', $methodName));
            $this->assertEquals('int', $returnType->getName(), sprintf('Method %s should return int', $methodName));
        }
    }

    public function testRunMethodSignature(): void
    {
        $runner = new UpdateRunner(
            Configuration::getConfigurationInstance(),
            new System(),
        );

        $reflection = new ReflectionClass($runner);
        $this->assertTrue($reflection->hasMethod('run'));

        $method = $reflection->getMethod('run');
        $this->assertTrue($method->isPublic());

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('io', $parameters[0]->getName());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('int', $returnType->getName());
    }
}

