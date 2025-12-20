<?php

/**
 * phpMyFAQ Date class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-24
 */

declare(strict_types=1);

namespace phpMyFAQ;

use DateTime;
use Exception;

/**
 * Class Date
 *
 * @package phpMyFAQ
 */
readonly class Date
{
    /**
     * Constructor.
     */
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Converts a phpMyFAQ date format (YmdHi[...]) to a format similar to ISO 8601 standard.
     *
     * Example: "202501311530" -> "2025-01-31 15:30" (default format)
     */
    public static function createIsoDate(string $date, string $format = 'Y-m-d H:i', mixed $pmfFormat = null): string
    {
        // Back-compat: if the third param is explicitly false, interpret $date as Unix timestamp / strtotime string
        if ($pmfFormat === false) {
            return self::createIsoDateFromUnixTimestamp($date, $format);
        }

        $timestamp = strtotime(
            substr($date, offset: 0, length: 4)
                . '-'
                . substr($date, offset: 4, length: 2)
                . '-'
                . substr($date, offset: 6, length: 2)
                . ' '
                . substr($date, offset: 8, length: 2)
                . ':'
                . substr($date, offset: 10, length: 2),
        );

        return date($format, (int) $timestamp);
    }

    /**
     * Formats a Unix timestamp according to the given format (default similar to ISO 8601).
     */
    public static function createIsoDateFromUnixTimestamp(int|string $timestamp, string $format = 'Y-m-d H:i'): string
    {
        if (is_string($timestamp) && !ctype_digit($timestamp)) {
            $parsed = strtotime($timestamp);
            return date($format, (int) $parsed);
        }

        return date($format, (int) $timestamp);
    }

    /**
     * Returns the start-of-day timestamp of a tracking filename (trackingDDMMYYYY).
     */
    public function getTrackingFileDateStart(string $file): int
    {
        if (Strings::strlen($file) >= 16) {
            $day = Strings::substr($file, start: 8, length: 2);
            $month = Strings::substr($file, start: 10, length: 2);
            $year = Strings::substr($file, start: 12, length: 4);

            return gmmktime(hour: 0, minute: 0, second: 0, month: (int) $month, day: (int) $day, year: (int) $year);
        }

        return -1;
    }

    /**
     * Returns the end-of-day timestamp of a tracking filename (trackingDDMMYYYY).
     */
    public function getTrackingFileDateEnd(string $file): int
    {
        if (Strings::strlen($file) >= 16) {
            $day = Strings::substr($file, start: 8, length: 2);
            $month = Strings::substr($file, start: 10, length: 2);
            $year = Strings::substr($file, start: 12, length: 4);

            return gmmktime(hour: 23, minute: 59, second: 59, month: (int) $month, day: (int) $day, year: (int) $year);
        }

        return -1;
    }

    /**
     * Returns date formatted according to user-defined format.
     */
    public function format(string $unformattedDate): string
    {
        try {
            $dateTime = new DateTime($unformattedDate);
            return $dateTime->format($this->configuration->get(item: 'main.dateFormat'));
        } catch (Exception $exception) {
            $this->configuration->getLogger()->error($exception->getMessage());
            return '';
        }
    }
}
