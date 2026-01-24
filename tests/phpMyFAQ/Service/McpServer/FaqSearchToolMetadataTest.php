<?php

namespace phpMyFAQ\Service\McpServer;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FaqSearchToolMetadataTest extends TestCase
{
    private FaqSearchToolMetadata $metadata;

    protected function setUp(): void
    {
        $this->metadata = new FaqSearchToolMetadata();
    }

    public function testGetName(): void
    {
        $this->assertSame('faq_search', $this->metadata->getName());
    }

    public function testGetDescription(): void
    {
        $desc = $this->metadata->getDescription();
        $this->assertIsString($desc);
        $this->assertStringContainsString('Search through the phpMyFAQ knowledge base', $desc);
    }

    public function testGetTitle(): void
    {
        $this->assertSame('FAQ Search', $this->metadata->getTitle());
    }

    public function testGetInputSchema(): void
    {
        $schema = $this->metadata->getInputSchema();
        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('query', $schema['properties']);
        $this->assertContains('query', $schema['required']);
    }

    public function testGetOutputSchema(): void
    {
        $schema = $this->metadata->getOutputSchema();
        $this->assertIsArray($schema);
        $this->assertArrayHasKey('results', $schema['properties']);
        $this->assertArrayHasKey('total_found', $schema['properties']);
    }

    public function testGetAnnotations(): void
    {
        $this->assertNull($this->metadata->getAnnotations());
    }
}
