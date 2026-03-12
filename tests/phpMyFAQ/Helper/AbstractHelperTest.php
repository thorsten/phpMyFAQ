<?php

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use phpMyFAQ\Category;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Configuration;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Tags;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AbstractHelperTest extends TestCase
{
    public function testFluentSettersStoreDependencies(): void
    {
        $helper = new class() extends AbstractHelper {};

        $category = $this->createStub(Category::class);
        $relation = $this->createStub(Relation::class);
        $tags = $this->createStub(Tags::class);
        $plurals = new Plurals();
        $configuration = $this->createStub(Configuration::class);

        $this->assertSame($helper, $helper->setCategory($category));
        $this->assertSame($category, $helper->getCategory());
        $this->assertSame($helper, $helper->setCategoryRelation($relation));
        $this->assertSame($helper, $helper->setTags($tags));
        $this->assertSame($helper, $helper->setPlurals($plurals));
        $this->assertSame($helper, $helper->setSessionId('abc123'));
        $this->assertSame($helper, $helper->setConfiguration($configuration));
        $this->assertSame($configuration, $helper->getConfiguration());
    }
}
