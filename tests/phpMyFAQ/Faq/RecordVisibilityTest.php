<?php

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RecordVisibility::class)]
final class RecordVisibilityTest extends TestCase
{
    public function testActivatedRecordInsideItsWindowIsVisible(): void
    {
        self::assertTrue($this->statusFor(['active' => 'yes'])->isVisible());
    }

    public function testDeactivatedRecordIsNotVisible(): void
    {
        self::assertFalse($this->statusFor(['active' => 'no'])->isVisible());
    }

    public function testExpiredRecordIsNotVisible(): void
    {
        self::assertFalse($this->statusFor(['active' => 'yes', 'dateEnd' => '20200101000000'])->isVisible());
    }

    public function testRecordBeforeItsStartDateIsNotVisible(): void
    {
        self::assertFalse($this->statusFor(['active' => 'yes', 'dateStart' => '29991231235959'])->isVisible());
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
     * Faq::getFaq() populates its access-denied placeholder with active = 'no', but a
     * record missing the key entirely must not be treated as visible either.
     */
    public function testRecordWithoutActiveFlagIsNotVisible(): void
    {
        self::assertFalse(new RecordVisibility(['title' => 'Draft'])->isVisible());
    }

    /**
     * Solution ID 42 is how getFaq() flags a record that does not exist or that the
     * requester has no permission for. Such a placeholder can still carry active = 'yes'
     * when it is built from a real row the requester may not read, so the sentinel has to
     * be rejected on its own rather than relying on the active flag.
     */
    public function testRecordFlaggedWithTheAccessDeniedSolutionIdIsNotVisible(): void
    {
        $status = $this->statusFor(['active' => 'yes', 'solution_id' => 42]);

        self::assertFalse($status->isVisible());
    }

    public function testRecordWithoutSolutionIdIsNotVisible(): void
    {
        self::assertFalse(new RecordVisibility(['active' => 'yes'])->isVisible());
    }

    public function testEmptyWindowBoundsAreTreatedAsUnbounded(): void
    {
        $status = $this->statusFor(['active' => 'yes', 'dateStart' => '', 'dateEnd' => '']);

        self::assertTrue($status->isVisible());
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function statusFor(array $overrides): RecordVisibility
    {
        return new RecordVisibility([
            'solution_id' => 1000,
            'active' => 'no',
            'dateStart' => '00000000000000',
            'dateEnd' => '99991231235959',
            ...$overrides,
        ]);
    }
}
