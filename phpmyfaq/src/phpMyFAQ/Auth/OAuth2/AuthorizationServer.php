<?php

/**
 * OAuth2 authorization server facade.
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

use DateInterval;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\ServerRequest as Psr7ServerRequest;
use League\OAuth2\Server\AuthorizationServer as LeagueAuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use phpMyFAQ\Auth\OAuth2\Entity\UserEntity;
use phpMyFAQ\Auth\OAuth2\Repository\AccessTokenRepository;
use phpMyFAQ\Auth\OAuth2\Repository\AuthCodeRepository;
use phpMyFAQ\Auth\OAuth2\Repository\ClientRepository;
use phpMyFAQ\Auth\OAuth2\Repository\RefreshTokenRepository;
use phpMyFAQ\Auth\OAuth2\Repository\ScopeRepository;
use phpMyFAQ\Configuration;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthorizationServer
{
    /** @var callable(Request): array{body: array<string, mixed>, status: int, headers?: array<string, string>}|null */
    private $tokenIssuer = null;

    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    /**
     * Allows integration code to provide a token issuer implementation.
     *
     * @param callable(Request): array{body: array<string, mixed>, status: int, headers?: array<string, string>} $issuer
     */
    public function setTokenIssuer(callable $issuer): void
    {
        $this->tokenIssuer = $issuer;
    }

    /**
     * Issues an OAuth2 access token response payload.
     *
     * @return array{body: array<string, mixed>, status: int, headers?: array<string, string>}
     */
    public function issueToken(Request $request): array
    {
        if (is_callable($this->tokenIssuer)) {
            return ($this->tokenIssuer)($request);
        }

        if (!class_exists(\League\OAuth2\Server\AuthorizationServer::class)) {
            throw new RuntimeException(
                'OAuth2 server dependency not installed. Please add league/oauth2-server to enable OAuth2 token issuing.',
            );
        }

        if (!$this->isEnabled()) {
            throw new RuntimeException('OAuth2 authorization server is disabled.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $psrRequest = $this->toPsr7Request($request);
            $psrResponse = new Psr7Response();
            $leagueResponse = $this->buildLeagueAuthorizationServer()->respondToAccessTokenRequest(
                $psrRequest,
                $psrResponse,
            );

            $body = json_decode((string) $leagueResponse->getBody(), true);
            if (!is_array($body)) {
                $body = ['error' => 'server_error', 'error_description' => 'Invalid OAuth2 token response body'];
            }

            $headers = [];
            foreach ($leagueResponse->getHeaders() as $headerName => $values) {
                $headers[$headerName] = implode(', ', $values);
            }

            return [
                'body' => $body,
                'status' => $leagueResponse->getStatusCode(),
                'headers' => $headers,
            ];
        } catch (OAuthServerException $exception) {
            return [
                'body' => $exception->getPayload(),
                'status' => $exception->getHttpStatusCode(),
                'headers' => $exception->getHttpHeaders(),
            ];
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'OAuth2 token issuance failed: ' . $exception->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function buildLeagueAuthorizationServer(): LeagueAuthorizationServer
    {
        $privateKeyPath = $this->getConfigString('oauth2.privateKeyPath');
        $encryptionKey = $this->getConfigString('oauth2.encryptionKey');

        if ($privateKeyPath === '' || $encryptionKey === '') {
            throw new RuntimeException(
                'OAuth2 keys are not configured. Set oauth2.privateKeyPath and oauth2.encryptionKey.',
            );
        }

        $server = new LeagueAuthorizationServer(
            new ClientRepository($this->configuration),
            new AccessTokenRepository($this->configuration),
            new ScopeRepository($this->configuration),
            new CryptKey($privateKeyPath, null, false),
            $encryptionKey,
        );

        $accessTokenTtl = $this->parseInterval($this->getConfigString('oauth2.accessTokenTTL'), 'PT1H');
        $refreshTokenTtl = $this->parseInterval($this->getConfigString('oauth2.refreshTokenTTL'), 'P1M');
        $authCodeTtl = $this->parseInterval($this->getConfigString('oauth2.authCodeTTL'), 'PT10M');

        $server->enableGrantType(new ClientCredentialsGrant(), $accessTokenTtl);

        $authCodeGrant = new AuthCodeGrant(
            new AuthCodeRepository($this->configuration),
            new RefreshTokenRepository($this->configuration),
            $authCodeTtl,
        );
        $authCodeGrant->setRefreshTokenTTL($refreshTokenTtl);
        $server->enableGrantType($authCodeGrant, $accessTokenTtl);

        return $server;
    }

    public function isEnabled(): bool
    {
        $value = $this->configuration->get('oauth2.enable');
        return $value === true || $value === 'true' || $value === 1 || $value === '1';
    }

    public function completeAuthorization(Request $request, string $userId, bool $approved): array
    {
        try {
            $psrRequest = $this->toPsr7Request($request);
            $server = $this->buildLeagueAuthorizationServer();
            $authorizationRequest = $server->validateAuthorizationRequest($psrRequest);
            $user = new UserEntity();
            $user->setIdentifier($userId);
            $authorizationRequest->setUser($user);
            $authorizationRequest->setAuthorizationApproved($approved);

            $response = $server->completeAuthorizationRequest($authorizationRequest, new Psr7Response());

            $headers = [];
            foreach ($response->getHeaders() as $headerName => $values) {
                $headers[$headerName] = implode(', ', $values);
            }

            return [
                'body' => (string) $response->getBody(),
                'status' => $response->getStatusCode(),
                'headers' => $headers,
            ];
        } catch (OAuthServerException $exception) {
            return [
                'body' => json_encode($exception->getPayload(), JSON_THROW_ON_ERROR),
                'status' => $exception->getHttpStatusCode(),
                'headers' => $exception->getHttpHeaders(),
            ];
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'OAuth2 authorization failed: ' . $exception->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    private function getConfigString(string $key): string
    {
        $value = $this->configuration->get($key);
        return is_string($value) ? trim($value) : '';
    }

    private function parseInterval(string $value, string $fallback): DateInterval
    {
        try {
            if ($value !== '') {
                return new DateInterval($value);
            }
        } catch (\Throwable) {
            // Fallback to default interval.
        }

        return new DateInterval($fallback);
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
