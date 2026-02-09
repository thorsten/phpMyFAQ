<?php

/**
 * OAuth2 resource server facade for bearer-token authentication.
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
 * @since     2026-02-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2;

use GuzzleHttp\Psr7\ServerRequest as Psr7ServerRequest;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\ResourceServer as LeagueResourceServer;
use phpMyFAQ\Auth\OAuth2\Repository\AccessTokenRepository;
use phpMyFAQ\Configuration;
use Symfony\Component\HttpFoundation\Request;

final class ResourceServer
{
    /** @var callable(Request): ?int|null */
    private $tokenValidator = null;

    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    /**
     * Registers OAuth2 token validation logic.
     *
     * @param callable(Request): ?int $validator
     */
    public function setTokenValidator(callable $validator): void
    {
        $this->tokenValidator = $validator;
    }

    /**
     * Authenticates bearer tokens and returns the authenticated user ID if available.
     */
    public function authenticate(Request $request): ?int
    {
        $authorizationHeader = trim((string) $request->headers->get('Authorization', ''));
        if (!str_starts_with($authorizationHeader, 'Bearer ')) {
            return null;
        }

        if (is_callable($this->tokenValidator)) {
            return ($this->tokenValidator)($request);
        }

        if (!class_exists(\League\OAuth2\Server\ResourceServer::class)) {
            return null;
        }

        if (!$this->isEnabled()) {
            return null;
        }

        $publicKeyPath = $this->getConfigString('oauth2.publicKeyPath');
        if ($publicKeyPath === '') {
            return null;
        }

        try {
            $psrRequest = $this->toPsr7Request($request);
            $server = new LeagueResourceServer(
                new AccessTokenRepository($this->configuration),
                new CryptKey($publicKeyPath, null, false),
            );
            $validatedRequest = $server->validateAuthenticatedRequest($psrRequest);
            $userId = $validatedRequest->getAttribute('oauth_user_id');

            if ($userId === null || $userId === '') {
                return null;
            }

            return (int) $userId;
        } catch (\Throwable) {
            return null;
        }
    }

    private function isEnabled(): bool
    {
        $value = $this->configuration->get('oauth2.enable');
        return $value === true || $value === 'true' || $value === 1 || $value === '1';
    }

    private function getConfigString(string $key): string
    {
        $value = $this->configuration->get($key);
        return is_string($value) ? trim($value) : '';
    }

    private function toPsr7Request(Request $request): Psr7ServerRequest
    {
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        $psrRequest = new Psr7ServerRequest(
            $request->getMethod(),
            $request->getUri(),
            $headers,
            $request->getContent(),
        );

        $parsedBody = $request->request->all();
        if ($parsedBody !== []) {
            $psrRequest = $psrRequest->withParsedBody($parsedBody);
        }

        return $psrRequest->withQueryParams($request->query->all());
    }
}
