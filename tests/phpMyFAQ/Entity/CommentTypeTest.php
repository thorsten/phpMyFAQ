<?php

/**
 * Test case for CommentType Entity
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 */

namespace phpMyFAQ\Entity;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class CommentTypeTest
 */
#[AllowMockObjectsWithoutExpectations]
class CommentTypeTest extends TestCase
{
    /**
     * Test FAQ constant value
     */
    public function testFaqConstant(): void
    {
        $this->assertEquals('faq', CommentType::FAQ);
        $this->assertIsString(CommentType::FAQ);
    }

    /**
     * Test NEWS constant value
     */
    public function testNewsConstant(): void
    {
        $this->assertEquals('news', CommentType::NEWS);
        $this->assertIsString(CommentType::NEWS);
    }

    /**
     * Test that constants are different
     */
    public function testConstantsAreDifferent(): void
    {
        $this->assertNotEquals(CommentType::FAQ, CommentType::NEWS);
    }

    /**
     * Test constants are not empty
     */
    public function testConstantsAreNotEmpty(): void
    {
        $this->assertNotEmpty(CommentType::FAQ);
        $this->assertNotEmpty(CommentType::NEWS);
    }

    /**
     * Test constants contain expected values
     */
    public function testConstantsContainExpectedValues(): void
    {
        $expectedValues = ['faq', 'news'];
        $actualValues = [CommentType::FAQ, CommentType::NEWS];

        $this->assertEquals($expectedValues, $actualValues);
    }

    /**
     * Test that constants can be used in array contexts
     */
    public function testConstantsInArrayContext(): void
    {
        $commentTypes = [
            CommentType::FAQ,
            CommentType::NEWS,
        ];

        $this->assertCount(2, $commentTypes);
        $this->assertContains('faq', $commentTypes);
        $this->assertContains('news', $commentTypes);
    }

    /**
     * Test constants in conditional logic
     */
    public function testConstantsInConditionals(): void
    {
        $type = CommentType::FAQ;

        if ($type === CommentType::FAQ) {
            $result = 'FAQ comment';
        } elseif ($type === CommentType::NEWS) {
            $result = 'News comment';
        } else {
            $result = 'Unknown';
        }

        $this->assertEquals('FAQ comment', $result);
    }

    /**
     * Test switch statement with constants
     */
    public function testConstantsInSwitch(): void
    {
        $results = [];

        foreach ([CommentType::FAQ, CommentType::NEWS] as $type) {
            switch ($type) {
                case CommentType::FAQ:
                    $results[] = 'FAQ type detected';
                    break;
                case CommentType::NEWS:
                    $results[] = 'News type detected';
                    break;
                default:
                    $results[] = 'Unknown type';
            }
        }

        $this->assertEquals(['FAQ type detected', 'News type detected'], $results);
    }

    /**
     * Test class instantiation (even though it's just constants)
     */
    public function testClassInstantiation(): void
    {
        $commentType = new CommentType();
        $this->assertInstanceOf(CommentType::class, $commentType);
    }

    /**
     * Test reflection on the class
     */
    public function testClassReflection(): void
    {
        $reflection = new ReflectionClass(CommentType::class);

        $this->assertTrue($reflection->hasConstant('FAQ'));
        $this->assertTrue($reflection->hasConstant('NEWS'));
        $this->assertEquals('faq', $reflection->getConstant('FAQ'));
        $this->assertEquals('news', $reflection->getConstant('NEWS'));
    }
}
