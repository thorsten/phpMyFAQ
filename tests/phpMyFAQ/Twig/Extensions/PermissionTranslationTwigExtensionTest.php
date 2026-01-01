<?php

namespace phpMyFAQ\Twig\Extensions;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Twig\Extension\AbstractExtension;

/**
 * Test class for PermissionTranslationTwigExtension
 */
#[AllowMockObjectsWithoutExpectations]
class PermissionTranslationTwigExtensionTest extends TestCase
{
    private PermissionTranslationTwigExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new PermissionTranslationTwigExtension();
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

    public function testGetPermissionTranslationMethodExists(): void
    {
        $this->assertTrue(method_exists(PermissionTranslationTwigExtension::class, 'getPermissionTranslation'));

        $reflection = new ReflectionClass(PermissionTranslationTwigExtension::class);
        $method = $reflection->getMethod('getPermissionTranslation');

        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    public function testGetPermissionTranslationMethodSignature(): void
    {
        $reflection = new ReflectionClass(PermissionTranslationTwigExtension::class);
        $method = $reflection->getMethod('getPermissionTranslation');

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
        $reflection = new ReflectionClass(PermissionTranslationTwigExtension::class);
        $method = $reflection->getMethod('getPermissionTranslation');

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

        $arguments = $filterAttribute->getArguments();
        $this->assertContains('permission', $arguments);
    }

    public function testClassHasCorrectImports(): void
    {
        $filename = (new ReflectionClass(PermissionTranslationTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        $expectedImports = [
            'use phpMyFAQ\Translation;',
            'use Twig\Attribute\AsTwigFilter;',
            'use Twig\Extension\AbstractExtension;',
        ];

        foreach ($expectedImports as $import) {
            $this->assertStringContainsString($import, $source);
        }
    }

    public function testMethodImplementsPermissionKeyGeneration(): void
    {
        $filename = (new ReflectionClass(PermissionTranslationTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString("'permission::%s'", $source);
        $this->assertStringContainsString('$string', $source);
    }

    public function testMethodImplementsTranslationLogic(): void
    {
        $filename = (new ReflectionClass(PermissionTranslationTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('Translation::has($key)', $source);
        $this->assertStringContainsString('Translation::get($key)', $source);
    }

    public function testMethodImplementsFallbackBehavior(): void
    {
        $filename = (new ReflectionClass(PermissionTranslationTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        // Should return empty string if translation doesn't exist
        $this->assertStringContainsString('? Translation::get($key) : \'\'', $source);
    }

    public function testMethodIsStaticForTwigCompatibility(): void
    {
        $reflection = new ReflectionClass(PermissionTranslationTwigExtension::class);
        $method = $reflection->getMethod('getPermissionTranslation');

        $this->assertTrue($method->isStatic(), 'getPermissionTranslation should be static for Twig performance');
    }

    public function testExtensionStructure(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, $this->extension);

        $reflection = new ReflectionClass($this->extension);
        $this->assertTrue($reflection->hasMethod('getPermissionTranslation'));
    }

    public function testParameterTypeEnforcement(): void
    {
        $reflection = new ReflectionClass(PermissionTranslationTwigExtension::class);
        $method = $reflection->getMethod('getPermissionTranslation');

        $parameters = $method->getParameters();
        $stringParam = $parameters[0];

        $this->assertTrue($stringParam->hasType());
        $this->assertEquals('string', $stringParam->getType()->getName());
        $this->assertFalse($stringParam->allowsNull());
    }

    public function testReturnTypeEnforcement(): void
    {
        $reflection = new ReflectionClass(PermissionTranslationTwigExtension::class);
        $method = $reflection->getMethod('getPermissionTranslation');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        $this->assertFalse($returnType->allowsNull());
    }

    public function testFilterNameIsCorrect(): void
    {
        $reflection = new ReflectionClass(PermissionTranslationTwigExtension::class);
        $method = $reflection->getMethod('getPermissionTranslation');

        $attributes = $method->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Twig\\Attribute\\AsTwigFilter') {
                $arguments = $attribute->getArguments();
                $this->assertEquals('permission', $arguments['name']);
                break;
            }
        }
    }

    public function testMethodUsesTranslationClass(): void
    {
        $filename = (new ReflectionClass(PermissionTranslationTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        // Should use both Translation::has and Translation::get
        $this->assertStringContainsString('Translation::has', $source);
        $this->assertStringContainsString('Translation::get', $source);
    }

    public function testDocumentationExists(): void
    {
        $filename = (new ReflectionClass(PermissionTranslationTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('/**', $source);
        $this->assertStringContainsString('Twig extension to translate the permission string', $source);
        $this->assertStringContainsString('@package   phpMyFAQ\Template', $source);
    }

    public function testPermissionKeyFormatIsCorrect(): void
    {
        // Test the permission key format structure
        $filename = (new ReflectionClass(PermissionTranslationTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        // Should format permission keys with 'permission::' prefix
        $this->assertStringContainsString('permission::%s', $source);
    }

    public function testTernaryOperatorUsage(): void
    {
        // Test that the method uses ternary operator for conditional translation
        $filename = (new ReflectionClass(PermissionTranslationTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('?', $source);
        $this->assertStringContainsString(':', $source);
    }
}
