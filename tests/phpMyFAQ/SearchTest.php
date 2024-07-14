<?php

namespace phpMyFAQ;

use Exception;
use PHPUnit\Framework\TestCase;

class SearchTest extends TestCase
{
    private Configuration $configuration;
    private Search $search;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->search = new Search($this->configuration);
    }

    public function testSetCategoryId(): void
    {
        $this->search->setCategoryId(1);
        $this->assertEquals(1, $this->search->getCategoryId());
    }

    public function testGetCategoryId(): void
    {
        $this->search->setCategoryId(1);
        $this->assertEquals(1, $this->search->getCategoryId());
    }

    /**
     * @throws Exception
     */
    public function testSearchWithNumericTerm(): void
    {
        $this->configuration->method('get')->willReturn(false);
        $this->search = $this->getMockBuilder(Search::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['searchDatabase'])
            ->getMock();

        $this->search->expects($this->once())
            ->method('searchDatabase')
            ->with('123', true)
            ->willReturn([]);

        $this->assertEquals([], $this->search->search('123'));
    }

    /**
     * @throws Exception
     */
    public function testSearchWithNonNumericTerm(): void
    {
        $this->configuration->method('get')->willReturn(false);
        $this->search = $this->getMockBuilder(Search::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['searchDatabase'])
            ->getMock();

        $this->search->expects($this->once())
            ->method('searchDatabase')
            ->with('test', true)
            ->willReturn([]);

        $this->assertEquals([], $this->search->search('test'));
    }
}
