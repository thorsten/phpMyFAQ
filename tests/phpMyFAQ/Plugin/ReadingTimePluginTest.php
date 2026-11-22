<?php

declare(strict_types=1);

namespace phpMyFAQ\Plugin;

use phpMyFAQ\Plugin\ReadingTime\ReadingTimePlugin;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/phpmyfaq/content/plugins/ReadingTime/ReadingTimePlugin.php';

/**
 * The bundled plugins live outside the measured source directory, so they are no coverage target.
 */
#[CoversNothing]
final class ReadingTimePluginTest extends TestCase
{
    public function testBadgeEndsWithTheUnitForOneMinute(): void
    {
        $this->assertSame('<i class="bi bi-clock"></i>  ~ 1 min', $this->badgeFor('A short answer.'));
    }

    public function testBadgeEndsWithTheUnitForSeveralMinutes(): void
    {
        $this->assertSame('<i class="bi bi-clock"></i>  ~ 3 min', $this->badgeFor(str_repeat('word ', 600)));
    }

    private function badgeFor(string $content): string
    {
        $event = new PluginEvent($content);
        new ReadingTimePlugin()->addReadingTime($event);

        return $event->getOutput();
    }
}
