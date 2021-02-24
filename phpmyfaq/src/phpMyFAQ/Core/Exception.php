<?php

/**
 * phpMyFAQ main exception class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-12-28
 */

namespace phpMyFAQ\Core;

/**
 * Class Exception
 *
 * @package phpMyFAQ
 */
class Exception extends \Exception
{
    /**
     * Converts Exception to a string.
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            "Exception %s with message %s in %s: %s\nStack trace:\n%s",
            get_class(),
            $this->getMessage(),
            $this->getFile(),
            $this->getLine(),
            $this->getTraceAsString()
        );
    }
}
