<?php

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RecordVisibility::class)]
final class RecordVisibilityTest extends TestCase
{
    public function testPublishedRecordInsideItsWindowIsVisible(): void
    {
        self::assertTrue($this->statusFor(['status' => 'published'])->isVisible());
    }

    public function testDraftRecordIsNotVisible(): void
    {
        self::assertFalse($this->statusFor(['status' => 'draft'])->isVisible());
    }

    public function testReviewRecordIsNotVisible(): void
    {
        self::assertFalse($this->statusFor(['status' => 'review'])->isVisible());
    }

    public function testExpiredRecordIsNotVisible(): void
    {
        self::assertFalse($this->statusFor(['status' => 'published', 'dateEnd' => '20200101000000'])->isVisible());
    }

    public function testRecordBeforeItsStartDateIsNotVisible(): void
    {
        self::assertFalse($this->statusFor(['status' => 'published', 'dateStart' => '29991231235959'])->isVisible());
    }

    /**
     * An empty record is what Faq::faqRecord holds before getFaq() runs. Defaulting it to
     * published would turn a forgotten load into a disclosure.
     */
    public function testEmptyRecordIsNotVisible(): void
    {
        self::assertFalse(new RecordVisibility([])->isVisible());
    }

    /**
     * Faq::getFaq() populates its access-denied placeholder with status = 'draft', but a
     * record missing the key entirely must not be treated as visible either.
     */
    public function testRecordWithoutStatusFlagIsNotVisible(): void
    {
        self::assertFalse(new RecordVisibility(['title' => 'Draft'])->isVisible());
    }

    /**
     * Solution ID 42 is how getFaq() flags a record that does not exist or that the
     * requester has no permission for. Such a placeholder can still carry status = 'published'
     * when it is built from a real row the requester may not read, so the sentinel has to
     * be rejected on its own rather than relying on the status flag.
     */
    public function testRecordFlaggedWithTheAccessDeniedSolutionIdIsNotVisible(): void
    {
        $status = $this->statusFor(['status' => 'published', 'solution_id' => 42]);

        self::assertFalse($status->isVisible());
    }

    public function testRecordWithoutSolutionIdIsNotVisible(): void
    {
        self::assertFalse(new RecordVisibility(['status' => 'published'])->isVisible());
    }

    public function testEmptyWindowBoundsAreTreatedAsUnbounded(): void
    {
        $status = $this->statusFor(['status' => 'published', 'dateStart' => '', 'dateEnd' => '']);

        self::assertTrue($status->isVisible());
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function statusFor(array $overrides): RecordVisibility
    {
        return new RecordVisibility([
            'solution_id' => 1000,
            'status' => 'draft',
            'dateStart' => '00000000000000',
            'dateEnd' => '99991231235959',
            ...$overrides,
        ]);
    }
}
