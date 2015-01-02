<?php
/**
 * The PMF_Cache_Varnish class implements the varnish cache service functionality
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Cache_Varnish
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2002-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-09-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Cache_Varnish
 *
 * @category  phpMyFAQ
 * @package   PMF_Cache_Varnish
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2002-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-09-23
 */
class PMF_Cache_Varnish extends PMF_Cache_Service
{
	protected $instance = NULL;

	/**
	 * Constructor.
	 *
	 * @param array $config Cache configuration
	 *
	 * @return void
	 */
	public function __construct(array $config)
	{
		$this->instance = new VarnishAdmin($config);	

		try {
			if(!$this->instance->connect()) {
				throw new VarnishException("Connection failed\n");
			}   
		} catch (VarnishException $e) {
			echo $e->getMessage();
		}

		try {
			if(!$this->instance->auth()) {
				throw new VarnishException("Auth failed\n");
			}   
		} catch (VarnishException $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Clear all cached article related items.
	 *
	 * @param intereg $id Article id
	 *
	 * @return void
	 */
	public function clearArticle($id)
	{
		$this->clearAll();
	}

	public function clearAll()
	{
		$this->instance->banUrl(".*");
	}

}
