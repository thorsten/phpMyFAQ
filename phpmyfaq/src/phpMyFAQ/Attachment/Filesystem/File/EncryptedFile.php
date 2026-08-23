<?php

/**
 * Encrypted file handler class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Attachment\Filesystem\File
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */

declare(strict_types=1);

namespace phpMyFAQ\Attachment\Filesystem\File;

use phpMyFAQ\Attachment\Filesystem\AbstractFile;
use phpseclib3\Crypt\AES;

/**
 * Class Encrypted
 *
 * @package phpMyFAQ\Attachment\Filesystem\File
 */
class EncryptedFile extends AbstractFile
{
    /**
     * Chunk delimiter.
     */
    private const string CHUNK_DELIMITER = 'ฒૐᥤ';

    /**
     * AES instance.
     */
    protected AES $aes;

    /**
     * @param string $filepath Path to the file in the filesystem
     * @param string $mode     Mode for fopen()
     * @param string $key      Encryption key
     * @throws FileException
     */
    public function __construct(string $filepath, string $mode, string $key)
    {
        // The AES cipher mode is fixed and independent of the fopen() mode.
        $this->aes = new AES('cbc');
        $this->aes->setKey($key);
        $this->aes->setIV(substr(hash('sha256', $key, binary: true), offset: 0, length: 16));

        parent::__construct($filepath, $mode);
    }

    public function putChunk(string $chunk): int|false
    {
        $content = $this->aes->encrypt($chunk) . self::CHUNK_DELIMITER;

        return fwrite($this->handle, $content);
    }

    /**
     * @throws FileException
     */
    public function copyTo(object|string $entry): bool
    {
        $return = false;

        if (is_string($entry)) {
            $entry = new VanillaFile($entry, AbstractFile::MODE_WRITE);
        } else {
            $entry->setMode(AbstractFile::MODE_WRITE);
        }

        if ($entry->isOk()) {
            while (!$this->eof()) {
                $entry->putChunk($this->getChunk());
            }

            $return = true;
        }

        return $return;
    }

    public function getChunk(): string
    {
        $readEnd = false;
        $chunk = '';
        $chunkDelimLen = strlen(self::CHUNK_DELIMITER);

        while (!$readEnd && !$this->eof()) {
            $chunk .= fread($this->handle, 1);
            $readEnd = self::CHUNK_DELIMITER === substr($chunk, -$chunkDelimLen);
        }

        $chunk = substr($chunk, 0, -$chunkDelimLen);

        return $chunk === '' || $chunk === '0' ? '' : $this->aes->decrypt($chunk);
    }
}
