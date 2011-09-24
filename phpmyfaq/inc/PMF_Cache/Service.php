<?php
/**
 * The PMF_Cache_Service class provides abstract cache functionality
 * 
 * PHP Version 5.2
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
 *
 * @category  phpMyFAQ
 * @package   PMF_Cache_Service
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2002-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
 * @copyright 2002-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
}
