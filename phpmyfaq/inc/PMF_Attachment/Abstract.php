<?php
/**
 * Abstract attachment class
 *
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id: Abstract.php 4459 2009-06-10 15:57:47Z thorsten $
 * @copyright  2009 phpMyFAQ Team
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
 * PMF_Atachment_Interface
 * 
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id: Abstract.php 4459 2009-06-10 15:57:47Z thorsten $
 * @copyright  2009 phpMyFAQ Team
 */
interface PMF_Attachment_Interface
{
	/**
	 * The key to encrypt with
	 * 
	 * @var string
	 */
	protected $encryptionKey;
	
	/**
	 * Errors
	 * @var array
	 */
	protected $error = array();
	
	/**
	 * Build attachment url
	 * 
	 * @param boolean $forHTML either to use ampersands directly
	 * 
	 * @return string
	 */
	public function buildUrl($forHTML = true)
	{
		
	}
	
	/**
	 * Set encryption key
	 * 
	 * @return null
	 */
	public function setKey()
	{
		
	}
}
