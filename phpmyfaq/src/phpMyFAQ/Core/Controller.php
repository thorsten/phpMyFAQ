<?php

/**
 * phpMyFAQ abstract controller class.
 *
 * @note This class will be used in a future release.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-11-18
 */

namespace phpMyFAQ\Core;

/**
 * Class Controller
 *
 * @package phpMyFAQ\Core
 */
abstract class Controller
{
    /**
     * Controller constructor.
     */
    public function __construct(protected array $routeParameters)
    {
    }

    /**
     * @throws Exception
     */
    public function __call(string $name, array $arguments)
    {
        $method = $name . 'Action';

        if (method_exists($this, $method)) {
            call_user_func_array([$this, $method], $arguments);
        } else {
            throw new Exception('Method ' . $method . ' not found in controller ' . static::class);
        }
    }
}
