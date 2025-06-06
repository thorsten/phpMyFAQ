<?php

/**
 * Unencrypted file handler class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */

namespace phpMyFAQ\Attachment\Filesystem\File;

use phpMyFAQ\Attachment\Filesystem\AbstractFile;

/**
 * Class VanillaFile
 *
 * @package phpMyFAQ\Attachment\Filesystem\File
 */
class VanillaFile extends AbstractFile
{
    /**
     * Chunk size read/write operations will deal with (in bytes).
     */
    private const int CHUNK_SIZE = 512;

    /**
     * @inheritdoc
     */
    public function putChunk(string $chunk): bool
    {
        return fwrite($this->handle, $chunk);
    }

    /**
     * @inheritdoc
     */
    public function copyTo(object|string $entry): bool
    {
        $doSimple = is_string($entry) || $entry instanceof self;
        if ($doSimple) {
            // If the target is a string or vanilla object, just move
            // it the simplest way we can.
            return $this->copyToSimple((string)$entry);
        }

        $entry->setMode(self::MODE_WRITE);
        while (!$this->eof()) {
            $entry->putChunk($this->getChunk());
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getChunk(): string
    {
        return fread($this->handle, self::CHUNK_SIZE);
    }
}
