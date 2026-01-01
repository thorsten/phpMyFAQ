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
 * @copyright 2009-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */

declare(strict_types=1);

namespace phpMyFAQ\Attachment;

use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;

/**
 * Class AttachmentAbstract
 *
 * @package phpMyFAQ\Attachment
 */
abstract class AbstractAttachment
{
    /** @var mixed Attachment id. */
    protected mixed $id = 0;

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
    protected DatabaseDriver $databaseDriver;

    /**
     * Record ID.
     */
    protected ?int $recordId = null;

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
     * @param mixed $attachmentId attachment id
     */
    public function __construct(mixed $attachmentId = null)
    {
        $this->databaseDriver = Database::getInstance();

        if (null !== $attachmentId) {
            $this->id = $attachmentId;
            $this->getMeta();
        }
    }

    /**
     * Get meta data.
     */
    protected function getMeta(): bool
    {
        $hasMeta = false;

        $sql = sprintf('
            SELECT 
                record_id, record_lang, real_hash, virtual_hash, password_hash,
                filename, filesize, encrypted, mime_type
            FROM
                %sfaqattachment
            WHERE 
                id = %d', Database::getTablePrefix(), $this->id);

        $result = $this->databaseDriver->query($sql);

        if ($result) {
            $assoc = $this->databaseDriver->fetchArray($result);
            if ($assoc !== null && $assoc !== []) {
                $this->recordId = (int) $assoc['record_id'];
                $this->recordLang = $assoc['record_lang'];
                $this->realHash = $assoc['real_hash'];
                $this->virtualHash = $assoc['virtual_hash'];
                $this->passwordHash = $assoc['password_hash'];
                $this->filename = $assoc['filename'];
                $this->filesize = (int) $assoc['filesize'];
                $this->encrypted = (bool) $assoc['encrypted'];
                $this->mimeType = $assoc['mime_type'];

                $hasMeta = true;
            }
        }

        return $hasMeta;
    }

    public function buildUrl(): string
    {
        return sprintf('index.php?action=attachment&id=%d', $this->id);
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
        // for this attachment, so let's hash it
        if (!$this->encrypted) {
            return;
        }

        if ($default) {
            return;
        }

        $this->passwordHash = sha1((string) $key);
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
    public function setId(int $attachmentId): void
    {
        $this->id = $attachmentId;
    }

    /**
     * Get attachment record id.
     */
    public function getRecordId(): int
    {
        return $this->recordId ?? 0;
    }

    /**
     * Set record id.
     *
     * @param int $recordId record id
     */
    public function setRecordId(int $recordId): void
    {
        $this->recordId = $recordId;
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

        if (null === $this->id) {
            $this->id = $this->databaseDriver->nextId($attachmentTableName, 'id');

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
                $this->databaseDriver->escape($this->recordLang),
                $this->databaseDriver->escape($this->realHash),
                $this->databaseDriver->escape($this->virtualHash),
                $this->databaseDriver->escape($this->passwordHash),
                $this->databaseDriver->escape($this->filename),
                $this->filesize,
                $this->encrypted ? 1 : 0,
                $this->databaseDriver->escape($this->mimeType),
            );

            $this->databaseDriver->query($sql);
        }

        return $this->id;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getFilesize(): int
    {
        return $this->filesize;
    }

    public function getRealHash(): string
    {
        return $this->realHash;
    }

    /**
     * Update several meta things after it was saved.
     */
    protected function postUpdateMeta(): void
    {
        $sql = sprintf(
            "UPDATE %sfaqattachment SET virtual_hash = '%s', mime_type = '%s' WHERE id = %d",
            Database::getTablePrefix(),
            $this->databaseDriver->escape($this->virtualHash),
            $this->readMimeType(),
            $this->id,
        );

        $this->databaseDriver->query($sql);
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
     * @return string|null NOTE The way a file is saved in the filesystem
     * NOTE The way a file is saved in the filesystem
     * is based on md5 hash. If the file is unencrypted,
     *  it md5 hash is used directly, otherwise a
     * hash based on several tokens gets generated.
     * @throws AttachmentException
     */
    protected function mkVirtualHash(): ?string
    {
        if ($this->encrypted) {
            if (
                null === $this->id
                || null === $this->recordId
                || null === $this->realHash
                || null === $this->filename
                || null === $this->key
            ) {
                throw new AttachmentException(
                    'All of id, recordId, hash, filename, key is needed to generate fs hash for encrypted files',
                );
            }

            $src = $this->id . $this->recordId . $this->realHash . $this->filename . $this->key;
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
            "SELECT COUNT(1) AS count FROM %sfaqattachment WHERE virtual_hash = '%s'",
            Database::getTablePrefix(),
            $this->databaseDriver->escape($this->virtualHash),
        );

        $result = $this->databaseDriver->query($sql);

        if ($result) {
            $assoc = $this->databaseDriver->fetchArray($result);
        }

        return isset($assoc['count']) && $assoc['count'] > 1;
    }

    /**
     * Remove metadata from the database.
     */
    public function deleteMeta(): void
    {
        $sql = sprintf('DELETE FROM %sfaqattachment WHERE id = %d', Database::getTablePrefix(), $this->id);

        $this->databaseDriver->query($sql);
    }
}
