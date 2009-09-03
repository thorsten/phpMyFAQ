<?php
/**
 * Attachment filesystem handler class 
 *
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id: File.php 4459 2009-06-10 15:57:47Z thorsten $
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
 * PMF_Atachment_File
 * 
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id: File.php 4459 2009-06-10 15:57:47Z thorsten $
 * @copyright  2009 phpMyFAQ Team
 */
class PMF_Attachment_File extends PMF_Attachment_Abstract implements PMF_Attachment_Interface
{		
	/**
	 * Construtor
	 * 
	 * @param int $id
	 * 
	 * @return PMF_Attachment
	 */
	private function __construct($id = null)
	{
		if(null !== $id) {
			$this->id = $id;
		}
		
//		parent::__construct();
	}
	
	/**
	 * Check weither the filestorage is ok
	 * 
	 * @return boolean
	 */
	public function isStorageOk()
	{
		
	}
	
	/**
	 * Save current attachment to the appropriate storage
	 * 
	 * @return boolean
	 */
	public function save()
	{
		
	}
	
	/**
	 * Retrieve file contents into a variable
	 * 
	 * @return string
	 */
	public function get()
	{
		
	}
	
	/**
	 * Output current file to stdout
	 * 
	 * @return null
	 */
	public function rawOut()
	{
		
	}
}
