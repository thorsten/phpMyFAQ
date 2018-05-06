<?php

namespace phpMyFAQ\Attachment\Filesystem\File;

/**
 * Encrypted file handler class.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */

use phpMyFAQ\Attachment\File;
use phpMyFAQ\Attachment\Filesystem\File\Vanilla;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

require PMF_ROOT_DIR.'/src/libs/phpseclib/Crypt/AES.php';

/**
 * PMF_Attachment_Abstract.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */
class Encrypted extends File
{
    /**
     * Chunk delimiter.
     *
     * @var string
     */
    const chunkDelimiter = 'ฒૐᥤ';

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
        $this->aes = new Crypt_AES();
        $this->aes->setKey($key);

        parent::__construct($filepath, $mode);
    }

    /**
     * Chunk here is an encrypted piece of file
     * until delimiter.
     *
     * @see PMF_Attachment_Filesystem_File#getChunk()
     */
    public function getChunk()
    {
        $readEnd = false;
        $chunk = '';
        $chunkDelimLen = strlen(self::chunkDelimiter);

        while (!$readEnd && !$this->eof()) {
            $chunk .= fread($this->handle, 1);
            $readEnd = self::chunkDelimiter == substr($chunk, -$chunkDelimLen);
        }

        $chunk = substr($chunk, 0, -$chunkDelimLen);

        return empty($chunk) ? '' : $this->aes->decrypt($chunk);
    }

    /**
     * Either file is encrypted.
     *
     * @return bool
     */
    public function isEncrypted()
    {
        return true;
    }

    /**
     * @see PMF_Attachment_Filesystem_File#putChunk()
     */
    public function putChunk($chunk)
    {
        $content = $this->aes->encrypt($chunk).self::chunkDelimiter;

        return fwrite($this->handle, $content);
    }

    /**
     * @param string $target
     *
     * @see src/phpMyFAQ/Attachment/Filesystem/Entry#copyTo($entry)
     *
     * @return string
     */
    public function copyTo($target)
    {
        $retval = false;

        if (is_string($target)) {
            $target = new Vanilla($target, self::MODE_WRITE);
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
}
