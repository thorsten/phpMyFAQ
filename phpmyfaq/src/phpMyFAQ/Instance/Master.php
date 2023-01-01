<?php

/**
 * The main phpMyFAQ instances class for instance masters.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-31
 */

namespace phpMyFAQ\Instance;

use phpMyFAQ\Instance;

/**
 * Class Master
 *
 * @package phpMyFAQ\Instance
 */
class Master extends Instance
{
    public function createMaster(Instance $instance)
    {
        $this->setId($instance->getId());
        $this->addConfig('isMaster', 'true');
    }
}
