<?php
/**
 * The PMF_Cache_Service class provides abstract cache functionality
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Cache_Service
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2002-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-09-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Cache_Service
 *
 * @category  phpMyFAQ
 * @package   PMF_Cache_Service
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2002-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-09-23
 */
abstract class PMF_Cache_Service
{
	/**
	 * Children must implement the constructor with the appropriate config.
	 *
	 * @param array $config Cache configuration
	 *
	 * @return void
	 */
	abstract function __construct(array $config);

	/**
	 * Children must implement this to be able to clear the single article cache as well as all the related items.
	 *
	 * @param intereg $id Article id
	 *
	 * @return void
	 */
	abstract function clearArticle($id);

	/**
	 * Children must implement this to be able to clear all the cache contents at once.
	 *
	 * @return void
	 */
	abstract function clearAll();
}
