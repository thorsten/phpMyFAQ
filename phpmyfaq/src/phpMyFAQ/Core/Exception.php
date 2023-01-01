<?php

/**
 * phpMyFAQ main exception class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-12-28
 */

namespace phpMyFAQ\Core;

/**
 * Class Exception
 *
 * @package phpMyFAQ
 */
class Exception extends \Exception implements \Stringable
{
    /**
     * Converts Exception to a string.
     */
    public function __toString(): string
    {
        return sprintf(
            "Exception %s with message %s in %s: %s\nStack trace:\n%s",
            self::class,
            $this->getMessage(),
            $this->getFile(),
            $this->getLine(),
            $this->getTraceAsString()
        );
    }
}
