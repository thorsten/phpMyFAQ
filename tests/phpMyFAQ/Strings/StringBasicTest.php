<?php

namespace phpMyFAQ\Strings;

use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

class StringBasicTest extends TestCase
{
    public function testStrlen(): void
    {
        // Test case 1: Check the length of a regular string
        $result = Strings::strlen("Hello, World!");
        $this->assertEquals(13, $result);

        // Test case 2: Check the length of an empty string
        $result = Strings::strlen("");
        $this->assertEquals(0, $result);

        // Test case 3: Check the length of a string with German umlauts
        $result = Strings::strlen("äöü");
        $this->assertEquals(3, $result);
    }
}
