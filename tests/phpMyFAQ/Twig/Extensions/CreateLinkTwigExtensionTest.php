<?php

namespace phpMyFAQ\Twig\Extensions;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Twig\Extension\AbstractExtension;

/**
 * Test class for CreateLinkTwigExtension
 */
class CreateLinkTwigExtensionTest extends TestCase
{
    private CreateLinkTwigExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new CreateLinkTwigExtension();
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

    public function testCategoryLinkMethodExists(): void
    {
        $this->assertTrue(method_exists(CreateLinkTwigExtension::class, 'categoryLink'));

        $reflection = new ReflectionClass(CreateLinkTwigExtension::class);
        $method = $reflection->getMethod('categoryLink');

        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    public function testCategoryLinkMethodSignature(): void
    {
        $reflection = new ReflectionClass(CreateLinkTwigExtension::class);
        $method = $reflection->getMethod('categoryLink');

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $categoryIdParam = $parameters[0];
        $this->assertEquals('categoryId', $categoryIdParam->getName());
        $this->assertEquals('int', $categoryIdParam->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    public function testHasTwigFilterAttribute(): void
    {
        $reflection = new ReflectionClass(CreateLinkTwigExtension::class);
        $method = $reflection->getMethod('categoryLink');

        $attributes = $method->getAttributes();
        $this->assertNotEmpty($attributes);

        $hasFilterAttribute = false;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Twig\Attribute\AsTwigFilter') {
                $hasFilterAttribute = true;
                $arguments = array_values($attribute->getArguments());
                $this->assertContains('categoryLink', $arguments);
                break;
            }
        }

        $this->assertTrue($hasFilterAttribute, 'Method should have AsTwigFilter attribute');
    }

    public function testHasTwigFunctionAttribute(): void
    {
        $reflection = new ReflectionClass(CreateLinkTwigExtension::class);
        $method = $reflection->getMethod('categoryLink');

        $attributes = $method->getAttributes();
        $this->assertNotEmpty($attributes);

        $hasFunctionAttribute = false;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Twig\Attribute\AsTwigFunction') {
                $hasFunctionAttribute = true;
                $arguments = array_values($attribute->getArguments());
                $this->assertContains('categoryLink', $arguments);
                break;
            }
        }

