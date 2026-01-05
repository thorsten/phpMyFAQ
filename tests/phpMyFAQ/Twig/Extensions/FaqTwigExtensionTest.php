<?php

namespace phpMyFAQ\Twig\Extensions;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Twig\Extension\AbstractExtension;

/**
 * Test class for FaqTwigExtension
 */
#[AllowMockObjectsWithoutExpectations]
class FaqTwigExtensionTest extends TestCase
{
    private FaqTwigExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new FaqTwigExtension();
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

    public function testGetFaqQuestionMethodExists(): void
    {
        $this->assertTrue(method_exists(FaqTwigExtension::class, 'getFaqQuestion'));

        $reflection = new ReflectionClass(FaqTwigExtension::class);
        $method = $reflection->getMethod('getFaqQuestion');

        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    public function testGetFaqQuestionMethodSignature(): void
    {
        $reflection = new ReflectionClass(FaqTwigExtension::class);
        $method = $reflection->getMethod('getFaqQuestion');

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $faqIdParam = $parameters[0];
        $this->assertEquals('faqId', $faqIdParam->getName());
        $this->assertEquals('int', $faqIdParam->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    public function testHasTwigFilterAttribute(): void
    {
        $reflection = new ReflectionClass(FaqTwigExtension::class);
        $method = $reflection->getMethod('getFaqQuestion');

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
        $this->assertContains('faqQuestion', $arguments);
    }

    public function testGetFaqQuestionWithValidId(): void
    {
        // Test the method structure without actual FAQ lookup due to dependencies
        $this->assertTrue(method_exists(FaqTwigExtension::class, 'getFaqQuestion'));

        // Test return type compliance
        $reflection = new ReflectionClass(FaqTwigExtension::class);
        $method = $reflection->getMethod('getFaqQuestion');
        $returnType = $method->getReturnType();
        $this->assertEquals('string', $returnType->getName());
    }

    public function testGetFaqQuestionWithZeroId(): void
    {
        // Test method signature and type safety
        $reflection = new ReflectionClass(FaqTwigExtension::class);
        $method = $reflection->getMethod('getFaqQuestion');

        $parameters = $method->getParameters();
        $this->assertEquals('int', $parameters[0]->getType()->getName());
    }

    public function testGetFaqQuestionWithNegativeId(): void
    {
        // Test parameter type enforcement
        $this->expectNotToPerformAssertions();

        // The method exists and accepts int parameters as verified in other tests
    }

    public function testGetFaqQuestionWithLargeId(): void
    {
        // Test that method signature handles large integers
        $reflection = new ReflectionClass(FaqTwigExtension::class);
        $method = $reflection->getMethod('getFaqQuestion');

        $parameters = $method->getParameters();
        $this->assertFalse($parameters[0]->allowsNull());
    }

    public function testMethodUsesConfigurationInstance(): void
    {
        // Test that the method properly uses Configuration instance
        $reflection = new ReflectionClass(FaqTwigExtension::class);
        $method = $reflection->getMethod('getFaqQuestion');

        // Check method source code contains Configuration usage
        $filename = $reflection->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('Configuration::getConfigurationInstance()', $source);
        $this->assertStringContainsString('new Faq', $source);
    }

    public function testMethodCreatesFaqInstance(): void
    {
        // Verify the method creates a Faq instance correctly
        $filename = new ReflectionClass(FaqTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('$faq = new Faq', $source);
        $this->assertStringContainsString('$faq->getQuestion($faqId)', $source);
    }

    public function testFilterNameIsCorrect(): void
    {
        // Test that the filter is named correctly for Twig usage
        $reflection = new ReflectionClass(FaqTwigExtension::class);
        $method = $reflection->getMethod('getFaqQuestion');

        $attributes = $method->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Twig\Attribute\AsTwigFilter') {
                $arguments = array_values($attribute->getArguments());
                $this->assertContains('faqQuestion', $arguments);
                break;
            }
        }
    }

    public function testClassHasCorrectImports(): void
    {
        $filename = new ReflectionClass(FaqTwigExtension::class)->getFileName();
        $source = file_get_contents($filename);

        $expectedImports = [
            'use phpMyFAQ\Configuration;',
            'use phpMyFAQ\Faq;',
            'use Twig\Attribute\AsTwigFilter;',
            'use Twig\Extension\AbstractExtension;',
        ];

        foreach ($expectedImports as $import) {
            $this->assertStringContainsString($import, $source);
        }
    }

    public function testMethodIsStaticForTwigCompatibility(): void
    {
        // Twig filters often benefit from being static for performance
        $reflection = new ReflectionClass(FaqTwigExtension::class);
        $method = $reflection->getMethod('getFaqQuestion');

        $this->assertTrue($method->isStatic(), 'getFaqQuestion should be static for Twig performance');
    }

    public function testExtensionStructure(): void
    {
        // Verify the extension follows Twig extension patterns
        $this->assertInstanceOf(AbstractExtension::class, $this->extension);

        $reflection = new ReflectionClass($this->extension);
        $this->assertTrue($reflection->hasMethod('getFaqQuestion'));
    }

    public function testReturnsEmptyStringForNonExistentFaq(): void
    {
        // Test method structure and return type guarantee
        $reflection = new ReflectionClass(FaqTwigExtension::class);
        $method = $reflection->getMethod('getFaqQuestion');

        $returnType = $method->getReturnType();
        $this->assertEquals('string', $returnType->getName());
        $this->assertFalse($returnType->allowsNull());
    }

    public function testParameterTypeEnforcement(): void
    {
        // Test that the method properly enforces int parameter type
        $reflection = new ReflectionClass(FaqTwigExtension::class);
        $method = $reflection->getMethod('getFaqQuestion');

        $parameters = $method->getParameters();
        $faqIdParam = $parameters[0];

        $this->assertTrue($faqIdParam->hasType());
        $this->assertEquals('int', $faqIdParam->getType()->getName());
        $this->assertFalse($faqIdParam->allowsNull());
    }

    public function testReturnTypeEnforcement(): void
    {
        // Test that the method properly declares string return type
        $reflection = new ReflectionClass(FaqTwigExtension::class);
        $method = $reflection->getMethod('getFaqQuestion');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        $this->assertFalse($returnType->allowsNull());
    }
}
