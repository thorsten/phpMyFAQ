<?php
/**
 * Attachment handler class 
 *
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id$
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
 * PMF_Atachment 
 * 
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
 */
class PMF_Attachment
{
    /**
     * Storage type filesystem
     * 
     * @var integer
     */
	const STORAGE_TYPE_FILESYSTEM = 0;
	
	/**
	 * Storage type database
	 * 
	 * @var integer
	 */
	const STORAGE_TYPE_DB         = 1;
	
}