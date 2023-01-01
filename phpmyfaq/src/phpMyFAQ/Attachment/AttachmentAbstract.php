<?php

/**
 * Abstract attachment class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */

namespace phpMyFAQ\Attachment;

use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;

/**
 * Class AttachmentAbstract
 *
 * @package phpMyFAQ\Attachment
 */
abstract class AttachmentAbstract
{
    /**
     * Attachment id.
     *
     * @var int
     */
    protected $id;

    /**
     * The key to encrypt with.
     */
    protected ?string $key = null;

    /**
     * Errors.
     *
     * @var string[]
     */
    protected array $error = [];

    /**
     * Database instance.
     */
    protected DatabaseDriver $db;

    /**
     * Record ID.
     */
    protected int $recordId;

    /**
     * Record language.
     */
    protected string $recordLang;

    /**
     * Real file md5 hash.
     */
    protected string $realHash;

    /**
     * Virtual unique md5 hash used for encrypted files.
     * Must equal real hash for unencrypted files.
     */
    protected string $virtualHash = '';

    /**
     * If this is set, the sh1 hashed key we got must equal to it.
     */
    protected string $passwordHash = '';

    /**
     * Filesize in bytes.
     */
    protected int $filesize;

    /**
     * Filename.
     */
    protected string $filename;

    /**
     * Encrypted.
     */
    protected ?bool $encrypted = null;

    /**
     * Attachment file mime type.
     */
    protected string $mimeType = '';

    /**
     * Constructor.
     *
     * @param mixed $id attachment id
     */
    public function __construct(mixed $id = null)
    {
        $this->db = Database::getInstance();

        if (null !== $id) {
            $this->id = $id;
            $this->getMeta();
        }
    }

    /**
     * Get meta data.
     */
    protected function getMeta(): bool
    {
        $hasMeta = false;

        $sql = sprintf(
            '
            SELECT 
                record_id, record_lang, real_hash, virtual_hash, password_hash,
                filename, filesize, encrypted, mime_type
            FROM
                %sfaqattachment
            WHERE 
                id = %d',
            Database::getTablePrefix(),
            (int)$this->id
        );

        $result = $this->db->query($sql);

        if ($result) {
            $assoc = $this->db->fetchArray($result);
            if (!empty($assoc)) {
                $this->recordId = $assoc['record_id'];
                $this->recordLang = $assoc['record_lang'];
                $this->realHash = $assoc['real_hash'];
                $this->virtualHash = $assoc['virtual_hash'];
                $this->passwordHash = $assoc['password_hash'];
                $this->filename = $assoc['filename'];
                $this->filesize = $assoc['filesize'];
                $this->encrypted = $assoc['encrypted'];
                $this->mimeType = $assoc['mime_type'];

                $hasMeta = true;
            }
        }

        return $hasMeta;
    }

    /**
     * Build attachment url.
     *
     * @param bool $forHTML either to use ampersands directly
     */
    public function buildUrl(bool $forHTML = true): string
    {
        $amp = true === $forHTML ? '&amp;' : '&';

        return "index.php?action=attachment{$amp}id={$this->id}";
    }

    /**
     * Set encryption key.
     *
     * @param string|null $key Encryption key
     * @param bool        $default if the key is default system-wide
     */
    public function setKey(?string $key, bool $default = true): void
    {
        $this->key = $key;
        $this->encrypted = null !== $key;

        // Not default means the key was set explicitly
        // for this attachment, so lets hash it
        if ($this->encrypted && !$default) {
            $this->passwordHash = sha1($key);
        }
    }

    /**
     * Set record language.
     *
     * @param string $lang record language
     */
    public function setRecordLang(string $lang): void
    {
        $this->recordLang = $lang;
    }

    /**
     * Get attachment id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Sets attachment id.
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get attachment record id.
     */
    public function getRecordId(): int
    {
        return $this->recordId;
    }

