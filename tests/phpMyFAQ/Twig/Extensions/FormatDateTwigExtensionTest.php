<?php

namespace phpMyFAQ\Twig\Extensions;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Twig\Extension\AbstractExtension;

/**
 * Test class for FormatDateTwigExtension
 */
#[AllowMockObjectsWithoutExpectations]
class FormatDateTwigExtensionTest extends TestCase
{
    private FormatDateTwigExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new FormatDateTwigExtension();
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

    public function testFormatDateMethodExists(): void
    {
        $this->assertTrue(method_exists(FormatDateTwigExtension::class, 'formatDate'));

        $reflection = new ReflectionClass(FormatDateTwigExtension::class);
        $method = $reflection->getMethod('formatDate');

        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    public function testFormatDateMethodSignature(): void
    {
        $reflection = new ReflectionClass(FormatDateTwigExtension::class);
        $method = $reflection->getMethod('formatDate');

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $stringParam = $parameters[0];
        $this->assertEquals('string', $stringParam->getName());
        $this->assertEquals('string', $stringParam->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    public function testHasTwigFilterAttribute(): void
    {
        $reflection = new ReflectionClass(FormatDateTwigExtension::class);
        $method = $reflection->getMethod('formatDate');

        $attributes = $method->getAttributes();
        $this->assertNotEmpty($attributes);

        $filterAttribute = null;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Twig\Attribute\AsTwigFilter') {
                $filterAttribute = $attribute;
                break;
            }
        }

        $this->assertNotNull($filterAttribute, 'Method should have AsTwigFilter attribute');

        $arguments = array_values($filterAttribute->getArguments());
        $this->assertContains('formatDate', $arguments);
    }

    public function testClassHasCorrectImports(): void
    {
        $filename = new ReflectionClass(FormatDateTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $expectedImports = [
            'use phpMyFAQ\Configuration;',
            'use phpMyFAQ\Date;',
            'use Twig\Attribute\AsTwigFilter;',
            'use Twig\Extension\AbstractExtension;',
        ];

        foreach ($expectedImports as $import) {
            $this->assertStringContainsString($import, $source);
        }
    }

    public function testMethodUsesConfigurationInstance(): void
    {
        $filename = new ReflectionClass(FormatDateTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('Configuration::getConfigurationInstance()', $source);
        $this->assertStringContainsString('new Date', $source);
    }

    public function testMethodImplementsDateFormatting(): void
    {
        $filename = new ReflectionClass(FormatDateTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('$date->format($string)', $source);
    }

    public function testMethodIsStaticForTwigCompatibility(): void
    {
        $reflection = new ReflectionClass(FormatDateTwigExtension::class);
        $method = $reflection->getMethod('formatDate');

        $this->assertTrue($method->isStatic(), 'formatDate should be static for Twig performance');
    }

    public function testExtensionStructure(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, $this->extension);

        $reflection = new ReflectionClass($this->extension);
        $this->assertTrue($reflection->hasMethod('formatDate'));
    }

    public function testParameterTypeEnforcement(): void
    {
        $reflection = new ReflectionClass(FormatDateTwigExtension::class);
        $method = $reflection->getMethod('formatDate');

        $parameters = $method->getParameters();
        $stringParam = $parameters[0];

        $this->assertTrue($stringParam->hasType());
        $this->assertEquals('string', $stringParam->getType()->getName());
        $this->assertFalse($stringParam->allowsNull());
    }

    public function testReturnTypeEnforcement(): void
    {
        $reflection = new ReflectionClass(FormatDateTwigExtension::class);
        $method = $reflection->getMethod('formatDate');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        $this->assertFalse($returnType->allowsNull());
    }

    public function testFilterNameIsCorrect(): void
    {
        $reflection = new ReflectionClass(FormatDateTwigExtension::class);
        $method = $reflection->getMethod('formatDate');

        $attributes = $method->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Twig\\Attribute\\AsTwigFilter') {
                $arguments = array_values($attribute->getArguments());
                $this->assertContains('formatDate', $arguments);
                break;
            }
        }
    }

    public function testMethodCreatesDateInstance(): void
    {
        $filename = new ReflectionClass(FormatDateTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('$date = new Date($configuration)', $source);
    }

    public function testDocumentationExists(): void
    {
        $filename = new ReflectionClass(FormatDateTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('/**', $source);
        $this->assertStringContainsString('Twig extension to format the date', $source);
        $this->assertStringContainsString('@package   phpMyFAQ\Template', $source);
    }
}
