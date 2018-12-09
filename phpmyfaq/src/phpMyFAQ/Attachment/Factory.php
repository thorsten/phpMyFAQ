<?php

namespace phpMyFAQ\Attachment;

/**
 * Attachment handler class.
 *
 * 
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @since     2009-08-21
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @copyright 2009-2018 phpMyFAQ Team
 */

use phpMyFAQ\Attachment;
use phpMyFAQ\Configuration;
use phpMyFAQ\Db;
use phpMyFAQ\Language;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Attachment_Factory.
 *
 * @category  phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @since     2009-08-21
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @copyright 2009-2018 phpMyFAQ Team
 */
class Factory
{
    /**
     * Default encryption key.
     *
     * @var string
     */
    private static $defaultKey = null;

    /**
     * Storage type.
     *
     * @var int
     */
    private static $storageType = null;

    /**
     * File encryption is enabled.
     *
     * @var bool
     */
    private static $encryptionEnabled = null;

    /**
     * Create an attachment exemplar.
     *
     * @param int    $id  ID
     * @param string $key Key
     *
     * @throws Exception
     *
     * @return File
     */
    public static function create($id = null, $key = null)
    {
        $retval = null;

        switch (self::$storageType) {
            case Attachment::STORAGE_TYPE_FILESYSTEM:
                $retval = new File($id);
                break;

            default:
                throw new Exception('Unknown attachment storage type');
                break;
        }

        /*
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
     * Fetch all record attachments.
     *
     * @param Configuration $config
     * @param int               $recordId ID of the record
     *
     * @throws Exception
     *
     * @return array
     */
    public static function fetchByRecordId(Configuration $config, $recordId)
    {
        $retval = [];

        $sql = sprintf(
            "SELECT
                id
            FROM
                %sfaqattachment
            WHERE
                record_id = %d
            AND
                record_lang = '%s'",
            Db::getTablePrefix(),
            $recordId,
            Language::$language
        );

        $result = $config->getDb()->fetchAll($config->getDb()->query($sql));
        if ($result) {
            foreach ($result as $item) {
                $retval[] = self::create($item->id);
            }
        }
        reset($retval);

        return $retval;
    }

    /**
     * Initializing factory with global attachment settings.
     *
     * @param int    $storageType       Storage type
     * @param string $defaultKey        Default key
     * @param bool   $encryptionEnabled Enabled encryption?
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

    /**
     * Re-arranges the $_FILES array for multiple file uploads.
     *
     * @param $filePost
     *
     * @return array
     */
    public static function rearrangeUploadedFiles(&$filePost) {

        $filesArray = [];
        $filesCount = count($filePost['name']);
        $filesKeys = array_keys($filePost);

        for ($i = 0; $i < $filesCount; $i++) {
            foreach ($filesKeys as $key) {
                $filesArray[$i][$key] = $filePost[$key][$i];
            }
        }

        return $filesArray;
    }
}
