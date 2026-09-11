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
 * @copyright 2009-2026 phpMyFAQ Team
 */

declare(strict_types=1);

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
     * Key lengths in bytes accepted by AES (128, 192 and 256 bit).
     */
    private const array SUPPORTED_KEY_LENGTHS = [16, 24, 32];

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
     * For an existing attachment the persisted per-record "encrypted" flag
     * decides whether a key is applied, independent of the global switch: the
     * configured key stays available for records already marked encrypted,
     * and records stored in plaintext are read as plaintext. The global switch
     * only decides how new attachments are written.
     *
     * @param int|null    $attachmentId  ID
     * @param string|null $key Key
     * @throws AttachmentException
     */
    public static function create(?int $attachmentId = null, ?string $key = null): File
    {
        $return = match (self::$storageType) {
            AttachmentStorageType::FILESYSTEM->value, AttachmentStorageType::S3->value => new File($attachmentId),
            default => throw new AttachmentException('Unknown attachment storage type'),
        };

        $key ??= self::$defaultKey;
        if ($key === '') {
            $key = null;
        }

        if ($return->hasMeta()) {
            $return->setKey($return->isEncrypted() ? $key : null);

            return $return;
        }

        if (!self::$encryptionEnabled) {
            $return->setKey(null);

            return $return;
        }

        // Fail before any metadata or file is written, not inside the cipher.
        if ($key === null || !self::isSupportedKey($key)) {
            throw new AttachmentException(
                'Attachment encryption is enabled, but the configured encryption key is not 16, 24 or 32 bytes long',
            );
        }

        $return->setKey($key);

        return $return;
    }

    /**
     * Checks whether the given key has a length supported by AES.
     */
    public static function isSupportedKey(string $key): bool
    {
        return in_array(strlen($key), self::SUPPORTED_KEY_LENGTHS, strict: true);
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
            Language::$language,
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
     * Fetch record attachments with pagination and sorting support.
     *
     * @param Configuration $configuration Configuration instance
     * @param int $recordId ID of the record
     * @param int $limit Number of items to fetch
     * @param int $offset Starting offset
     * @param string $sortField Field to sort by (id, filename, mime_type, filesize, created)
     * @param string $sortOrder Sort order (ASC or DESC)
     *
     * @throws AttachmentException
     * @return array Array of attachment data with filename and URL
     */
    public static function fetchByRecordIdPaginated(
        Configuration $configuration,
        int $recordId,
        int $limit = 25,
        int $offset = 0,
        string $sortField = 'id',
        string $sortOrder = 'ASC',
    ): array {
        $files = [];

        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['id', 'filename', 'mime_type', 'filesize', 'created'];
        if (!in_array($sortField, $allowedSortFields, strict: true)) {
            $sortField = 'id';
        }

        // Validate sort order
        $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

        $sql = sprintf(
            "SELECT id FROM %sfaqattachment WHERE record_id = %d AND record_lang = '%s' ORDER BY %s %s LIMIT %d OFFSET %d",
            Database::getTablePrefix(),
            $recordId,
            $configuration->getDb()->escape(Language::$language),
            $sortField,
            $sortOrder,
            $limit,
            $offset,
        );

        $result = $configuration->getDb()->fetchAll($configuration->getDb()->query($sql));

        if ($result !== null && $result !== []) {
            foreach ($result as $item) {
                $attachment = self::create((int) $item->id);
                $files[] = [
                    'filename' => $attachment->getFilename(),
                    'url' => $configuration->getDefaultUrl() . $attachment->buildUrl(),
                ];
            }
        }

        return $files;
    }

    /**
     * Count total number of attachments for a record.
     *
     * @param Configuration $configuration Configuration instance
     * @param int $recordId ID of the record
     * @return int Total count of attachments
     */
    public static function countByRecordId(Configuration $configuration, int $recordId): int
    {
        $sql = sprintf(
            "SELECT COUNT(*) as total FROM %sfaqattachment WHERE record_id = %d AND record_lang = '%s'",
            Database::getTablePrefix(),
            $recordId,
            $configuration->getDb()->escape(Language::$language),
        );

        $result = $configuration->getDb()->query($sql);
        $row = $configuration->getDb()->fetchObject($result);

        return $row instanceof \stdClass ? (int) ($row->total ?? 0) : 0;
    }

    /**
     * Initializing the factory with global attachment settings.
     *
     * @param string $defaultKey        Default key
     * @param bool   $encryptionEnabled Enabled encryption?
     * @param int|null $storageType Optional storage type (defaults to filesystem)
     */
    public static function init(string $defaultKey, bool $encryptionEnabled, ?int $storageType = null): void
    {
        // Always apply the given settings: keeping the first-ever values would
        // let a long-running process (e.g. FrankenPHP worker mode) retain a stale
        // plaintext configuration after attachment encryption has been enabled.
        self::$defaultKey = $defaultKey;
        self::$encryptionEnabled = $encryptionEnabled;

        if ($storageType !== null) {
            self::$storageType = $storageType;
        }
    }

    /**
     * (Re-)initializes the factory from the current configuration values.
     *
     * Long-running workers (e.g. FrankenPHP worker mode) run the bootstrap
     * once, so this must be called again whenever the attachment settings are
     * saved; otherwise the process keeps its startup settings until restart.
     */
    public static function initFromConfiguration(Configuration $configuration): void
    {
        self::init(
            (string) $configuration->get('records.defaultAttachmentEncKey'),
            $configuration->get('records.enableAttachmentEncryption') === true,
            (int) $configuration->get('records.attachmentsStorageType'),
        );
    }
}
