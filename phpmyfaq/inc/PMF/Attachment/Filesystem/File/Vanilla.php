<?php

/**
 * Unencrypted file handler class.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Attachment_Abstract.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */
class PMF_Attachment_Filesystem_File_Vanilla extends PMF_Attachment_Filesystem_File
{
    /**
     * Chunk size read/write operations will deal with
     * (in bytes).
     *
     * @var int
     */
    const chunkSize = 512;

    /**
     * @see PMF_Attachment_Filesystem_File#getChunk()
     */
    public function getChunk()
    {
        return fread($this->handle, self::chunkSize);
    }

    /**
     * @see PMF_Attachment_Filesystem_File#putChunk($chunk)
     */
    public function putChunk($chunk)
    {
        return fwrite($this->handle, $chunk);
    }

    /**
     * @see inc/Attachment/Filesystem/PMF_Attachment_Filesystem_Entry#copyTo($entry)
     */
    public function copyTo($target)
    {
        $retval = false;

        $doSimple = is_string($target) ||
                    $target instanceof self;

        if ($doSimple) {
            /*
         * If the target is a string or vanilla object, just move
         * it the simplest way we can. 
         */
            $retval = $this->copyToSimple((string) $target);
        } else {
            $target->setMode(self::MODE_WRITE);
            while (!$this->eof()) {
                $target->putChunk($this->getChunk());
            }

            $retval = true;
        }

        return $retval;
    }
}