    /**
     * Set record id.
     *
     * @param int $id record id
     */
    public function setRecordId(int $id): void
    {
        $this->recordId = $id;
    }

    /**
     * Save attachment meta data.
     *
     * @return int saved attachment id
     *
     * @todo implement update case
     */
    public function saveMeta(): int
    {
        $attachmentTableName = sprintf('%sfaqattachment', Database::getTablePrefix());

        if (null == $this->id) {
            $this->id = $this->db->nextId($attachmentTableName, 'id');

            $sql = sprintf(
                "
                INSERT INTO 
                    %s
                (id, record_id, record_lang, real_hash, virtual_hash,
                password_hash, filename, filesize, encrypted, mime_type)
                    VALUES
                (%d, %d, '%s', '%s', '%s', '%s', '%s', %d, %d, '%s')",
                $attachmentTableName,
                $this->id,
                $this->recordId,
                $this->db->escape($this->recordLang),
                $this->db->escape($this->realHash),
                $this->db->escape($this->virtualHash),
                $this->db->escape($this->passwordHash),
                $this->db->escape($this->filename),
                $this->filesize,
                $this->encrypted ? 1 : 0,
                $this->db->escape($this->mimeType)
            );

            $this->db->query($sql);
        }

        return $this->id;
    }

    /**
     * Returns filename.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Returns the MIME type
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Update several meta things after it was saved.
     */
    protected function postUpdateMeta(): void
    {
        $sql = sprintf(
            "
            UPDATE
                %sfaqattachment
            SET virtual_hash = '%s',
                mime_type = '%s'
            WHERE id = %d",
            Database::getTablePrefix(),
            $this->db->escape($this->virtualHash),
            $this->readMimeType(),
            $this->id
        );

        $this->db->query($sql);
    }

    /**
     * The function is supposed to detect the file mime
     * type.
     */
    protected function readMimeType(): string
    {
        $ext = pathinfo($this->filename, PATHINFO_EXTENSION);
        $this->mimeType = AbstractMimeType::guessByExt($ext);

        return $this->mimeType;
    }

    /**
     * Generate hash based on current conditions.
     *
     * @return string
     *
     * NOTE The way a file is saved in the filesystem
     * is based on md5 hash. If the file is unencrypted,
     * it's md5 hash is used directly, otherwise a
     * hash based on several tokens gets generated.
     *
     * @return string|null
     * @throws AttachmentException
     */
    protected function mkVirtualHash(): ?string
    {
        if ($this->encrypted) {
            if (
                null === $this->id || null === $this->recordId
                || null === $this->realHash || null === $this->filename
                || null === $this->key
            ) {
                throw new AttachmentException(
                    'All of id, recordId, hash, filename, key is needed to generate fs hash for encrypted files'
                );
            }

            $src = $this->id . $this->recordId . $this->realHash .
                $this->filename . $this->key;
            $this->virtualHash = md5($src);
        } else {
            $this->virtualHash = $this->realHash;
        }

        return $this->virtualHash;
    }

    /**
     * Check if the same virtual hash exists more than once
     * in the database. If so, this means the file
     * is already uploaded as unencrypted.
     */
    protected function linkedRecords(): bool
    {
        $assoc = [];

        $sql = sprintf(
            "SELECT COUNT(1) AS count FROM  %sfaqattachment WHERE virtual_hash = '%s'",
            Database::getTablePrefix(),
            $this->db->escape($this->virtualHash),
        );

        $result = $this->db->query($sql);

        if ($result) {
            $assoc = $this->db->fetchArray($result);
        }

        return $assoc['count'] > 1;
    }

    /**
     * Remove metadata from the database.
     */
    protected function deleteMeta(): void
    {
        $sql = sprintf(
            'DELETE FROM %sfaqattachment WHERE id = %d',
            Database::getTablePrefix(),
            $this->id
        );

        $this->db->query($sql);
    }
}
