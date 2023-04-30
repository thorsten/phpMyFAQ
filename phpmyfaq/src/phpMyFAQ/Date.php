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
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-24
 */

namespace phpMyFAQ;

use DateTime;
use Exception;

/**
 * Class Date
 *
 * @package phpMyFAQ
 */
class Date
{
    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
    }

    /**
     * Converts the phpMyFAQ date format to a format similar to ISO 8601 standard.
     *
     * @param string $date      Date string
     * @param string $format    Date format
     * @param bool   $pmfFormat true if the passed date is in phpMyFAQ format, false if in
     *                          Unix timestamp format
     */
    public static function createIsoDate(string $date, string $format = 'Y-m-d H:i', bool $pmfFormat = true): string
    {
        if ($pmfFormat) {
            $dateString = strtotime(
                substr($date, 0, 4) . '-' .
                substr($date, 4, 2) . '-' .
                substr($date, 6, 2) . ' ' .
                substr($date, 8, 2) . ':' .
                substr($date, 10, 2)
            );
        } else {
            $dateString = $date;
        }

        return date($format, $dateString);
    }

    /**
     * Returns the timestamp of a tracking file.
     *
     * @param string $file     Filename
     * @param bool   $endOfDay End of day?
     */
    public static function getTrackingFileDate(string $file, bool $endOfDay = false): int
    {
        if (Strings::strlen($file) >= 16) {
            $day = Strings::substr($file, 8, 2);
            $month = Strings::substr($file, 10, 2);
            $year = Strings::substr($file, 12, 4);

            if (!$endOfDay) {
                $time = mktime(0, 0, 0, (int) $month, (int) $day, (int) $year);
            } else {
                $time = mktime(23, 59, 59, (int) $month, (int) $day, (int) $year);
            }

            return $time;
        } else {
            return -1;
        }
    }

    /**
     * Returns date formatted according to user defined format.
     */
    public function format(string $unformattedDate): string
    {
        try {
            $date = new DateTime($unformattedDate);
            return $date->format($this->config->get('main.dateFormat'));
        } catch (Exception $e) {
            $this->config->getLogger()->error($e->getMessage());
            return '';
        }
    }
}
