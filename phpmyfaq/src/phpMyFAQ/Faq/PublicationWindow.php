<?php

/**
 * The period in which a published FAQ is public.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * A published FAQ is public from its start date to its end date, both inclusive. Either side
 * may be open: no start means "from the beginning", no end means "never expires".
 *
 * The window is stored in faqdata.date_start / faqdata.date_end as 14-character "YmdHis"
 * strings, where an open side is written as a sentinel value. Read paths compare these
 * columns as strings, so the sentinels and the format are part of the storage contract.
 */
final readonly class PublicationWindow
{
    public const string NO_START = '00000000000000';

    public const string NO_END = '99991231235959';

    private const string STORAGE_FORMAT = 'YmdHis';

    /** The value formats of an HTML datetime-local input, without and with seconds. */
    private const array INPUT_FORMATS = ['Y-m-d\TH:i', 'Y-m-d\TH:i:s'];

    private function __construct(
        private ?DateTimeImmutable $start,
        private ?DateTimeImmutable $end,
    ) {
    }

    /**
     * A window without a start and without an end.
     */
    public static function unlimited(): self
    {
        return new self(null, null);
    }

    /**
     * Builds the window from the values of the two datetime-local inputs of the FAQ editor.
     * An empty value leaves that side of the window open.
     *
     * @throws InvalidPublicationWindowException if a value is not a date or the end is before the start
     */
    public static function fromInput(string $start, string $end): self
    {
        $startDate = self::parseInput($start);
        $endDate = self::parseInput($end);

        if ($startDate instanceof DateTimeImmutable && $endDate instanceof DateTimeImmutable && $endDate < $startDate) {
            throw InvalidPublicationWindowException::endBeforeStart();
        }

        return new self($startDate, $endDate);
    }

    /**
     * Builds the window from the stored date_start / date_end columns. A sentinel, and any
     * value that is not a date at all, leaves that side of the window open. The order of the
     * two dates is not enforced here: a row stored before the order was checked has to stay
     * readable, so the editor can show it and have it corrected.
     */
    public static function fromStorage(string $dateStart, string $dateEnd): self
    {
        return new self(
            $dateStart === self::NO_START ? null : self::parse(self::STORAGE_FORMAT, $dateStart),
            $dateEnd === self::NO_END ? null : self::parse(self::STORAGE_FORMAT, $dateEnd),
        );
    }

    public function start(): ?DateTimeImmutable
    {
        return $this->start;
    }

    public function end(): ?DateTimeImmutable
    {
        return $this->end;
    }

    /**
     * The value to write to faqdata.date_start.
     */
    public function storedStart(): string
    {
        return $this->start?->format(self::STORAGE_FORMAT) ?? self::NO_START;
    }

    /**
     * The value to write to faqdata.date_end.
     */
    public function storedEnd(): string
    {
        return $this->end?->format(self::STORAGE_FORMAT) ?? self::NO_END;
    }

    /**
     * Whether a published FAQ with this window is public at the given moment. Compares the
     * stored representation, exactly as the read paths do.
     */
    public function isOpenAt(DateTimeInterface $moment): bool
    {
        $now = $moment->format(self::STORAGE_FORMAT);

        return $this->storedStart() <= $now && $this->storedEnd() >= $now;
    }

    /**
     * @throws InvalidPublicationWindowException
     */
    private static function parseInput(string $value): ?DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach (self::INPUT_FORMATS as $format) {
            $date = self::parse($format, $value);
            if ($date instanceof DateTimeImmutable) {
                return $date;
            }
        }

        throw InvalidPublicationWindowException::invalidDate($value);
    }

    /**
     * Strict parsing: the value has to round-trip, which rejects overflowing dates such as
     * February 30th that PHP would otherwise silently roll over into March.
     */
    private static function parse(string $format, string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);

        return $date instanceof DateTimeImmutable && $date->format($format) === $value ? $date : null;
    }
}
