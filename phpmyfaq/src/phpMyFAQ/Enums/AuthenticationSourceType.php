<?php

/**
 * Authentication source type enum
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-09-23
 */

namespace phpMyFAQ\Enums;

enum AuthenticationSourceType: string
{
    case AUTH_LOCAL = 'local';
    case AUTH_AZURE = 'azure';
    case AUTH_LDAP = 'ldap';
    case AUTH_HTTP = 'http';
    case AUTH_SSO = 'sso';
    case AUTH_WEB_AUTHN = 'webauthn';
}
