<?php
/**
 * Attachment handler class 
 *
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id: Factory.php 4459 2009-06-10 15:57:47Z thorsten $
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

set_include_path(dirname(dirname(__FILE__)) . '/libs/phpseclib' . PATH_SEPARATOR . 
                 get_include_path());

require_once "Crypt/AES.php";
                 
/**
 * PMF_Atachment 
 * 
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id: Factory.php 4459 2009-06-10 15:57:47Z thorsten $
 * @copyright  2009 phpMyFAQ Team
 */
class PMF_Attachment_Factory
{
	/**
	 * Default encryption key
	 * 
	 * @var string
	 */
	private static $defaultEncKey     = null;
	
	/**
	 * Storage type
	 * 
	 * @var integer
	 */
	private static $storageType       = null;
	
	/**
	 * Weither file encryption is enabled
	 * 
	 * @var boolean
	 */
	private static $encryptionEnabled = null;
	
	/**
	 * Create an attachment exemplar
	 * 
	 * @param int    $id
	 * @param string $key
	 * @return unknown_type
	 */
	public static function create($id = null, $key = null)
	{	
		switch(self::$storageType) {
			case PMF_Attachment::STORAGE_TYPE_FILESYSTEM:
					$retval = new PMF_Attachment_File($id);
				break;
		}
		
		return $retval;
	}
	
	/**
	 * Initalizing factory with global attachment settings
	 * 
	 * @param int     $storageType
	 * @param string  $defaultEncKey
	 * @param boolean $encryptionEnabled
	 * 
	 * @return null
	 */
	public static function init($storageType, $defaultEncKey, $encryptionEnabled)
	{
		if(null === self::$storageType) {
			self::$storageType = $storageType;	
		}
		
		if(null === self::$defaultEncKey) {
			self::$defaultEncKey = $defaultEncKey;	
		}
		
		if(null === self::$encryptionEnabled) {
			self::$encryptionEnabled = $encryptionEnabled;
		}
	}

}
