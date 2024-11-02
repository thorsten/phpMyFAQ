<?php

namespace phpMyFAQ\Auth\EntraId;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Session\AbstractSession;
use Random\RandomException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;

class Session extends AbstractSession
{
    /** @var string EntraID session key */
    final public const ENTRA_ID_SESSION_KEY = 'pmf-entra-id-session-key';

    /** @var string */
    final public const ENTRA_ID_OAUTH_VERIFIER = 'pmf-entra-id-oauth-verifier';

    /** @var string */
    final public const ENTRA_ID_JWT = 'pmf-entra-id-jwt';

    private ?string $currentSessionKey;

    public function __construct(private readonly Configuration $configuration, private readonly SymfonySession $session)
    {
        parent::__construct($configuration, $session);

        $this->createCurrentSessionKey();
    }

    /**
     * Creates the current UUID session key
     */
    public function createCurrentSessionKey(): void
    {
        $this->currentSessionKey = $this->uuid();
    }

    /**
     * Returns the current UUID session key
     */
    public function getCurrentSessionKey(): ?string
    {
        return $this->currentSessionKey ?? $this->session->get(self::ENTRA_ID_SESSION_KEY);
    }

    /**
     * Sets the current UUID session key
     *
     * @throws Exception
     */
    public function setCurrentSessionKey(): Session
    {
        if (!isset($this->currentSessionKey)) {
            $this->createCurrentSessionKey();
        }

        $this->session->set(self::ENTRA_ID_SESSION_KEY, $this->currentSessionKey);

        return $this;
    }

    /**
     * Store the Session ID into a persistent cookie expiring
     * 3600 seconds after the page request.
     *
     * @param string          $name Cookie name
     * @param int|string|null $sessionId Session ID
     * @param int             $timeout Cookie timeout
     */
    public function setCookie(string $name, int|string|null $sessionId, int $timeout = 3600, bool $strict = true): void
    {
        $request = Request::createFromGlobals();

        Cookie::create($name)
            ->withValue($sessionId ?? '')
            ->withExpires($request->server->get('REQUEST_TIME') + $timeout)
            ->withPath(dirname($request->server->get('SCRIPT_NAME')))
            ->withDomain(parse_url($this->configuration->getDefaultUrl(), PHP_URL_HOST))
            ->withSameSite($strict ? 'strict' : '')
            ->withSecure($request->isSecure())
            ->withHttpOnly();
    }

    /**
     * Returns the value of a cookie.
     *
     * @param string $name Cookie name
     */
    public function getCookie(string $name): string
    {
        $request = Request::createFromGlobals();
        return $request->cookies->get($name, '');
    }

    /**
     * Returns a UUID Version 4 compatible universally unique identifier.
     */
    public function uuid(): string
    {
        try {
            return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0x0fff) | 0x4000,
                random_int(0, 0x3fff) | 0x8000,
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff)
            );
        } catch (RandomException $e) {
            $this->configuration->getLogger()->error('Cannot generate UUID: ' . $e->getMessage());
            return '';
        }
    }
}