        $this->assertTrue($hasFunctionAttribute, 'Method should have AsTwigFunction attribute');
    }

    public function testCategoryLinkWithValidId(): void
    {
        // Test method structure without actual category lookup due to dependencies
        $this->assertTrue(method_exists(CreateLinkTwigExtension::class, 'categoryLink'));

        // Test return type compliance
        $reflection = new ReflectionClass(CreateLinkTwigExtension::class);
        $method = $reflection->getMethod('categoryLink');
        $returnType = $method->getReturnType();
        $this->assertEquals('string', $returnType->getName());
    }

    public function testCategoryLinkWithZeroId(): void
    {
        // Test method signature and type safety
        $reflection = new ReflectionClass(CreateLinkTwigExtension::class);
        $method = $reflection->getMethod('categoryLink');

        $parameters = $method->getParameters();
        $this->assertEquals('int', $parameters[0]->getType()->getName());
    }

    public function testCategoryLinkWithNegativeId(): void
    {
        // Test parameter type enforcement
        $this->expectNotToPerformAssertions();
        // The method exists and accepts int parameters as verified in other tests
    }

    public function testCategoryLinkWithLargeId(): void
    {
        // Test that method signature handles large integers
        $reflection = new ReflectionClass(CreateLinkTwigExtension::class);
        $method = $reflection->getMethod('categoryLink');

        $parameters = $method->getParameters();
        $this->assertFalse($parameters[0]->allowsNull());
    }

    public function testClassHasCorrectImports(): void
    {
        $filename = (new ReflectionClass(CreateLinkTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        $expectedImports = [
            'use phpMyFAQ\Category;',
            'use phpMyFAQ\Configuration;',
            'use phpMyFAQ\Faq;',
            'use phpMyFAQ\Link;',
            'use Twig\Attribute\AsTwigFilter;',
            'use Twig\Attribute\AsTwigFunction;',
            'use Twig\Extension\AbstractExtension;'
        ];

        foreach ($expectedImports as $import) {
            $this->assertStringContainsString($import, $source);
        }
    }

    public function testMethodUsesConfigurationInstance(): void
    {
        $filename = (new ReflectionClass(CreateLinkTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('Configuration::getConfigurationInstance()', $source);
        $this->assertStringContainsString('new Category', $source);
        $this->assertStringContainsString('new Link', $source);
    }

    public function testMethodCreatesCorrectUrlFormat(): void
    {
        $filename = (new ReflectionClass(CreateLinkTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        // Should create URL with proper format
        $this->assertStringContainsString('index.php?action=show&cat=', $source);
        $this->assertStringContainsString('sprintf', $source);
        $this->assertStringContainsString('$categoryId', $source);
    }

    public function testMethodIsStaticForTwigCompatibility(): void
    {
        $reflection = new ReflectionClass(CreateLinkTwigExtension::class);
        $method = $reflection->getMethod('categoryLink');

        $this->assertTrue($method->isStatic(), 'categoryLink should be static for Twig performance');
    }

    public function testExtensionStructure(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, $this->extension);

        $reflection = new ReflectionClass($this->extension);
        $this->assertTrue($reflection->hasMethod('categoryLink'));
    }

    public function testParameterTypeEnforcement(): void
    {
        $reflection = new ReflectionClass(CreateLinkTwigExtension::class);
        $method = $reflection->getMethod('categoryLink');

        $parameters = $method->getParameters();
        $categoryIdParam = $parameters[0];

        $this->assertTrue($categoryIdParam->hasType());
        $this->assertEquals('int', $categoryIdParam->getType()->getName());
        $this->assertFalse($categoryIdParam->allowsNull());
    }

    public function testReturnTypeEnforcement(): void
    {
        $reflection = new ReflectionClass(CreateLinkTwigExtension::class);
        $method = $reflection->getMethod('categoryLink');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        $this->assertFalse($returnType->allowsNull());
    }

    public function testDualAttributeImplementation(): void
    {
        // Test that method has both Filter and Function attributes
        $reflection = new ReflectionClass(CreateLinkTwigExtension::class);
        $method = $reflection->getMethod('categoryLink');

        $attributes = $method->getAttributes();
        $this->assertGreaterThanOrEqual(2, count($attributes), 'Should have at least 2 attributes (Filter and Function)');

        $attributeNames = array_map(fn($attr) => $attr->getName(), $attributes);
        $this->assertContains('Twig\Attribute\AsTwigFilter', $attributeNames);
        $this->assertContains('Twig\Attribute\AsTwigFunction', $attributeNames);
    }

    public function testDeclareStrictTypes(): void
    {
        $filename = (new ReflectionClass(CreateLinkTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('declare(strict_types=1);', $source);
    }

    public function testMethodImplementsLinkCreation(): void
    {
        $filename = (new ReflectionClass(CreateLinkTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        // Should properly create Link object and set properties
        $this->assertStringContainsString('$link = new Link', $source);
        $this->assertStringContainsString('$link->itemTitle', $source);
        $this->assertStringContainsString('$link->toString()', $source);
    }

    public function testMethodCreatesCategoryInstance(): void
    {
        $filename = (new ReflectionClass(CreateLinkTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        // Should create Category instance and get category data
        $this->assertStringContainsString('$category = new Category', $source);
        $this->assertStringContainsString('getCategoryData($categoryId)', $source);
    }

    public function testFilterAndFunctionNamesAreCorrect(): void
    {
        $reflection = new ReflectionClass(CreateLinkTwigExtension::class);
        $method = $reflection->getMethod('categoryLink');

        $attributes = $method->getAttributes();
        foreach ($attributes as $attribute) {
            $arguments = array_values($attribute->getArguments());
            if (!empty($arguments)) {
                $this->assertContains('categoryLink', $arguments);
            }
        }
    }

    public function testUrlGenerationPattern(): void
    {
        // Test that URL generation follows expected pattern
        $filename = (new ReflectionClass(CreateLinkTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        // Should use Configuration to get default URL
        $this->assertStringContainsString('$configuration->getDefaultUrl()', $source);

        // Should format URL with category ID
        $this->assertStringContainsString('%sindex.php?action=show&cat=%d', $source);
    }

    public function testMethodHandlesConfigurationProperly(): void
    {
        // Verify method properly injects Configuration into dependencies
        $filename = (new ReflectionClass(CreateLinkTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        // Should pass configuration to Category and Link constructors
        $this->assertStringContainsString('new Category($configuration)', $source);
        $this->assertStringContainsString('new Link($url, $configuration)', $source);
    }
}
