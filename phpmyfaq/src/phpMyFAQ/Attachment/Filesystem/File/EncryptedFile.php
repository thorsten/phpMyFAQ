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
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */

namespace phpMyFAQ\Attachment\Filesystem\File;

use phpMyFAQ\Attachment\File;
use phpMyFAQ\Attachment\Filesystem\AbstractFile;
use phpseclib3\Crypt\AES;

/**
 * Class Encrypted
 *
 * @package phpMyFAQ\Attachment\Filesystem\File
 */
class EncryptedFile extends File
{
    /**
     * Chunk delimiter.
     */
    private const string CHUNK_DELIMITER = 'ฒૐᥤ';

    /**
     * AES instance.
     */
    protected AES $aes;

    /** @var resource */
    private $handle;

    /**
     * @param string $filepath
     */
    public function __construct($filepath, string $mode, string $key)
    {
        $this->aes = new AES($mode);
        $this->aes->setKey($key);

        parent::__construct($filepath);
    }

    public function putChunk(string $chunk): int|false
    {
        $content = $this->aes->encrypt($chunk) . self::CHUNK_DELIMITER;

        return fwrite($this->handle, $content);
    }

    /**
     * @throws FileException
     */
    public function copyTo(string $target): bool
    {
        $return = false;

        if (is_string($target)) {
            $target = new VanillaFile($target, AbstractFile::MODE_WRITE);
        } else {
            $target->setMode(AbstractFile::MODE_WRITE);
        }

        if ($target->isOk()) {
            while (!$this->eof()) {
                $target->putChunk($this->getChunk());
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
