<?php

namespace phpMyFAQ\Twig\Extensions;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Twig\Extension\AbstractExtension;

/**
 * Test class for CategoryNameTwigExtension
 */
#[AllowMockObjectsWithoutExpectations]
class CategoryNameTwigExtensionTest extends TestCase
{
    private CategoryNameTwigExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new CategoryNameTwigExtension();
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

    public function testGetCategoryNameMethodExists(): void
    {
        $this->assertTrue(method_exists(CategoryNameTwigExtension::class, 'getCategoryName'));

        $reflection = new ReflectionClass(CategoryNameTwigExtension::class);
        $method = $reflection->getMethod('getCategoryName');

        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    public function testGetCategoryNameMethodSignature(): void
    {
        $reflection = new ReflectionClass(CategoryNameTwigExtension::class);
        $method = $reflection->getMethod('getCategoryName');

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
        $reflection = new ReflectionClass(CategoryNameTwigExtension::class);
        $method = $reflection->getMethod('getCategoryName');

        $attributes = $method->getAttributes();
        $this->assertNotEmpty($attributes);

        $hasFilterAttribute = false;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Twig\Attribute\AsTwigFilter') {
                $hasFilterAttribute = true;
                $arguments = $attribute->getArguments();
                $this->assertContains('categoryName', $arguments);
                break;
            }
        }

        $this->assertTrue($hasFilterAttribute, 'Method should have AsTwigFilter attribute');
    }

    public function testHasTwigFunctionAttribute(): void
    {
        $reflection = new ReflectionClass(CategoryNameTwigExtension::class);
        $method = $reflection->getMethod('getCategoryName');

        $attributes = $method->getAttributes();
        $this->assertNotEmpty($attributes);

        $hasFunctionAttribute = false;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Twig\Attribute\AsTwigFunction') {
                $hasFunctionAttribute = true;
                $arguments = $attribute->getArguments();
                $this->assertContains('categoryName', $arguments);
                break;
            }
        }

        $this->assertTrue($hasFunctionAttribute, 'Method should have AsTwigFunction attribute');
    }

    public function testClassHasCorrectImports(): void
    {
        $filename = new ReflectionClass(CategoryNameTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $expectedImports = [
            'use phpMyFAQ\Category;',
            'use phpMyFAQ\Configuration;',
            'use Twig\Attribute\AsTwigFilter;',
            'use Twig\Attribute\AsTwigFunction;',
            'use Twig\Extension\AbstractExtension;',
        ];

        foreach ($expectedImports as $import) {
            $this->assertStringContainsString($import, $source);
        }
    }

    public function testMethodUsesConfigurationInstance(): void
    {
        $filename = new ReflectionClass(CategoryNameTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('Configuration::getConfigurationInstance()', $source);
        $this->assertStringContainsString('new Category', $source);
    }

    public function testMethodImplementsCategoryDataRetrieval(): void
    {
        $filename = new ReflectionClass(CategoryNameTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('$category->getCategoryData($categoryId)', $source);
        $this->assertStringContainsString('$categoryEntity->getName()', $source);
    }

    public function testMethodIsStaticForTwigCompatibility(): void
    {
        $reflection = new ReflectionClass(CategoryNameTwigExtension::class);
        $method = $reflection->getMethod('getCategoryName');

        $this->assertTrue($method->isStatic(), 'getCategoryName should be static for Twig performance');
    }

    public function testExtensionStructure(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, $this->extension);

        $reflection = new ReflectionClass($this->extension);
        $this->assertTrue($reflection->hasMethod('getCategoryName'));
    }

    public function testParameterTypeEnforcement(): void
    {
        $reflection = new ReflectionClass(CategoryNameTwigExtension::class);
        $method = $reflection->getMethod('getCategoryName');

        $parameters = $method->getParameters();
        $categoryIdParam = $parameters[0];

        $this->assertTrue($categoryIdParam->hasType());
        $this->assertEquals('int', $categoryIdParam->getType()->getName());
        $this->assertFalse($categoryIdParam->allowsNull());
    }

    public function testReturnTypeEnforcement(): void
    {
        $reflection = new ReflectionClass(CategoryNameTwigExtension::class);
        $method = $reflection->getMethod('getCategoryName');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        $this->assertFalse($returnType->allowsNull());
    }

    public function testDualAttributeImplementation(): void
    {
        $reflection = new ReflectionClass(CategoryNameTwigExtension::class);
        $method = $reflection->getMethod('getCategoryName');

        $attributes = $method->getAttributes();
        $this->assertGreaterThanOrEqual(
            2,
            count($attributes),
            'Should have at least 2 attributes (Filter and Function)',
        );

        $attributeNames = array_map(fn($attr) => $attr->getName(), $attributes);
        $this->assertContains('Twig\Attribute\AsTwigFilter', $attributeNames);
        $this->assertContains('Twig\Attribute\AsTwigFunction', $attributeNames);
    }

    public function testFilterAndFunctionNamesAreCorrect(): void
    {
        $reflection = new ReflectionClass(CategoryNameTwigExtension::class);
        $method = $reflection->getMethod('getCategoryName');

        $attributes = $method->getAttributes();
        foreach ($attributes as $attribute) {
            $arguments = $attribute->getArguments();
            if (!empty($arguments)) {
                // Support both positional and named arguments
                $values = array_values($arguments);
                $this->assertContains('categoryName', $values);
            }
        }
    }

    public function testDocumentationExists(): void
    {
        $filename = new ReflectionClass(CategoryNameTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('/**', $source);
        $this->assertStringContainsString('Twig extension to return the category name by category ID', $source);
        $this->assertStringContainsString('@package   phpMyFAQ\Template', $source);
    }
}
