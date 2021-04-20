<?php

/**
 * File handler class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */

namespace phpMyFAQ\Attachment\Filesystem;

/**
 * Class Entry
 *
 * @package phpMyFAQ\Attachment\Filesystem
 */
abstract class AbstractEntry
{
    /**
     * Path to the entry in the filesystem.
     *
     * @var string
     */
    protected $path;

    /**
     * This opened handle.
     *
     * @var resource
     */
    protected $handle;

    /**
     * Move file to another location.
     *
     * @param object|string $entry an entry to move to
     *
     * @return bool
     */
    public function moveTo($entry): bool
    {
        return $this->copyTo($entry) && $this->delete();
    }

    /**
     * Copy file to another location.
     *
     * @param object|string $entry an entry to copy to
     *
     * @return bool
     */
    abstract public function copyTo($entry): bool;

    /**
     * Delete this file.
     *
     * @return bool
     */
    abstract public function delete();

    /**
     * Either file is encrypted.
     *
     * @return bool
     */
    public function isEncrypted(): bool
    {
        return false;
    }

    /**
     * Return current file path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Magic to use in string context.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->path;
    }
}
