<?php

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PublicationWindow::class)]
#[CoversClass(InvalidPublicationWindowException::class)]
final class PublicationWindowTest extends TestCase
{
    public function testUnlimitedIsStoredAsTheTwoSentinels(): void
    {
        $window = PublicationWindow::unlimited();

        $this->assertNull($window->start());
        $this->assertNull($window->end());
        $this->assertSame('00000000000000', $window->storedStart());
        $this->assertSame('99991231235959', $window->storedEnd());
    }

    public function testEmptyInputMeansNoLimit(): void
    {
        $window = PublicationWindow::fromInput('', '  ');

        $this->assertSame('00000000000000', $window->storedStart());
        $this->assertSame('99991231235959', $window->storedEnd());
    }

    public function testInputIsStoredInTheFourteenCharacterFormat(): void
    {
        $window = PublicationWindow::fromInput('2026-03-04T05:06', '2027-08-09T10:11:12');

        $this->assertSame('20260304050600', $window->storedStart());
        $this->assertSame('20270809101112', $window->storedEnd());
    }

    public function testOneSideOfTheWindowMayStayOpen(): void
    {
        $this->assertSame('99991231235959', PublicationWindow::fromInput('2026-03-04T05:06', '')->storedEnd());
        $this->assertSame('00000000000000', PublicationWindow::fromInput('', '2026-03-04T05:06')->storedStart());
    }

    public function testStartAndEndMayBeTheSameMoment(): void
    {
        $window = PublicationWindow::fromInput('2026-03-04T05:06', '2026-03-04T05:06');

        $this->assertSame($window->storedStart(), $window->storedEnd());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidDates(): array
    {
        return [
            'free text' => ['tomorrow'],
            'stored format' => ['20260304050600'],
            'date only' => ['2026-03-04'],
            'space instead of T' => ['2026-03-04 05:06'],
            'day that does not exist' => ['2026-02-30T05:06'],
            'hour that does not exist' => ['2026-03-04T25:06'],
            'five digit year' => ['12026-03-04T05:06'],
            'markup' => ['<b>2026-03-04T05:06</b>'],
        ];
    }

    #[DataProvider('invalidDates')]
    public function testInvalidStartIsRejectedWithTheInvalidDateMessage(string $value): void
    {
        try {
            PublicationWindow::fromInput($value, '');
            $this->fail('Expected the start date to be rejected.');
        } catch (InvalidPublicationWindowException $exception) {
            $this->assertSame('msgInvalidFaqDate', $exception->translationKey);
            $this->assertStringContainsString($value, $exception->getMessage());
        }
    }

    #[DataProvider('invalidDates')]
    public function testInvalidEndIsRejectedWithTheInvalidDateMessage(string $value): void
    {
        try {
            PublicationWindow::fromInput('', $value);
            $this->fail('Expected the end date to be rejected.');
        } catch (InvalidPublicationWindowException $exception) {
            $this->assertSame('msgInvalidFaqDate', $exception->translationKey);
        }
    }

    public function testEndBeforeStartIsRejectedWithTheRangeMessage(): void
    {
        try {
            PublicationWindow::fromInput('2026-03-04T05:06', '2026-03-04T05:05');
            $this->fail('Expected the window to be rejected.');
        } catch (InvalidPublicationWindowException $exception) {
            $this->assertSame('msgInvalidFaqDateRange', $exception->translationKey);
        }
    }

    public function testStoredSentinelsAreAnUnlimitedWindow(): void
    {
        $window = PublicationWindow::fromStorage('00000000000000', '99991231235959');

        $this->assertNull($window->start());
        $this->assertNull($window->end());
    }

    public function testStoredDatesRoundTrip(): void
    {
        $window = PublicationWindow::fromStorage('20260304050607', '20270809101112');

        $this->assertSame('2026-03-04T05:06:07', $window->start()?->format('Y-m-d\TH:i:s'));
        $this->assertSame('2027-08-09T10:11:12', $window->end()?->format('Y-m-d\TH:i:s'));
        $this->assertSame('20260304050607', $window->storedStart());
        $this->assertSame('20270809101112', $window->storedEnd());
    }

    public function testStoredValuesThatAreNoDateLeaveThatSideOpen(): void
    {
        $window = PublicationWindow::fromStorage('', 'garbage');

        $this->assertNull($window->start());
        $this->assertNull($window->end());
    }

    /**
     * A row written before the order was enforced has to stay readable, so the editor can show
     * it; fromInput() rejects it when it is saved again.
     */
    public function testStoredWindowThatEndsBeforeItStartsStaysReadable(): void
    {
        $window = PublicationWindow::fromStorage('20270101000000', '20260101000000');

        $this->assertSame('20270101000000', $window->storedStart());
        $this->assertSame('20260101000000', $window->storedEnd());
        $this->assertFalse($window->isOpenAt(new DateTimeImmutable('2026-06-01 00:00:00')));
    }

    public function testIsOpenAtIncludesBothEnds(): void
    {
        $window = PublicationWindow::fromInput('2026-03-04T05:06', '2026-03-05T05:06');

        $this->assertFalse($window->isOpenAt(new DateTimeImmutable('2026-03-04 05:05:59')));
        $this->assertTrue($window->isOpenAt(new DateTimeImmutable('2026-03-04 05:06:00')));
        $this->assertTrue($window->isOpenAt(new DateTimeImmutable('2026-03-05 05:06:00')));
        $this->assertFalse($window->isOpenAt(new DateTimeImmutable('2026-03-05 05:06:01')));
    }

    public function testAnUnlimitedWindowIsAlwaysOpen(): void
    {
        $this->assertTrue(PublicationWindow::unlimited()->isOpenAt(new DateTimeImmutable('1970-01-01 00:00:00')));
        $this->assertTrue(PublicationWindow::unlimited()->isOpenAt(new DateTimeImmutable('9999-12-31 23:59:59')));
    }
}
