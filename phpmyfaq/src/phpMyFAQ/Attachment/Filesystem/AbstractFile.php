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
 * @copyright 2009-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */

declare(strict_types=1);

namespace phpMyFAQ\Attachment\Filesystem;

use phpMyFAQ\Attachment\Filesystem\File\FileException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class File.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */
abstract class AbstractFile extends AbstractEntry
{
    public const string MODE_READ = 'rb';

    public const string MODE_APPEND = 'ab';

    public const string MODE_WRITE = 'wb';

    /**
     * Constructor.
     *
     * @param string $filepath path to file
     * @param string $mode     mode for fopen
     * @throws FileException
     */
    public function __construct(
        string $filepath,
        protected string $mode = self::MODE_READ,
    ) {
        $this->path = $filepath;

        $this->handle = fopen($this->path, $this->mode);

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
     */
    public function eof(): bool
    {
        return feof($this->handle);
    }

    /**
     * Get the next file chunk.
     */
    abstract public function getChunk(): string;

    /**
     * Put chunk into file.
     *
     * @param string $chunk chunk to write
     * @return int|bool bytes written or false
     */
    abstract public function putChunk(string $chunk): int|bool;

    /**
     * Deletes the file.
     *
     * @throws FileException
     */
    public function delete(): bool
    {
        if ($this->handle) {
            fclose($this->handle);
        }

        // Read the uploaded temp path directly from $_FILES instead of
        // rebuilding a Symfony Request. Request::createFromGlobals() eagerly
        // constructs an UploadedFile with path validation, which throws a
        // FileNotFoundException here because move_uploaded_file() has already
        // moved the temp file away during the preceding copyTo() call.
        $uploadedTmpName = $_FILES['userfile']['tmp_name'] ?? null;
        if (is_string($uploadedTmpName) && $this->path !== $uploadedTmpName && file_exists($this->path)) {
            return $this->deleteDir(dirname($this->path));
        }

        return true;
    }

    /**
     * Return current file mode.
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Reopen a file in given mode.
     *
     * @param string $mode file mode
     */
    public function setMode($mode): bool
    {
        $retval = false;

        if (in_array($mode, [self::MODE_WRITE, self::MODE_READ, self::MODE_APPEND], strict: true)) {
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
     */
    public function copyToSimple($target): bool
    {
        if (is_uploaded_file($this->path)) {
            return move_uploaded_file($this->path, $target);
        }

        return copy($this->path, $target);
    }

    /**
     * Self check.
     */
    public function isOk(): bool
    {
        return is_resource($this->handle);
    }

    /**
     * Recursive deletion of path and file.
     *
     * @param string $path
     *
     * @throws FileException
     */
    public function deleteDir($path): bool
    {
        if (!file_exists($path)) {
            throw new FileException(sprintf("Directory %s doesn't exist.", $path));
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($it as $file) {
            if (in_array($file->getBasename(), ['.', '..'], strict: true)) {
                continue;
            }

            if ($file->isDir()) {
                rmdir($file->getPathname());
            }

            if ($file->isFile() || $file->isLink()) {
                if (!is_writable($file->getPathname())) {
                    throw new FileException("File can't be deleted.");
                }

                unlink($file->getPathname());
            }
        }

        return rmdir($path);
    }
}
