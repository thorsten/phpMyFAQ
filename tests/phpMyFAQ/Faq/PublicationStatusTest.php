<?php

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PublicationStatus::class)]
final class PublicationStatusTest extends TestCase
{
    public function testActivatedRecordInsideItsWindowIsPublished(): void
    {
        self::assertTrue($this->statusFor(['active' => 'yes'])->isPublished());
    }

    public function testDeactivatedRecordIsNotPublished(): void
    {
        self::assertFalse($this->statusFor(['active' => 'no'])->isPublished());
    }

    public function testExpiredRecordIsNotPublished(): void
    {
        self::assertFalse($this->statusFor(['active' => 'yes', 'dateEnd' => '20200101000000'])->isPublished());
    }

    public function testRecordBeforeItsStartDateIsNotPublished(): void
    {
        self::assertFalse($this->statusFor(['active' => 'yes', 'dateStart' => '29991231235959'])->isPublished());
    }

    /**
     * An empty record is what Faq::faqRecord holds before getFaq() runs. Defaulting it to
     * published would turn a forgotten load into a disclosure.
     */
    public function testEmptyRecordIsNotPublished(): void
    {
        self::assertFalse(new PublicationStatus([])->isPublished());
    }

    /**
     * Faq::getFaq() populates its access-denied placeholder with active = 'no', but a
     * record missing the key entirely must not be treated as published either.
     */
    public function testRecordWithoutActiveFlagIsNotPublished(): void
    {
        self::assertFalse(new PublicationStatus(['title' => 'Draft'])->isPublished());
    }

    public function testEmptyWindowBoundsAreTreatedAsUnbounded(): void
    {
        $status = $this->statusFor(['active' => 'yes', 'dateStart' => '', 'dateEnd' => '']);

        self::assertTrue($status->isPublished());
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function statusFor(array $overrides): PublicationStatus
    {
        return new PublicationStatus([
            'active' => 'no',
            'dateStart' => '00000000000000',
            'dateEnd' => '99991231235959',
            ...$overrides,
        ]);
    }
}
