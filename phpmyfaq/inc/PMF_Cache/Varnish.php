<?php
/**
 * The PMF_Cache_Varnish class implements the varnish cache service functionality
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
 * @package   PMF_Cache_Varnish
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
 * PMF_Cache_Varnish
 *
 * @category  phpMyFAQ
 * @package   PMF_Cache_Varnish
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2002-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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

	}
}
