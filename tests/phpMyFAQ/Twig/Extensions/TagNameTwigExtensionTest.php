<?php

namespace phpMyFAQ\Twig\Extensions;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Twig\Extension\AbstractExtension;

/**
 * Test class for TagNameTwigExtension
 */
class TagNameTwigExtensionTest extends TestCase
{
    private TagNameTwigExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new TagNameTwigExtension();
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

    public function testGetTagNameMethodExists(): void
    {
        $this->assertTrue(method_exists(TagNameTwigExtension::class, 'getTagName'));

        $reflection = new ReflectionClass(TagNameTwigExtension::class);
        $method = $reflection->getMethod('getTagName');

        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    public function testGetTagNameMethodSignature(): void
    {
        $reflection = new ReflectionClass(TagNameTwigExtension::class);
        $method = $reflection->getMethod('getTagName');

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $tagIdParam = $parameters[0];
        $this->assertEquals('tagId', $tagIdParam->getName());
        $this->assertEquals('int', $tagIdParam->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    public function testHasTwigFilterAttribute(): void
    {
        $reflection = new ReflectionClass(TagNameTwigExtension::class);
        $method = $reflection->getMethod('getTagName');

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
        $this->assertContains('tagName', $arguments);
    }

    public function testClassHasCorrectImports(): void
    {
        $filename = (new ReflectionClass(TagNameTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        $expectedImports = [
            'use phpMyFAQ\Configuration;',
            'use phpMyFAQ\Tags;',
            'use Twig\Attribute\AsTwigFilter;',
            'use Twig\Extension\AbstractExtension;'
        ];

        foreach ($expectedImports as $import) {
            $this->assertStringContainsString($import, $source);
        }
    }

    public function testMethodUsesConfigurationInstance(): void
    {
        $filename = (new ReflectionClass(TagNameTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('Configuration::getConfigurationInstance()', $source);
        $this->assertStringContainsString('new Tags', $source);
    }

    public function testMethodImplementsTagDataRetrieval(): void
    {
        $filename = (new ReflectionClass(TagNameTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('$tags->getTagNameById($tagId)', $source);
    }

    public function testMethodIsStaticForTwigCompatibility(): void
    {
        $reflection = new ReflectionClass(TagNameTwigExtension::class);
        $method = $reflection->getMethod('getTagName');

        $this->assertTrue($method->isStatic(), 'getTagName should be static for Twig performance');
    }

    public function testExtensionStructure(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, $this->extension);

        $reflection = new ReflectionClass($this->extension);
        $this->assertTrue($reflection->hasMethod('getTagName'));
    }

    public function testParameterTypeEnforcement(): void
    {
        $reflection = new ReflectionClass(TagNameTwigExtension::class);
        $method = $reflection->getMethod('getTagName');

        $parameters = $method->getParameters();
        $tagIdParam = $parameters[0];

        $this->assertTrue($tagIdParam->hasType());
        $this->assertEquals('int', $tagIdParam->getType()->getName());
        $this->assertFalse($tagIdParam->allowsNull());
    }

    public function testReturnTypeEnforcement(): void
    {
        $reflection = new ReflectionClass(TagNameTwigExtension::class);
        $method = $reflection->getMethod('getTagName');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        $this->assertFalse($returnType->allowsNull());
    }

    public function testFilterNameIsCorrect(): void
    {
        $reflection = new ReflectionClass(TagNameTwigExtension::class);
        $method = $reflection->getMethod('getTagName');

        $attributes = $method->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Twig\\Attribute\\AsTwigFilter') {
                $arguments = array_values($attribute->getArguments());
                $this->assertContains('tagName', $arguments);
                break;
            }
        }
    }

    public function testDeclareStrictTypes(): void
    {
        $filename = (new ReflectionClass(TagNameTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('declare(strict_types=1);', $source);
    }

    public function testMethodCreatesTags(): void
    {
        $filename = (new ReflectionClass(TagNameTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('$tags = new Tags(Configuration::getConfigurationInstance())', $source);
    }
}
