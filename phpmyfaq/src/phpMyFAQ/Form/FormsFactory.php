<?php

/**
 * Minimal Factory for Forms instances.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-04
 */

declare(strict_types=1);

namespace phpMyFAQ\Form;

use phpMyFAQ\Configuration;
use phpMyFAQ\Forms;

final class FormsFactory
{
    public static function create(
        Configuration $configuration,
        ?FormsRepositoryInterface $formsRepository = null,
    ): Forms {
        return new Forms($configuration, $formsRepository);
    }
}
