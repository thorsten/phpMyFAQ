<?php

/**
 * Class for filesystem operations.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-02
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Filesystem.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-02
 */
class PMF_Filesystem
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
     * @var array
     */
    private $folders = [];

    /**
     * Constructor, sets the root path of the master phpMyFAQ installation.
     *
     * @param string $rootPath
     *
     * @return PMF_Filesystem
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
    public function getRootPath()
    {
        return $this->rootPath;
    }

    /**
     * @param array $folders
     */
    public function setFolders(Array $folders)
    {
        $this->folders = $folders;
    }

    /**
     * @return array
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Copies the source file to the destination.
     *
     * @param string $source
     * @param string $dest
     *
     * @throws PMF_Exception
     *
     * @return bool
     */
    public function copy($source, $dest)
    {
        if (!is_readable($source)) {
            throw new PMF_Exception($source.' is not readable.');
        }

        if (!is_writable(dirname($dest))) {
            throw new PMF_Exception($dest.' is not writeable.');
        }

        if (!copy($source, $dest)) {
            $error = error_get_last();
            throw new PMF_Exception($error['message']);
        }

        return true;
    }

    /**
     * Copies recursively the source to the destination.
     *
     * @param string $source
     * @param string $dest
     *
     * @return bool
     */
    public function recursiveCopy($source, $dest)
    {
        if (is_dir($source)) {
            $directoryHandle = opendir($source);
        }

        $directoryName = substr($source, strrpos($source, '/') + 1);

        $this->mkdir($dest.'/'.$directoryName, 0750, true);

        while ($file = readdir($directoryHandle)) {
            if ('.' != $file && '..' != $file) {
                if (!is_dir($source.'/'.$file)) {
                    $this->copy(
                        $source.'/'.$file,
                        $dest.'/'.$directoryName.'/'.$file
                    );
                } else {
                    $this->recursiveCopy($source.'/'.$file, $dest.'/'.$directoryName);
                }
            }
        }

        closedir($directoryHandle);

        return true;
    }

    /**
     * Makes directory.
     *
     * @param string $pathname  The directory path
     * @param int    $mode      The mode is 0777 by default
     * @param bool   $recursive Allows the creation of nested directories
     *                          specified in the pathname.
     *
     * @return bool
     */
    public function mkdir($pathname, $mode = 0777, $recursive = false)
    {
        if (is_dir($pathname)) {
            return true; // Directory already exists
        }

        return mkdir($pathname, $mode, $recursive);
    }
}
