<?php

/**
 * Abstract class for phpMyFAQ search classes.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-06-06
 */

namespace phpMyFAQ\Search;

use stdClass;
use phpMyFAQ\Configuration;

/**
 * Class AbstractSearch
 *
 * @package phpMyFAQ\Search
 */
abstract class AbstractSearch
{
    /**
     * ResultSet
     *
     * @var stdClass[]
     */
    protected mixed $resultSet;

    /**
     * AbstractSearch constructor.
     */
    public function __construct(protected Configuration $configuration)
    {
    }
}
