<?php

/**
 * Class for filesystem operations.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-02
 */

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;

/**
 * Class Filesystem
 *
 * @package phpMyFAQ
 */
class Filesystem
{
    /**
     * @var string
     */
    private $rootPath;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string[]
     */
    private $folders = [];

    /**
     * Constructor, sets the root path of the master phpMyFAQ installation.
     *
     * @param string $rootPath
     */
    public function __construct($rootPath = '')
    {
        if (empty($rootPath)) {
            $this->rootPath = dirname(dirname(__DIR__));
        } else {
            $this->rootPath = $rootPath;
        }
    }

    /**
     * @return string
     */
    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    /**
     * @return string[]
     */
    public function getFolders(): array
    {
        return $this->folders;
    }

    /**
     * @param string[] $folders
     */
    public function setFolders(array $folders): void
    {
        $this->folders = $folders;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Copies recursively the source to the destination.
     *
     * @param  string $source
     * @param  string $dest
     * @throws Exception
     * @return bool
     */
    public function recursiveCopy(string $source, string $dest): bool
    {
        if (is_dir($source)) {
            $directoryHandle = opendir($source);
            $directoryName = substr($source, strrpos($source, '/') + 1);

            $this->createDirectory($dest . '/' . $directoryName, 0750, true);

            while ($file = readdir($directoryHandle)) {
                if ('.' != $file && '..' != $file) {
                    if (!is_dir($source . '/' . $file)) {
                        $this->copy(
                            $source . '/' . $file,
                            $dest . '/' . $directoryName . '/' . $file
                        );
                    } else {
                        $this->recursiveCopy($source . '/' . $file, $dest . '/' . $directoryName);
                    }
                }
            }

            closedir($directoryHandle);

            return true;
        }

        return false;
    }

    /**
     * Creates directory.
     *
     * @param string $pathname  The directory path
     * @param int    $mode      The mode is 0777 by default
     * @param bool   $recursive Allows the creation of nested directories
     *                          specified in the pathname.
     * @return bool
     */
    public function createDirectory(string $pathname, $mode = 0777, $recursive = false): bool
    {
        if (is_dir($pathname)) {
            return true; // Directory already exists
        }

        return mkdir($pathname, $mode, $recursive);
    }

    /**
     * Moves given directory.
     *
     * @param string $sourcePath
     * @param string $destinationPath
     *
     * @return bool
     */
    public function moveDirectory(string $sourcePath, string $destinationPath): bool
    {
        if (is_dir($destinationPath)) {
            return false;
        }

        return rename($sourcePath, $destinationPath);
    }

    /**
     * Deletes given directory.
     *
     * @param string $pathname
     * @return bool
     */
    public function deleteDirectory(string $pathname): bool
    {
        if (!is_dir($pathname)) {
            return false;
        }

        $directory = opendir($pathname);
        while (false !== ( $file = readdir($directory))) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $full = $pathname . '/' . $file;
                if (is_dir($full)) {
                    $this->deleteDirectory($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($directory);

        return rmdir($pathname);
    }

    /**
     * Copies the source file to the destination.
     *
     * @param string $source
     * @param string $dest
     * @return bool
     * @throws Exception
     */
    public function copy(string $source, string $dest): bool
    {
        if (!is_readable($source)) {
            throw new Exception($source . ' is not readable.');
        }

        if (!is_writable(dirname($dest))) {
            throw new Exception($dest . ' is not writeable.');
        }

        if (!copy($source, $dest)) {
            $error = error_get_last();
            throw new Exception($error['message']);
        }

        return true;
    }
}
