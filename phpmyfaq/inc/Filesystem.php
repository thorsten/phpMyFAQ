<?php
/**
 * Class for filesystem operations
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Filesystem
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-04-02
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Filesystem
 *
 * @category  phpMyFAQ
 * @package   PMF_Filesystem
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-04-02
 */
class PMF_Filesystem
{
    /**
     * @var string
     */
    private $_path;

    /**
     * @var array
     */
    private $_folders = array();

    /**
     * @param array $folders
     */
    public function setFolders(Array $folders)
    {
        $this->_folders = $folders;
    }

    /**
     * @return array
     */
    public function getFolders()
    {
        return $this->_folders;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->_path = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Copies the source file to the destination
     *
     * @param string $source
     * @param string $dest
     *
     * @return bool
     */
    public function copy($source, $dest)
    {
        if (! is_readable($source)) {
            throw new PMF_Exception('/config/constants.php is not readable.');
        }

        if (! is_writable(dirname($dest))) {
            throw new PMF_Exception(sprintf('%s is not writeable.', $dest));
        }

        if (! copy($source, $dest)) {
            $error = error_get_last();
            throw new PMF_Exception($error['message']);
        }

        return true;
    }
}