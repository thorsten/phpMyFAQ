<?php

namespace phpMyFAQ\Attachment;

/**
 * Abstract attachment class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2009-08-21
 */

use phpMyFAQ\Db;
use phpMyFAQ\Db\Driver;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Attachment_Abstract.
 *
 * @package phpMyFAQ
 * @author Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2009-08-21
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
     *
     * @var string
     */
    protected $key;

    /**
     * Errors.
     *
     * @var array
     */
    protected $error = [];

    /**
     * Database instance.
     *
     * @var Driver
     */
    protected $db;

    /**
     * Record ID.
     *
     * @var int
     */
    protected $recordId;

    /**
     * Record language.
     *
     * @var string
     */
    protected $recordLang;

    /**
     * Real file md5 hash.
     *
     * @var string
     */
    protected $realHash;

    /**
     * Virtual unique md5 hash used for encrypted files.
     * Must equal real hash for unencrypted files.
     *
     * @var string
     */
    protected $virtualHash;

    /**
     * If this is set, the sh1 hashed key we got must equal to it.
     *
     * @var string
     */
    protected $passwordHash;

    /**
     * Filesize in bytes.
     *
     * @var int
     */
    protected $filesize;

    /**
     * Filename.
     *
     * @var string
     */
    protected $filename;

    /**
     * Encrypted.
     *
     * @var string
     */
    protected $encrypted;

    /**
     * Attachment file mime type.
     *
     * @var string
     */
    protected $mimeType;
    /**
     * Constructor.
     *
     * @param int $id attachment id
     */
    public function __construct($id = null)
    {
        $this->db = Db::getInstance();

        if (null !== $id) {
            $this->id = $id;
            $this->getMeta();
        }
    }

    /**
     * Build attachment url.
     *
     * @param bool $forHTML either to use ampersands directly
     *
     * @return string
     */
    public function buildUrl($forHTML = true)
    {
        $amp = true === $forHTML ? '&amp;' : '&';

        return "index.php?action=attachment{$amp}id={$this->id}";
    }

    /**
     * Set encryption key.
     *
     * @param string $key     encryption key
     * @param bool   $default if the key is default system wide
     */
    public function setKey($key, $default = true)
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
     * Set record id.
     *
     * @param int $id record id
     */
    public function setRecordId($id)
    {
        $this->recordId = $id;
    }

    /**
     * Set record language.
     *
     * @param string $lang record language
     */
    public function setRecordLang($lang)
    {
        $this->recordLang = $lang;
    }

    /**
     * Get attachment id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Sets attachment id.
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get attachment record id.
     *
     * @return int
     */
    public function getRecordId(): int
    {
        return $this->recordId;
    }

    /**
     * Get meta data.
     *
     * @return bool
     */
    protected function getMeta(): bool
    {
        $retval = false;

        $sql = sprintf('
            SELECT 
                record_id, record_lang, real_hash, virtual_hash, password_hash,
                filename, filesize, encrypted, mime_type
            FROM
                %sfaqattachment
            WHERE 
                id = %d',
            Db::getTablePrefix(),
            (int)$this->id);

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

                $retval = true;
            }
        }

        return $retval;
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
        $faqattTableName = sprintf('%sfaqattachment', Db::getTablePrefix());

        if (null == $this->id) {
            $this->id = $this->db->nextId($faqattTableName, 'id');

            $sql = sprintf("
                INSERT INTO 
                    %s
                (id, record_id, record_lang, real_hash, virtual_hash,
                password_hash, filename, filesize, encrypted, mime_type)
                    VALUES
                (%d, %d, '%s', '%s', '%s', '%s', '%s', %d, %d, '%s')",
                $faqattTableName,
                $this->id,
                $this->recordId,
                $this->recordLang,
                $this->realHash,
                $this->virtualHash,
                $this->passwordHash,
                $this->filename,
                $this->filesize,
                $this->encrypted ? 1 : 0,
                $this->mimeType
            );

            $this->db->query($sql);
        }

        return $this->id;
    }

    /**
     * Update several meta things after it was saved.
     */
    protected function postUpdateMeta()
    {
        $sql = sprintf("
            UPDATE
                %sfaqattachment
            SET virtual_hash = '%s',
                mime_type = '%s'
            WHERE id = %d",
            Db::getTablePrefix(),
            $this->virtualHash,
            $this->readMimeType(),
            $this->id
        );

        $this->db->query($sql);
    }

    /**
     * The function is supposed to detect the file mime
     * type.
     *
     * @return string
     */
    protected function readMimeType(): string
    {
        $ext = pathinfo($this->filename, PATHINFO_EXTENSION);
        $this->mimeType = MimeType::guessByExt($ext);

        return $this->mimeType;
    }

    /**
     * Generate hash based on current conditions.
     *
     * @throws Exception
     *
     * @return string
     * 
     * NOTE The way a file is saved in the filesystem
     * is based on md5 hash. If the file is unencrypted,
     * it's md5 hash is used directly, otherwise a
     * hash based on several tokens gets generated.
     *
     * @throws Exception
     *
     * @return string
     */
    protected function mkVirtualHash(): string
    {
        if ($this->encrypted) {
            if (null === $this->id || null === $this->recordId ||
               null === $this->realHash || null === $this->filename ||
               null === $this->key) {
                throw new Exception(
                    'All of id, recordId, hash, filename, key is needed to generate fs hash for encrypted files'
                );
            }

            $src = $this->id.$this->recordId.$this->realHash.
                        $this->filename.$this->key;
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
     *  
     * @return bool
     */
    protected function linkedRecords(): bool
    {
        $assoc = [];

        $sql = sprintf(
            "SELECT COUNT(1) AS count FROM  %sfaqattachment WHERE virtual_hash = '%s'",
            Db::getTablePrefix(),
            $this->virtualHash
        );

        $result = $this->db->query($sql);

        if ($result) {
            $assoc = $this->db->fetchArray($result);
        }

        return $assoc['count'] > 1;
    }

    /**
     * Remove meta data from the db.
     */
    protected function deleteMeta()
    {
        $sql = sprintf(
            'DELETE FROM %sfaqattachment WHERE id = %d',
            Db::getTablePrefix(),
            $this->id
        );

        $this->db->query($sql);
    }

    /**
     * Validate attached file with the real hash.
     *
     * @return bool
     */
    public function validate(): bool
    {
        // TODO implement this
    }

    /**
     * Returns filename.
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }
}
