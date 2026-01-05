<?php

namespace phpMyFAQ\Twig\Extensions;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Twig\Extension\AbstractExtension;

/**
 * Test class for PluginTwigExtension
 */
#[AllowMockObjectsWithoutExpectations]
class PluginTwigExtensionTest extends TestCase
{
    private PluginTwigExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new PluginTwigExtension();
    }

    public function testExtendsAbstractExtension(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, $this->extension);
    }

    public function testClassUsesCorrectNamespace(): void
    {
        $reflection = new ReflectionClass($this->extension);
        $this->assertEquals('phpMyFAQ\Twig\Extensions', $reflection->getNamespaceName());
    }

    public function testTriggerPluginEventMethodExists(): void
    {
        $this->assertTrue(method_exists(PluginTwigExtension::class, 'triggerPluginEvent'));

        $reflection = new ReflectionClass(PluginTwigExtension::class);
        $method = $reflection->getMethod('triggerPluginEvent');

        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    public function testTriggerPluginEventMethodSignature(): void
    {
        $reflection = new ReflectionClass(PluginTwigExtension::class);
        $method = $reflection->getMethod('triggerPluginEvent');

        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $eventNameParam = $parameters[0];
        $this->assertEquals('eventName', $eventNameParam->getName());
        $this->assertEquals('string', $eventNameParam->getType()->getName());

        $dataParam = $parameters[1];
        $this->assertEquals('data', $dataParam->getName());
        $this->assertEquals('mixed', $dataParam->getType()->getName());
        $this->assertTrue($dataParam->isDefaultValueAvailable());
        $this->assertNull($dataParam->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    public function testHasTwigFunctionAttribute(): void
    {
        $reflection = new ReflectionClass(PluginTwigExtension::class);
        $method = $reflection->getMethod('triggerPluginEvent');

        $attributes = $method->getAttributes();
        $this->assertNotEmpty($attributes);

        $functionAttribute = null;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Twig\Attribute\AsTwigFunction') {
                $functionAttribute = $attribute;
                break;
            }
        }

        $this->assertNotNull($functionAttribute, 'Method should have AsTwigFunction attribute');

        $arguments = array_values($functionAttribute->getArguments());
        $this->assertContains('phpMyFAQPlugin', $arguments);
    }

    public function testClassHasCorrectImports(): void
    {
        $filename = new ReflectionClass(PluginTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $expectedImports = [
            'use phpMyFAQ\Configuration;',
            'use Twig\Attribute\AsTwigFunction;',
            'use Twig\Extension\AbstractExtension;',
        ];

        foreach ($expectedImports as $import) {
            $this->assertStringContainsString($import, $source);
        }
    }

    public function testMethodUsesConfigurationInstance(): void
    {
        $filename = new ReflectionClass(PluginTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('Configuration::getConfigurationInstance()', $source);
    }

    public function testMethodImplementsPluginManagerCall(): void
    {
        $filename = new ReflectionClass(PluginTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('->getPluginManager()->triggerEvent($eventName, $data)', $source);
    }

    public function testMethodIsStaticForTwigCompatibility(): void
    {
        $reflection = new ReflectionClass(PluginTwigExtension::class);
        $method = $reflection->getMethod('triggerPluginEvent');

        $this->assertTrue($method->isStatic(), 'triggerPluginEvent should be static for Twig performance');
    }

    public function testExtensionStructure(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, $this->extension);

        $reflection = new ReflectionClass($this->extension);
        $this->assertTrue($reflection->hasMethod('triggerPluginEvent'));
    }

    public function testParameterTypeEnforcement(): void
    {
        $reflection = new ReflectionClass(PluginTwigExtension::class);
        $method = $reflection->getMethod('triggerPluginEvent');

        $parameters = $method->getParameters();

        // Test eventName parameter
        $eventNameParam = $parameters[0];
        $this->assertTrue($eventNameParam->hasType());
        $this->assertEquals('string', $eventNameParam->getType()->getName());
        $this->assertFalse($eventNameParam->allowsNull());

        // Test data parameter
        $dataParam = $parameters[1];
        $this->assertTrue($dataParam->hasType());
        $this->assertEquals('mixed', $dataParam->getType()->getName());
        $this->assertTrue($dataParam->allowsNull());
    }

    public function testReturnTypeEnforcement(): void
    {
        $reflection = new ReflectionClass(PluginTwigExtension::class);
        $method = $reflection->getMethod('triggerPluginEvent');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        $this->assertFalse($returnType->allowsNull());
    }

    public function testFunctionNameIsCorrect(): void
    {
        $reflection = new ReflectionClass(PluginTwigExtension::class);
        $method = $reflection->getMethod('triggerPluginEvent');

        $attributes = $method->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Twig\\Attribute\\AsTwigFunction') {
                $arguments = array_values($attribute->getArguments());
                $this->assertContains('phpMyFAQPlugin', $arguments);
                break;
            }
        }
    }

    public function testDeclareStrictTypes(): void
    {
        $filename = new ReflectionClass(PluginTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('declare(strict_types=1);', $source);
    }

    public function testMethodChaining(): void
    {
        $filename = new ReflectionClass(PluginTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        // Should chain getConfigurationInstance -> getPluginManager -> triggerEvent
        $this->assertStringContainsString('->getPluginManager()', $source);
        $this->assertStringContainsString('->triggerEvent(', $source);
    }

    public function testDocumentationExists(): void
    {
        $filename = new ReflectionClass(PluginTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('/**', $source);
        $this->assertStringContainsString('Twig extension to trigger plugin events', $source);
        $this->assertStringContainsString('@package   phpMyFAQ\Template', $source);
    }

    public function testOptionalDataParameter(): void
    {
        $reflection = new ReflectionClass(PluginTwigExtension::class);
        $method = $reflection->getMethod('triggerPluginEvent');

        $parameters = $method->getParameters();
        $dataParam = $parameters[1];

        $this->assertTrue($dataParam->isOptional());
        $this->assertTrue($dataParam->isDefaultValueAvailable());
        $this->assertNull($dataParam->getDefaultValue());
    }

    public function testMixedTypeUsage(): void
    {
        // Test that method correctly uses mixed type for flexible data parameter
        $reflection = new ReflectionClass(PluginTwigExtension::class);
        $method = $reflection->getMethod('triggerPluginEvent');

        $parameters = $method->getParameters();
        $dataParam = $parameters[1];

        $this->assertEquals('mixed', $dataParam->getType()->getName());
    }
}
