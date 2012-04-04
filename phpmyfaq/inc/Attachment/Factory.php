<?php
/**
 * Attachment handler class 
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Attachment
 * @author    Anatoliy Belsky <ab@php.net>
 * @since     2009-08-21
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @copyright 2009-2012 phpMyFAQ Team
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR .
    'phpseclib' . DIRECTORY_SEPARATOR . 'Crypt' . DIRECTORY_SEPARATOR .'AES.php';
                 
/**
 * PMF_Atachment_Factory
 *
 * @category  phpMyFAQ
 * @package   Attachment
 * @author    Anatoliy Belsky <ab@php.net>
 * @since     2009-08-21
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @copyright 2009-2012 phpMyFAQ Team
 */
class PMF_Attachment_Factory
{
    /**
     * Default encryption key
     *
     * @var string
     */
    private static $defaultKey = null;
    
    /**
     * Storage type
     *
     * @var integer
     */
    private static $storageType = null;
    
    /**
     * Weither file encryption is enabled
     *
     * @var boolean
     */
    private static $encryptionEnabled = null;
    
    /**
     * Create an attachment exemplar
     *
     * @param int    $id  ID
     * @param string $key Key
     *
     * @return PMF_Attachment_File
     */
    public static function create($id = null, $key = null)
    {    
        $retval = null;

        switch (self::$storageType) {
            case PMF_Attachment::STORAGE_TYPE_FILESYSTEM:
                $retval = new PMF_Attachment_File($id);
                break;
                
            case PMF_Attachment::STORAGE_TYPE_DB:
                $retval = new PMF_Attachment_DB($id);
                break;
                
            default:
                throw new PMF_Attachment_Exception('Unknown attachment storage type');
                break;
        }
        
        /**
         * If encryption isn't enabled, just ignoring all keys
         */
        if (self::$encryptionEnabled) {
            $key = null == $key ? self::$defaultKey : $key;
        } else {
            $key = null;
        }
        
        $retval->setKey($key);
        
        return $retval;
    }
    
    /**
     * Fetch all record attachments
     *
     * @param integer $recordId ID of the record
     *
     * @return array
     */
    public static function fetchByRecordId($recordId)
    {
        $retval = array();
        $db     = PMF_Db::getInstance();
        
        $sql = sprintf("
            SELECT
                id
            FROM 
                %sfaqattachment
            WHERE 
                record_id = %d
            AND
                record_lang = '%s'",
            SQLPREFIX,
            $recordId,
            PMF_Language::$language);
        
        $result = $db->fetchAll($db->query($sql));
        if ($result) {
            foreach ($result as $item) {
                $retval[] = self::create($item->id);
            }
        }
        reset($retval);
        
        return $retval;
    }
    
    /**
     * Initalizing factory with global attachment settings
     *
     * @param int     $storageType       Storage type   
     * @param string  $defaultKey        Default key
     * @param boolean $encryptionEnabled Enabled encryption?
     *
     * @return null
     */
    public static function init($storageType, $defaultKey, $encryptionEnabled)
    {
        if (null === self::$storageType) {
            self::$storageType = $storageType;    
        }
        
        if (null === self::$defaultKey) {
            self::$defaultKey = $defaultKey;    
        }
        
        if (null === self::$encryptionEnabled) {
            self::$encryptionEnabled = $encryptionEnabled;
        }
    }

}