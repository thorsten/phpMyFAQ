<?php

/**
 * Attachment handler class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @since     2009-08-21
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @copyright 2009-2024 phpMyFAQ Team
 */

namespace phpMyFAQ\Attachment;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\AttachmentStorageType;
use phpMyFAQ\Language;

/**
 * Class AttachmentFactory
 *
 * @package phpMyFAQ\Attachment
 */
class AttachmentFactory
{
    /**
     * Default encryption key.
     */
    private static ?string $defaultKey = null;

    /**
     * Storage type.
     */
    private static ?int $storageType = 0;

    /**
     * File encryption is enabled.
     */
    private static ?bool $encryptionEnabled = null;

    /**
     * Create an attachment exemplar.
     *
     * @param int|null    $attachmentId  ID
     * @param string|null $key Key
     * @throws AttachmentException
     */
    public static function create(?int $attachmentId = null, ?string $key = null): File
    {
        $return = match (self::$storageType) {
            AttachmentStorageType::FILESYSTEM->value => new File($attachmentId),
            default => throw new AttachmentException('Unknown attachment storage type'),
        };

        /*
         * If encryption isn't enabled, just ignoring all keys
         */
        if (self::$encryptionEnabled) {
            $key ??= self::$defaultKey;
        }

        $return->setKey($key);

        return $return;
    }

    /**
     * Fetch all record attachments.
     *
     * @param int           $recordId ID of the record
     *
     * @throws AttachmentException
     * @return File[]
     */
    public static function fetchByRecordId(Configuration $configuration, int $recordId): array
    {
        $files = [];

        $sql = sprintf(
            "SELECT id FROM %sfaqattachment WHERE record_id = %d AND record_lang = '%s'",
            Database::getTablePrefix(),
            $recordId,
            Language::$language
        );

        $result = $configuration->getDb()->fetchAll($configuration->getDb()->query($sql));

        if ($result !== null && $result !== []) {
            foreach ($result as $item) {
                $files[] = self::create((int) $item->id);
            }
        }

        return $files;
    }

    /**
     * Initializing factory with global attachment settings.
     *
     * @param string $defaultKey        Default key
     * @param bool   $encryptionEnabled Enabled encryption?
     */
    public static function init(string $defaultKey, bool $encryptionEnabled): void
    {
        if (null === self::$defaultKey) {
            self::$defaultKey = $defaultKey;
        }

        if (null === self::$encryptionEnabled) {
            self::$encryptionEnabled = $encryptionEnabled;
        }
    }
}
