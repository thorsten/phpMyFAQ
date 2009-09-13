<?php
/**
 * Attachment migration handler
 *
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-09-13
 * @version    SVN: $Id: Migration.php 4885 2009-09-06 20:56:12Z anatoliy $
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
 * PMF_Atachment_Migration
 * 
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-09-13
 * @version    SVN: $Id: Migration.php 4885 2009-09-06 20:56:12Z anatoliy $
 * @copyright  2009 phpMyFAQ Team
 */
class PMF_Attachment_Migration
{
    /**
     * Migrate 2.0.x, 2.5.x to 2.6+ without encryption
     */
    const MIGRATION_TYPE1 = 1;
    
    /**
     * Migrate 2.0.x, 2.5.x to 2.6+ encrypting with default key
     */
    const MIGRATION_TYPE2 = 2;
        
    /**
     * Migrate encrypted to unencrypted.
     * NOTE this will migrate only files encrypted
     * with default key
     */
    const MIGRATION_TYPE3 = 3;

    /**
     * Migrate files encrypted with default key
     * to unencrypted files
     */
    const MIGRATION_TYPE4 = 4;
    
    /**
     * Migrate
     * 
     * @param integer $migrationType how to migrate
     * @param array   $options       migration options
     * 
     * @return boolean
     */
    public function doMigrate($migrationType, $options)
    {
        
    } 
}