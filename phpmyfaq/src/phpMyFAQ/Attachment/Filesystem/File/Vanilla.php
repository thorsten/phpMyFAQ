<?php

namespace phpMyFAQ\Attachment\Filesystem\File;

/**
 * Unencrypted file handler class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */

use phpMyFAQ\Attachment\Filesystem\File;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Attachment_Abstract.
 *
 * @category  phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */
class Vanilla extends File
{
    /**
     * Chunk size read/write operations will deal with
     * (in bytes).
     *
     * @var int
     */
    const chunkSize = 512;

    /**
     * @inheritdoc
     */
    public function getChunk(): string
    {
        return fread($this->handle, self::chunkSize);
    }

    /**
     * @inheritdoc
     */
    public function putChunk($chunk): bool
    {
        return fwrite($this->handle, $chunk);
    }

    /**
     * @inheritdoc
     */
    public function copyTo($target): bool
    {
        $doSimple = is_string($target) || $target instanceof self;

        if ($doSimple) {
            // If the target is a string or vanilla object, just move
            // it the simplest way we can.
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
