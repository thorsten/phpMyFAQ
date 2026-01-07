<?php

/**
 * The abstract Administration API controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-06
 */

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Controller\AbstractController;

class AbstractAdministrationApiController extends AbstractController
{
    protected ?AdminLog $adminLog = null;

    public function __construct()
    {
        parent::__construct();

        $this->adminLog = $this->container->get(id: 'phpmyfaq.admin.admin-log');
    }
}
