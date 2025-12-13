<?php

namespace phpMyFAQ;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ServicesTest extends TestCase
{
    public function testSetQuestion(): void
    {
        // Create a mock Configuration object
        $configuration = $this->createStub(Configuration::class);

        // Create a Services object
        $services = new Services($configuration);
        $services->setQuestion('What is phpMyFAQ?');

        // Test getQuestion method
        $this->assertEquals('What+is+phpMyFAQ%3F', $services->getQuestion());
    }

    public function testGetPdfLink(): void
    {
        // Create a mock Configuration object
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDefaultUrl')->willReturn('http://example.com/');

        // Create a Services object
        $services = new Services($configuration);
        $services->setCategoryId(1);
        $services->setFaqId(123);
        $services->setLanguage('en');

        // Test getPdfLink method
        $expected = 'http://example.com/pdf.php?cat=1&id=123&artlang=en';
        $this->assertEquals($expected, $services->getPdfLink());
    }

    public function testGetPdfApiLink(): void
    {
        // Create a mock Configuration object
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDefaultUrl')->willReturn('http://example.com/');

        // Create a Services object
        $services = new Services($configuration);
        $services->setCategoryId(1);
        $services->setFaqId(123);
        $services->setLanguage('en');

        // Test getPdfApiLink method
        $expected = 'http://example.com/pdf.php?cat=1&id=123&artlang=en';
        $this->assertEquals($expected, $services->getPdfApiLink());
    }
}
