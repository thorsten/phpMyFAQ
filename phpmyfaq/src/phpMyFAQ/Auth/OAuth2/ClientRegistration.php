<?php

/**
 * What an administrator registers an OAuth 2.0 client with.
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

namespace phpMyFAQ\Auth\OAuth2;

use InvalidArgumentException;
use phpMyFAQ\Enums\OAuth2GrantType;

/**
 * A registration that exists is one the authorization server can work with. The message of the
 * exception thrown for an invalid one is a translation key.
 */
final readonly class ClientRegistration
{
    private const int MAXIMUM_NAME_LENGTH = 255;

    /** Schemes a browser would execute or read locally instead of redirecting to the client. */
    private const array FORBIDDEN_REDIRECT_SCHEMES = ['javascript', 'data', 'vbscript', 'file'];

    public string $name;

    public string $redirectUri;

    /** @var list<OAuth2GrantType> */
    public array $grants;

    /**
     * @param list<OAuth2GrantType> $grants
     * @param bool $confidential Whether the client can keep a secret (a server-side application)
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $name,
        string $redirectUri,
        array $grants,
        public bool $confidential,
    ) {
        $name = trim($name);
        $redirectUri = trim($redirectUri);

        if ($name === '' || mb_strlen($name) > self::MAXIMUM_NAME_LENGTH) {
            throw new InvalidArgumentException('msgOAuth2ClientNameInvalid');
        }

        if ($grants === []) {
            throw new InvalidArgumentException('msgOAuth2ClientGrantRequired');
        }

        // Without a secret anybody who knows the client ID could fetch a token
        if (!$confidential && in_array(OAuth2GrantType::CLIENT_CREDENTIALS, $grants, strict: true)) {
            throw new InvalidArgumentException('msgOAuth2ClientCredentialsNeedSecret');
        }

        if (in_array(OAuth2GrantType::AUTHORIZATION_CODE, $grants, strict: true) && $redirectUri === '') {
            throw new InvalidArgumentException('msgOAuth2ClientRedirectUriRequired');
        }

        if ($redirectUri !== '' && !self::isRedirectUri($redirectUri)) {
            throw new InvalidArgumentException('msgOAuth2ClientRedirectUriInvalid');
        }

        $this->name = $name;
        $this->redirectUri = $redirectUri;
        $this->grants = array_values(array_unique($grants, SORT_REGULAR));
    }

    /**
     * @return list<string>
     */
    public function grantValues(): array
    {
        return array_map(static fn(OAuth2GrantType $grant): string => $grant->value, $this->grants);
    }

    /**
     * An absolute URI; native applications register a scheme of their own.
     */
    private static function isRedirectUri(string $redirectUri): bool
    {
        if (filter_var($redirectUri, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($redirectUri, PHP_URL_SCHEME));

        return !in_array($scheme, self::FORBIDDEN_REDIRECT_SCHEMES, strict: true);
    }
}
