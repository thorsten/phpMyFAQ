<?php

namespace phpMyFAQ\Storage;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class StorageInterfaceTest extends TestCase
{
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(StorageInterface::class);
    }

    public function testIsInterface(): void
    {
        $this->assertTrue($this->reflection->isInterface());
    }

    public function testHasPutMethod(): void
    {
        $method = $this->reflection->getMethod('put');
        $this->assertCount(2, $method->getParameters());
        $this->assertEquals('bool', $method->getReturnType()->getName());
        $this->assertEquals('path', $method->getParameters()[0]->getName());
        $this->assertEquals('contents', $method->getParameters()[1]->getName());
    }

    public function testHasPutStreamMethod(): void
    {
        $method = $this->reflection->getMethod('putStream');
        $this->assertCount(2, $method->getParameters());
        $this->assertEquals('bool', $method->getReturnType()->getName());
        $this->assertEquals('path', $method->getParameters()[0]->getName());
        $this->assertEquals('stream', $method->getParameters()[1]->getName());
        $this->assertEquals('mixed', $method->getParameters()[1]->getType()->getName());
    }

    public function testHasGetMethod(): void
    {
        $method = $this->reflection->getMethod('get');
        $this->assertCount(1, $method->getParameters());
        $this->assertEquals('string', $method->getReturnType()->getName());
        $this->assertEquals('path', $method->getParameters()[0]->getName());
    }

    public function testHasDeleteMethod(): void
    {
        $method = $this->reflection->getMethod('delete');
        $this->assertCount(1, $method->getParameters());
        $this->assertEquals('bool', $method->getReturnType()->getName());
        $this->assertEquals('path', $method->getParameters()[0]->getName());
    }

    public function testHasExistsMethod(): void
    {
        $method = $this->reflection->getMethod('exists');
        $this->assertCount(1, $method->getParameters());
        $this->assertEquals('bool', $method->getReturnType()->getName());
        $this->assertEquals('path', $method->getParameters()[0]->getName());
    }

    public function testHasUrlMethod(): void
    {
        $method = $this->reflection->getMethod('url');
        $this->assertCount(1, $method->getParameters());
        $this->assertEquals('string', $method->getReturnType()->getName());
        $this->assertEquals('path', $method->getParameters()[0]->getName());
    }

    public function testHasSizeMethod(): void
    {
        $method = $this->reflection->getMethod('size');
        $this->assertCount(1, $method->getParameters());
        $this->assertEquals('int', $method->getReturnType()->getName());
        $this->assertEquals('path', $method->getParameters()[0]->getName());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMockImplementation(): void
    {
        $mock = $this->createMock(StorageInterface::class);

        $mock->method('put')->willReturn(true);
        $mock->method('putStream')->willReturn(true);
        $mock->method('get')->willReturn('file contents');
        $mock->method('delete')->willReturn(true);
        $mock->method('exists')->willReturn(true);
        $mock->method('url')->willReturn('https://example.com/file.txt');
        $mock->method('size')->willReturn(1024);

        $this->assertTrue($mock->put('test/file.txt', 'contents'));
        $this->assertTrue($mock->putStream('test/file.txt', fopen('php://memory', 'r')));
        $this->assertEquals('file contents', $mock->get('test/file.txt'));
        $this->assertTrue($mock->delete('test/file.txt'));
        $this->assertTrue($mock->exists('test/file.txt'));
        $this->assertEquals('https://example.com/file.txt', $mock->url('test/file.txt'));
        $this->assertEquals(1024, $mock->size('test/file.txt'));
    }
}
