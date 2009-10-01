<?php
/**
 * phpMyFAQ Date class
 *
 * @category  phpMyFAQ
 * @package   PMF_Date
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
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
 * @since     2009-09-24
 * @copyright 2009 phpMyFAQ Team
 */
class PMF_Date
{
	/**
	 * Converts the phpMyFAQ date format to a format similar to ISO 8601 standard
     *
     * @param  string $date Date string
     * 
     * @return string
	 */
	public static function createIsoDate($date)
	{
        $datestring = strtotime(
            substr($date, 0, 4) . '-' .
            substr($date, 4, 2) . '-' .
            substr($date, 6, 2) . ' ' .
            substr($date, 8, 2) . ':' .
            substr($date, 10, 2));

        return date('Y-m-d H:i', $datestring);
	}
}