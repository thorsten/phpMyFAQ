<?php

/**
 * The glossary helper class.
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
 * @since     2025-11-07
 */

declare(strict_types=1);

namespace phpMyFAQ\Glossary;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class GlossaryHelperTest extends TestCase
{
    private GlossaryHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new GlossaryHelper();
    }

    public function testExtractSimplePattern(): void
    {
        $matches = [' full', ' ', 'word', '.'];
        [$prefix, $item, $postfix] = $this->helper->extractMatchParts($matches);
        $this->assertSame(' ', $prefix);
        $this->assertSame('word', $item);
        $this->assertSame('.', $postfix);
    }

    public function testExtractFallbackEmpty(): void
    {
        $matches = ['word'];
        [$prefix, $item, $postfix] = $this->helper->extractMatchParts($matches);
        $this->assertSame('', $prefix);
        $this->assertSame('', $item);
        $this->assertSame('', $postfix);
    }

    public function testExtractExtendedPattern(): void
    {
        $matches = array_fill(0, 11, '');
        $matches[9] = '(';
        $matches[10] = 'word';
        [$prefix, $item, $postfix] = $this->helper->extractMatchParts($matches);
        $this->assertSame('(', $prefix);
        $this->assertSame('word', $item);
        $this->assertSame('', $postfix);
    }

    public function testFormatTooltip(): void
    {
        $html = $this->helper->formatTooltip('def', 'item', '(', ')');
        $this->assertStringContainsString('title="def"', $html);
        $this->assertStringContainsString('>item<', $html);
        $this->assertStringStartsWith('(', $html);
        $this->assertStringContainsString('</abbr>', $html);
        $this->assertStringEndsWith(')', rtrim($html));
    }
}
