<?php

/**
 * File handler class.
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

namespace phpMyFAQ\Attachment\Filesystem;

/**
 * Class Entry
 *
 * @package phpMyFAQ\Attachment\Filesystem
 */
abstract class AbstractEntry implements \Stringable
{
    /**
     * Path to the entry in the filesystem.
     */
    protected string $path;

    /**
     * This opened handle.
     *
     * @var resource
     */
    public $handle;

    /**
     * Move a file to another location.
     *
     * @param object|string $entry an entry to move to
     */
    public function moveTo(object|string $entry): bool
    {
        return $this->copyTo($entry) && $this->delete();
    }

    /**
     * Copy file to another location.
     *
     * @param object|string $entry an entry to copy to
     */
    abstract public function copyTo(object|string $entry): bool;

    /**
     * Delete this file.
     */
    abstract public function delete(): bool;

    /**
     * Either file is encrypted.
     */
    public function isEncrypted(): bool
    {
        return false;
    }

    /**
     * Return the current file path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Magic to use in string context.
     */
    public function __toString(): string
    {
        return $this->path;
    }
}
