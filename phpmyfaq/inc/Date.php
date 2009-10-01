<?php
/**
 * phpMyFAQ Date class
 *
 * @category  phpMyFAQ
 * @package   PMF_Date
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @since     2009-09-24
 * @version   git: $Id$
 * @copyright 2009 phpMyFAQ Team
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

/**
 * PMF_Date
 *
 * @category  phpMyFAQ
 * @package   PMF_Date
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @since     2009-09-24
 * @copyright 2009 phpMyFAQ Team
 */
class PMF_Date
{
	/**
	 * Converts the phpMyFAQ date format to a format similar to ISO 8601 standard
     *
     * @param string  $date      Date string
     * @param string  $format    Date format
     * @param boolean $pmfFormat true if the passed date is in phpMyFAQ format, false if in
     *                           Unix timestamp format
     * 
     * @return string
	 */
	public static function createIsoDate($date, $format = 'Y-m-d H:i', $pmfFormat = true)
	{
		if ($pmfFormat) {
            $dateString = strtotime(
                substr($date, 0, 4) . '-' .
                substr($date, 4, 2) . '-' .
                substr($date, 6, 2) . ' ' .
                substr($date, 8, 2) . ':' .
                substr($date, 10, 2));
		} else {
			$dateString = $date;
		}

        return date($format, $dateString);
	}
	
    /**
     * Converts the phpMyFAQ/Unix date format to the RFC 822 format
     *
     * @param string  $date      Date string
     * @param boolean $pmfFormat true if the passed date is in phpMyFAQ format, false if in
     *                           Unix timestamp format
     * 
     * @return  string  RFC 822 date
     */
    public static function createRFC822Date($date, $pmfFormat = true)
    {
        $rfc822TZ = date('O');
        if ('+0000' == $rfc822TZ) {
            $rfc822TZ = 'GMT';
        }

        return self::createIsoDate($date, 'D, d M Y H:i:s', $pmfFormat) . ' ' . $rfc822TZ;
    }

    /**
     * Converts the phpMyFAQ/Unix date format to the ISO 8601 format
     *
     * See the spec here: http://www.w3.org/TR/NOTE-datetime
     *
     * @param string  $date      Date string
     * @param boolean $pmfFormat true if the passed date is in phpMyFAQ format, false if in
     *                           Unix timestamp format
     * 
     * @return  string  ISO 8601 date
     */
    public static function createISO8601Date($date, $pmfFormat = true)
    {
        $iso8601TZD = date('P');
        if ('+00:00' == $iso8601TZD) {
            $iso8601TZD = 'Z';
        }

        return self::createIsoDate($date, 'Y-m-d\TH:i:s', $pmfFormat) . $iso8601TZD;
    }
	
}