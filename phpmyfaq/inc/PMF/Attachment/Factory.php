<?php

/**
 * Attachment handler class.
 *
 * PHP Version 5.5
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
 * @copyright 2009-2019 phpMyFAQ Team
 */
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
 * @copyright 2009-2019 phpMyFAQ Team
 */
class PMF_Attachment_Factory
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
     * @throws PMF_Attachment_Exception
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

            default:
                throw new PMF_Attachment_Exception('Unknown attachment storage type');
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
     * @param PMF_Configuration $config
     * @param int               $recordId ID of the record
     *
     * @return integer|null
     */
    public static function fetchByRecordId(PMF_Configuration $config, $recordId)
    {
        $retval = [];

        $sql = sprintf(
            "
                        SELECT
                            id
                        FROM
                            %sfaqattachment
                        WHERE
                            record_id = %d
                        AND
                            record_lang = '%s'",
            PMF_Db::getTablePrefix(),
            $recordId,
            PMF_Language::$language
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
     * Initalizing factory with global attachment settings.
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
}
