<?php

/**
 * Encrypted file handler class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Attachment\Filesystem\File
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2020 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */

namespace phpMyFAQ\Attachment\Filesystem\File;

use phpMyFAQ\Attachment\File;
use phpseclib\Crypt\AES;

// phpcs:disable
require PMF_ROOT_DIR . '/src/libs/phpseclib/Crypt/AES.php';
// phpcs:enable

/**
 * Class Encrypted
 *
 * @package phpMyFAQ\Attachment\Filesystem\File
 */
class EncryptedFile extends File
{
    /**
     * Chunk delimiter.
     *
     * @var string
     */
    private const CHUNKDELIMITER = 'ฒૐᥤ';

    /**
     * AES instance.
     *
     * @var object
     */
    protected $aes;

    /**
     * @param string $filepath
     * @param string $mode
     * @param string $key
     */
    public function __construct($filepath, $mode, $key)
    {
        $this->aes = new AES();
        $this->aes->setKey($key);

        parent::__construct($filepath, $mode);
    }

    /**
     * @inheritdoc
     */
    public function putChunk($chunk)
    {
        $content = $this->aes->encrypt($chunk) . self::CHUNKDELIMITER;

        return fwrite($this->handle, $content);
    }

    /**
     * @inheritdoc
     * @throws     FileException
     */
    public function copyTo($target): bool
    {
        $retval = false;

        if (is_string($target)) {
            $target = new VanillaFile($target, self::MODE_WRITE);
        } else {
            $target->setMode(self::MODE_WRITE);
        }

        if ($target->isOk()) {
            while (!$this->eof()) {
                $target->putChunk($this->getChunk());
            }

            $retval = true;
        }

        return $retval;
    }

    /**
     * @inheritdoc
     */
    public function getChunk(): string
    {
        $readEnd = false;
        $chunk = '';
        $chunkDelimLen = strlen(self::CHUNKDELIMITER);

        while (!$readEnd && !$this->eof()) {
            $chunk .= fread($this->handle, 1);
            $readEnd = self::CHUNKDELIMITER == substr($chunk, -$chunkDelimLen);
        }

        $chunk = substr($chunk, 0, -$chunkDelimLen);

        return empty($chunk) ? '' : $this->aes->decrypt($chunk);
    }
}
