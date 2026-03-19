<?php

namespace phpMyFAQ\Service\McpServer;

use PHPUnit\Framework\TestCase;

class McpToolDefinitionTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $inputSchema = ['type' => 'object', 'properties' => ['query' => ['type' => 'string']]];
        $outputSchema = ['type' => 'object', 'properties' => ['results' => ['type' => 'array']]];
        $annotations = ['readOnlyHint' => true];

        $definition = new McpToolDefinition(
            name: 'test_tool',
            description: 'A test tool',
            title: 'Test Tool',
            inputSchema: $inputSchema,
            outputSchema: $outputSchema,
            annotations: $annotations,
        );

        $this->assertSame('test_tool', $definition->name);
        $this->assertSame('A test tool', $definition->description);
        $this->assertSame('Test Tool', $definition->title);
        $this->assertSame($inputSchema, $definition->inputSchema);
        $this->assertSame($outputSchema, $definition->outputSchema);
        $this->assertSame($annotations, $definition->annotations);
    }

    public function testConstructorWithDefaultOptionalParameters(): void
    {
        $definition = new McpToolDefinition(
            name: 'minimal_tool',
            description: 'Minimal tool',
            title: null,
            inputSchema: ['type' => 'object'],
        );

        $this->assertSame('minimal_tool', $definition->name);
        $this->assertNull($definition->title);
        $this->assertNull($definition->outputSchema);
        $this->assertSame([], $definition->annotations);
    }

    public function testIsReadonly(): void
    {
        $definition = new McpToolDefinition(name: 'tool', description: 'desc', title: null, inputSchema: []);

        $reflection = new \ReflectionClass($definition);
        $this->assertTrue($reflection->isReadOnly());
    }
}
