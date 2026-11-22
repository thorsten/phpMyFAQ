<?php

/**
 * Grant types the OAuth 2.0 authorization server issues tokens for.
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
 * @since     2026-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Enums;

/**
 * Only the grants AuthorizationServer enables: a client registered for any other grant could
 * never obtain a token.
 */
enum OAuth2GrantType: string
{
    case AUTHORIZATION_CODE = 'authorization_code';

    case CLIENT_CREDENTIALS = 'client_credentials';
}
