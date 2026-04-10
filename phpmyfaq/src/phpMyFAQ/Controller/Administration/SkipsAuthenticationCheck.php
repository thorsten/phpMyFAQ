<?php

/**
 * Marker interface for admin controllers that intentionally bypass the
 * automatic authentication enforcement performed by the
 * ControllerContainerListener.
 *
 * Only the AuthenticationController itself (handling login/logout/token
 * endpoints) should implement this interface. Every other controller in
 * the Administration namespace must require an authenticated user.
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
 * @since     2026-04-10
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

interface SkipsAuthenticationCheck {}
