<?php

declare(strict_types=1);

namespace phpMyFAQ\Link\Util;

use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

/**
 * Micro benchmark / performance sanity check for TitleSlugifier.
 * Not a strict performance test; just ensures execution stays within a loose time budget.
 */
final class TitleSlugifierBenchTest extends TestCase
{
    protected function setUp(): void
    {
        Strings::init();
    }

    public function testSlugPerformanceUnderThreshold(): void
    {
        $iterations = 5000;
        $titles = [
            'HD Ready Monitor 24"',
            'Ä Ö Ü – Complex   Title ###',
            'Multiple   spaces and --- dashes',
            'Symbols! @#$%^&*() remove please',
            'Long Long Long Long Long Long Long Long Title With 123 Numbers',
        ];
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            foreach ($titles as $t) {
                TitleSlugifier::slug($t);
            }
        }
        $durationMs = (microtime(true) - $start) * 1000;
        // Arbitrary soft threshold: 300ms for 25k slug operations on local dev hardware.
        $this->assertLessThan(300.0, $durationMs, 'Slugifier too slow: ' . $durationMs . 'ms');
    }
}

