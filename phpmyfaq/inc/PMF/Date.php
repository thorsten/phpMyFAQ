<?php

/**
 * phpMyFAQ Date class.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-24
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Date.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-24
 */
class PMF_Date
{
    /**
     * @var PMF_Configuration
     */
    private $_config = null;

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Date
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
    }

    /**
     * Converts the phpMyFAQ date format to a format similar to ISO 8601 standard.
     *
     * @param string $date      Date string
     * @param string $format    Date format
     * @param bool   $pmfFormat true if the passed date is in phpMyFAQ format, false if in
     *                          Unix timestamp format
     *
     * @return string
     */
    public static function createIsoDate($date, $format = 'Y-m-d H:i', $pmfFormat = true)
    {
        if ($pmfFormat) {
            $dateString = strtotime(
                substr($date, 0, 4).'-'.
                substr($date, 4, 2).'-'.
                substr($date, 6, 2).' '.
                substr($date, 8, 2).':'.
                substr($date, 10, 2)
            );
        } else {
            $dateString = $date;
        }

        return date($format, $dateString);
    }

    /**
     * Converts the phpMyFAQ/Unix date format to the RFC 822 format.
     *
     * @param string $date      Date string
     * @param bool   $pmfFormat true if the passed date is in phpMyFAQ format, false if in
     *                          Unix timestamp format
     *
     * @return string RFC 822 date
     */
    public static function createRFC822Date($date, $pmfFormat = true)
    {
        return self::createIsoDate($date, DATE_RFC822, $pmfFormat);
    }

    /**
     * Returns the timestamp of a tracking file.
     *
     * @param string $file     Filename
     * @param bool   $endOfDay End of day?
     *
     * @return int
     */
    public static function getTrackingFileDate($file, $endOfDay = false)
    {
        if (PMF_String::strlen($file) >= 16) {
            $day = PMF_String::substr($file, 8, 2);
            $month = PMF_String::substr($file, 10, 2);
            $year = PMF_String::substr($file, 12, 4);

            if (!$endOfDay) {
                $time = mktime(0, 0, 0, $month, $day, $year);
            } else {
                $time = mktime(23, 59, 59, $month, $day, $year);
            }

            return $time;
        } else {
            return -1;
        }
    }

    /**
     * Returns date formatted according to user defined format.
     *
     * @param string $unformattedDate
     *
     * @return string
     */
    public function format($unformattedDate)
    {
        $date = new DateTime($unformattedDate);

        return $date->format($this->_config->get('main.dateFormat'));
    }
}
