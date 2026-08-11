<?php

/**
 * Marker interface for controllers that intentionally bypass the automatic
 * "login only" enforcement performed by AbstractController::isSecured().
 *
 * Only controllers that make up the login flow itself (the admin
 * AuthenticationController and the WebAuthn login controllers) should
 * implement this interface — without it, requesting the login page in
 * "login only" mode redirects to itself in an infinite loop. Every other
 * controller must require an authenticated user.
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
