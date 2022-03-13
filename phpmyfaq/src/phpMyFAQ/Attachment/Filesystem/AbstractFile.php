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
 * @copyright 2009-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */

namespace phpMyFAQ\Attachment\Filesystem;

use phpMyFAQ\Attachment\Filesystem\AbstractEntry;
use phpMyFAQ\Attachment\Filesystem\File\FileException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class File.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */
abstract class AbstractFile extends AbstractEntry
{
    /**
     * Enums.
     */
    public const MODE_READ = 'rb';
    public const MODE_APPEND = 'ab';
    public const MODE_WRITE = 'wb';

    /**
     * Filemode.
     *
     * @var string
     */
    protected $mode;

    /**
     * Constructor.
     *
     * @param string $filepath path to file
     * @param string $mode     mode for fopen
     *
     * @throws FileException
     */
    public function __construct($filepath, $mode = self::MODE_READ)
    {
        $this->path = $filepath;
        $this->mode = $mode;

        $this->handle = @fopen($this->path, $this->mode);

        if (!is_resource($this->handle)) {
            throw new FileException('Could not open file: ' . $this->path);
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    /**
     * Either EOF was reached.
     *
     * @return bool
     */
    public function eof(): bool
    {
        return feof($this->handle);
    }

    /**
     * Get next file chunk.
     *
     * @return string
     */
    abstract public function getChunk();

    /**
     * Put chunk into file.
     *
     * @param string $chunk chunk to write
     *
     * @return int bytes written or false
     */
    abstract public function putChunk($chunk);

    /**
     * Deletes the file.
     *
     * @see inc/PMF_Attachment/Filesystem/PMF_Attachment_Filesystem_Entry#delete()
     *
     * @return bool
     * @throws FileException
     */
    public function delete(): bool
    {
        $deleted = true;

        if ($this->handle) {
            fclose($this->handle);
        }

        if (isset($_FILES['userfile']) && $this->path !== $_FILES['userfile']['tmp_name'] && file_exists($this->path)) {
            $deleted = $this->deleteDir(dirname($this->path));
        }

        return $deleted;
    }

    /**
     * Return current file mode.
     *
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Reopen file in given mode.
     *
     * @param string $mode file mode
     *
     * @return bool
     */
    public function setMode($mode): bool
    {
        $retval = false;

        if (in_array($mode, array(self::MODE_WRITE, self::MODE_READ, self::MODE_APPEND))) {
            fclose($this->handle);
            $this->handle = fopen($this->path, $mode);

            $retval = is_resource($this->handle);
        }

        return $retval;
    }

    /**
     * Simple copy file.
     *
     * @param string $target filepath
     *
     * @return bool
     */
    public function copyToSimple($target): bool
    {
        if (is_uploaded_file($this->path)) {
            $success = move_uploaded_file($this->path, $target);
        } else {
            $success = copy($this->path, $target);
        }

        return $success;
    }

    /**
     * Self check.
     *
     * @return bool
     */
    public function isOk()
    {
        return is_resource($this->handle);
    }

    /**
     * Recursive deletion of path and file.
     *
     * @param string $path
     *
     * @return bool
     * @throws FileException
     */
    public function deleteDir($path): bool
    {
        if (!file_exists($path)) {
            throw new FileException("Directory $path doesn't exist.");
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $file) {
            if (in_array($file->getBasename(), array('.', '..'))) {
                continue;
            } elseif ($file->isDir()) {
                rmdir($file->getPathname());
            } elseif ($file->isFile() || $file->isLink()) {
                if (!is_writable($file->getPathname())) {
                    throw new FileException("File can't be deleted.");
                }
                unlink($file->getPathname());
            }
        }

        return rmdir($path);
    }
}
