<?php

namespace phpMyFAQ\Helper;

use phpMyFAQ\Category;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Configuration;
use phpMyFAQ\Translation;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class CategoryHelperTest extends TestCase
{
    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();
    }

    /**
     * @throws Exception
     */
    public function testRenderOptionsWithIntCategoryId(): void
    {
        $mockCategory = $this->createMock(Category::class);
        $mockCategory->method('getCategoryTree')->willReturn([
            ['id' => 1, 'name' => 'Category 1', 'indent' => 0],
            ['id' => 2, 'name' => 'Category 2', 'indent' => 1],
        ]);

        $mockClass = $this->getMockBuilder(CategoryHelper::class)
            ->onlyMethods(['getCategory'])
            ->getMock();

        $mockClass->method('getCategory')->willReturn($mockCategory);

        $output = $mockClass->renderOptions(1);

        $expectedOutput = '<option value="1" selected> Category 1 </option>'
            . '<option value="2">.... Category 2 </option>';

        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * @throws Exception
     */
    public function testRenderOptionsWithArrayCategoryId(): void
    {
        $mockCategory = $this->createMock(Category::class);
        $mockCategory->method('getCategoryTree')->willReturn([
            ['id' => 1, 'name' => 'Category 1', 'indent' => 0],
            ['id' => 2, 'name' => 'Category 2', 'indent' => 1],
        ]);

        $mockClass = $this->getMockBuilder(CategoryHelper::class)
            ->onlyMethods(['getCategory'])
            ->getMock();

        $mockClass->method('getCategory')->willReturn($mockCategory);

        $output = $mockClass->renderOptions([
            'category_id' => 2,
            'category_lang' => 'en',
        ]);

        $expectedOutput = '<option value="1"> Category 1 </option>'
            . '<option value="2" selected>.... Category 2 </option>';

        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * @throws Exception
     */
    public function testRenderOptionsWithEmptyCategoryId(): void
    {
        $mockCategory = $this->createMock(Category::class);
        $mockCategory->method('getCategoryTree')->willReturn([
            ['id' => 1, 'name' => 'Category 1', 'indent' => 0],
            ['id' => 2, 'name' => 'Category 2', 'indent' => 1],
        ]);

        $mockClass = $this->getMockBuilder(CategoryHelper::class)
            ->onlyMethods(['getCategory'])
            ->getMock();

        $mockClass->method('getCategory')->willReturn($mockCategory);

        $output = $mockClass->renderOptions([]);

        $expectedOutput = '<option value="1" selected> Category 1 </option>'
            . '<option value="2">.... Category 2 </option>';

        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * @throws Exception
     */
    public function testRenderOptionsWithEmptyCategoryTree(): void
    {
        $mockCategory = $this->createMock(Category::class);
        $mockCategory->method('getCategoryTree')->willReturn([]);

        $mockClass = $this->getMockBuilder(CategoryHelper::class)->onlyMethods(['getCategory'])->getMock();

        $mockClass->method('getCategory')->willReturn($mockCategory);

        $output = $mockClass->renderOptions(1);

        $this->assertEquals('', $output);
    }

    /**
     * @throws Exception
     */
    public function testRenderCategoryTreeWithCategories(): void
    {
        $mockConfiguration = $this->createMock(Configuration::class);
        $mockCategory = $this->createMock(Category::class);

        $mockCategoryTree = [
            ['category_id' => 1, 'name' => 'Category 1'],
            ['category_id' => 2, 'name' => 'Category 2'],
        ];
        $mockCategoryNumbers = [['category_id' => 5, 'parent_id' => 0, 'faqs' => 10]];
        $mockAggregatedNumbers = [['category_id' => 5, 'parent_id' => 0, 'faqs' => 10]];

        $mockCategory->method('getGroups')->willReturn(['group1', 'group2']);
        $mockCategory->method('getOrderedCategories')->willReturn($mockCategoryTree);

        $mockRelation = $this->getMockBuilder(Relation::class)
            ->setConstructorArgs([$mockConfiguration, $mockCategory])
            ->onlyMethods(['getCategoryWithFaqs', 'getAggregatedFaqNumbers'])
            ->getMock();
        $mockRelation->method('getCategoryWithFaqs')->willReturn($mockCategoryNumbers);
        $mockRelation->method('getAggregatedFaqNumbers')->willReturn($mockAggregatedNumbers);

        $mockClass = $this->getMockBuilder(CategoryHelper::class)
            ->setConstructorArgs([$mockConfiguration, $mockCategory])
            ->onlyMethods(['getCategory', 'getConfiguration', 'buildCategoryList', 'normalizeCategoryTree'])
            ->getMock();
        $mockClass->method('getCategory')->willReturn($mockCategory);
        $mockClass->method('getConfiguration')->willReturn($mockConfiguration);
        $mockClass->method('buildCategoryList')->willReturn('<li>Category 1</li><li>Category 2</li>');
        $mockClass->method('normalizeCategoryTree')->willReturn($mockCategoryNumbers);

        $output = $mockClass->renderCategoryTree();

        $expectedOutput = '<ul class="pmf-category-overview"><li>Category 1</li><li>Category 2</li></ul>';
        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * @throws Exception
     */
    public function testRenderCategoryTreeWithMissingCategoriesButTranslationsAvailable(): void
    {
        $mockConfiguration = $this->createMock(Configuration::class);
        $mockCategory = $this->createMock(Category::class);
        $mockRelation = $this->createMock(Relation::class);

        $mockCategoryTree = [];

        $mockCategory->method('getGroups')->willReturn(['group1', 'group2']);
        $mockCategory->method('getOrderedCategories')->willReturn($mockCategoryTree);
        $mockCategory->method('getCategoryLanguagesTranslated')->willReturn(['en', 'fr']);

        $mockClass = $this->getMockBuilder(CategoryHelper::class)
            ->setConstructorArgs([$mockConfiguration, $mockCategory])
            ->onlyMethods(['getCategory', 'getConfiguration', 'buildAvailableCategoryTranslationsList', 'normalizeCategoryTree'])
            ->getMock();
        $mockClass->method('getCategory')->willReturn($mockCategory);
        $mockClass->method('getConfiguration')->willReturn($mockConfiguration);
        $mockClass->method('buildAvailableCategoryTranslationsList')->willReturn('<li>English</li><li>French</li>');
        $mockClass->method('normalizeCategoryTree')->willReturn([]);

        $output = $mockClass->renderCategoryTree(0);

        $expectedOutput = '<p>No category was found in the selected language, but you can select the following languages:</p><ul class="pmf-category-overview"><li>English</li><li>French</li></ul>';
        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * @throws Exception
     */
    public function testRenderCategoryTreeWithEmptyCategoryTree(): void
    {
        $mockConfiguration = $this->createMock(Configuration::class);
        $mockCategory = $this->createMock(Category::class);
        $mockRelation = $this->createMock(Relation::class);

        $mockCategoryTree = [];

        $mockCategory->method('getGroups')->willReturn(['group1', 'group2']);
        $mockCategory->method('getOrderedCategories')->willReturn($mockCategoryTree);
        $mockCategory->method('getCategoryLanguagesTranslated')->willReturn([]);

        $mockClass = $this->getMockBuilder(CategoryHelper::class)
            ->setConstructorArgs([$mockConfiguration, $mockCategory])
            ->onlyMethods(
                ['getCategory', 'getConfiguration', 'buildAvailableCategoryTranslationsList', 'normalizeCategoryTree']
            )
            ->getMock();
        $mockClass->method('getCategory')->willReturn($mockCategory);
        $mockClass->method('getConfiguration')->willReturn($mockConfiguration);
        $mockClass->method('buildAvailableCategoryTranslationsList')->willReturn('<li>No categories available</li>');
        $mockClass->method('normalizeCategoryTree')->willReturn([]);

        $output = $mockClass->renderCategoryTree(0);

        $expectedOutput =
            '<p>No category was found in the selected language, but you can select the following languages:</p>' .
            '<ul class="pmf-category-overview"><li>No categories available</li></ul>';
        $this->assertEquals($expectedOutput, $output);
    }
}
