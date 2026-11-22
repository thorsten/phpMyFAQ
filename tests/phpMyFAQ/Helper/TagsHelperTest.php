<?php

namespace phpMyFAQ\Helper;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class TagsHelperTest extends TestCase
{
    private TagsHelper $tagsHelper;

    protected function setUp(): void
    {
        $this->tagsHelper = new TagsHelper();
    }

    public function testRenderTagList()
    {
        $tags = [
            1 => 'tag1',
            2 => 'tag2',
        ];

        $this->tagsHelper->setTaggingIds([1, 2]);

        $expectedOutput =
            '<a class="btn btn-outline-primary m-1" href="./search.html?tagging_id=2">tag1 '
            . '<i aria-hidden="true" class="bi bi-dash-square"></i></a> '
            . '<a class="btn btn-outline-primary m-1" href="./search.html?tagging_id=1">tag2 '
            . '<i aria-hidden="true" class="bi bi-dash-square"></i></a> ';

        $result = $this->tagsHelper->renderTagList($tags);
        $this->assertEquals($expectedOutput, $result);
    }

    public function testRenderSearchTag()
    {
        $tagId = 1;
        $tagName = 'tag1';

        $this->tagsHelper->setTaggingIds([1, 2]);

        $expectedOutput =
            '<a class="btn btn-outline-primary m-1" href="./search.html?tagging_id=2">tag1 '
            . '<i aria-hidden="true" class="bi bi-dash-square"></i></a> ';

        $result = $this->tagsHelper->renderSearchTag($tagId, $tagName);
        $this->assertEquals($expectedOutput, $result);
    }

    public function testRenderSearchTagRemovesOnlyTheWholeTagId(): void
    {
        $this->tagsHelper->setTaggingIds([1, 12, 21]);

        $this->assertStringContainsString('href="./search.html?tagging_id=12,21"', $this->tagsHelper->renderSearchTag(
            1,
            'tag1',
        ));
        $this->assertStringContainsString('href="./search.html?tagging_id=1,21"', $this->tagsHelper->renderSearchTag(
            12,
            'tag12',
        ));
    }

    public function testRenderSearchTagUrlEncodesTheNameOfTheLastTag(): void
    {
        $this->tagsHelper->setTaggingIds([1]);

        $this->assertSame('<a class="btn btn-outline-primary m-1" href="./search.html?search=C%26A+B">C&amp;A B '
        . '<i aria-hidden="true" class="bi bi-dash-square"></i></a> ', $this->tagsHelper->renderSearchTag(1, 'C&A B'));
    }

    public function testGetTaggingIds()
    {
        $taggingIds = [1, 2, 3];
        $this->tagsHelper->setTaggingIds($taggingIds);

        $result = $this->tagsHelper->getTaggingIds();
        $this->assertEquals($taggingIds, $result);
    }

    public function testRenderRelatedTag()
    {
        $tagId = 1;
        $tagName = 'tag1';
        $relevance = 10;

        $this->tagsHelper->setTaggingIds([2, 3]);

        $expectedOutput =
            '<a class="btn btn-outline-primary m-1" href="./search.html?tagging_id=2,3,1">'
            . '<i aria-hidden="true" class="bi bi-plus-square"></i>  tag1 '
            . '<span class="badge bg-info">10</span></a>';

        $result = $this->tagsHelper->renderRelatedTag($tagId, $tagName, $relevance);
        $this->assertEquals($expectedOutput, $result);
    }
}
