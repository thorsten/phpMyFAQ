<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration as JwtConfiguration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Language;
use phpMyFAQ\Notification;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\Question\QuestionHistoryRepository;
use phpMyFAQ\Strings;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Write endpoints accept a personal API key or OAuth2 access token instead of the
 * installation-wide client token. Exercised through POST /v4.0/question against a private copy of
 * the SQLite test database; the OAuth2 tokens are signed with the test key pair and validated by
 * the real league resource server.
 */
#[CoversClass(AbstractApiController::class)]
#[UsesNamespace('phpMyFAQ')]
final class BearerCredentialAuthenticationTest extends TestCase
{
    use TestDatabaseTrait;

    private const int ADMIN_ID = 1;

    private const string API_KEY = 'pmf_0123456789abcdef0123456789abcdef01234567';

    private const string OAUTH_CLIENT = 'bearer-test-client';

    private Configuration $configuration;

    /** @var list<string> */
    private array $temporaryFiles = [];

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->configuration = $this->createTestConfiguration();

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->configuration->set('api.enableAccess', 'true');
        $this->configuration->set('api.apiClientToken', 'installation-token');
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_X_PMF_TOKEN']);

        foreach ($this->temporaryFiles as $file) {
            // nosemgrep: php.lang.security.unlink-use.unlink-use - temp file created by tempnam() below
            @unlink($file);
        }

        parent::tearDown();
    }

    public function testAnApiKeyWithTheScopeActsAsItsOwner(): void
    {
        $this->storeApiKey(self::ADMIN_ID, ['question.write']);
        $controller = $this->createController();

        $response = $controller->create($this->questionRequest('Bearer ' . self::API_KEY));

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame(self::ADMIN_ID, $this->currentUserOf($controller)->getUserId());
        self::assertTrue($this->currentUserOf($controller)->isLoggedIn());
    }

    public function testAnApiKeyDoesNotLogItsOwnerIntoTheSession(): void
    {
        $this->storeApiKey(self::ADMIN_ID, ['question.write']);
        $controller = $this->createController();

        $controller->create($this->questionRequest('Bearer ' . self::API_KEY));

        $storedSessionId = $this->configuration
            ->getDb()
            ->fetchObject($this->configuration->getDb()->query('SELECT session_id FROM faquser WHERE user_id = 1'));
        self::assertContains($storedSessionId->session_id ?? null, [null, '']);
    }

    public function testAnApiKeyRecordsItsUse(): void
    {
        $this->storeApiKey(self::ADMIN_ID, ['question.write']);

        $this->createController()->create($this->questionRequest('Bearer ' . self::API_KEY));

        $key = $this->configuration
            ->getDb()
            ->fetchObject($this->configuration->getDb()->query('SELECT last_used_at FROM faqapi_keys WHERE id = 1'));
        self::assertNotEmpty($key->last_used_at);
    }

    public function testAnExpiredApiKeyIsRejected(): void
    {
        $this->storeApiKey(self::ADMIN_ID, ['question.write'], expiresAt: '2020-01-01 00:00:00');

        $this->expectBearerRejection();

        $this->createController()->create($this->questionRequest('Bearer ' . self::API_KEY));
    }

    public function testAnApiKeyThatExpiresLaterIsAccepted(): void
    {
        $this->storeApiKey(self::ADMIN_ID, ['question.write'], expiresAt: date('Y-m-d H:i:s', time() + 3600));

        $response = $this->createController()->create($this->questionRequest('Bearer ' . self::API_KEY));

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testARevokedApiKeyIsRejected(): void
    {
        $this->storeApiKey(self::ADMIN_ID, ['question.write']);
        $this->configuration->getDb()->query('DELETE FROM faqapi_keys WHERE id = 1');

        $this->expectBearerRejection();

        $this->createController()->create($this->questionRequest('Bearer ' . self::API_KEY));
    }

    public function testAnApiKeyWithoutTheScopeOfTheEndpointIsRejected(): void
    {
        $this->storeApiKey(self::ADMIN_ID, ['faq.write', 'category.write', 'faq.read']);

        $this->expectBearerRejection();

        $this->createController()->create($this->questionRequest('Bearer ' . self::API_KEY));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedHeaderProvider(): iterable
    {
        yield 'unknown key' => ['Bearer pmf_ffffffffffffffffffffffffffffffffffffffff'];
        yield 'key with trailing data' => ['Bearer ' . self::API_KEY . ' extra'];
        yield 'key without prefix' => ['Bearer ' . substr(self::API_KEY, 4)];
        yield 'hash of the key' => [
            'Bearer ' . 'pmf_' . 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
        ];
        yield 'injection attempt' => ["Bearer pmf_x' OR '1'='1"];
        yield 'not a token at all' => ['Bearer not.a.jwt'];
    }

    #[DataProvider('malformedHeaderProvider')]
    public function testAMalformedBearerHeaderIsRejected(string $header): void
    {
        $this->storeApiKey(self::ADMIN_ID, ['question.write']);

        $this->expectBearerRejection();

        $this->createController()->create($this->questionRequest($header));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function foreignSchemeProvider(): iterable
    {
        yield 'basic authentication' => ['Basic ' . self::API_KEY];
        yield 'key without a scheme' => [self::API_KEY];
        yield 'lower-case scheme' => ['bearer ' . self::API_KEY];
        yield 'scheme without a credential' => ['Bearer '];
    }

    /**
     * Anything but a Bearer credential is not ours to judge: the request needs the client token.
     */
    #[DataProvider('foreignSchemeProvider')]
    public function testAnotherAuthorizationSchemeFallsBackToTheClientToken(string $header): void
    {
        $this->storeApiKey(self::ADMIN_ID, ['question.write']);

        $this->expectClientTokenRejection();

        $this->createController()->create($this->questionRequest($header));
    }

    public function testAnApiKeyOfABlockedOwnerIsRejected(): void
    {
        $ownerId = $this->createAccount('blocked-owner');
        $owner = new User($this->configuration);
        $owner->getUserById($ownerId, allowBlockedUsers: true);
        $owner->setStatus('blocked');
        $this->storeApiKey($ownerId, ['question.write']);

        $this->expectBearerRejection();

        $this->createController()->create($this->questionRequest('Bearer ' . self::API_KEY));
    }

    public function testAnApiKeyOfADeletedOwnerIsRejected(): void
    {
        $this->storeApiKey(4711, ['question.write']);

        $this->expectBearerRejection();

        $this->createController()->create($this->questionRequest('Bearer ' . self::API_KEY));
    }

    /**
     * The scope only says what the key may be used for. What its owner may do is still decided
     * by the owner's permissions at the time of the request.
     */
    public function testAnApiKeyDoesNotGrantWhatItsOwnerMayNotDo(): void
    {
        $this->storeApiKey($this->createAccount('no-rights'), ['question.write']);

        $this->expectException(ForbiddenException::class);

        $this->createController()->create($this->questionRequest('Bearer ' . self::API_KEY));
    }

    /**
     * The client token on its own never was enough: the endpoint needs a logged-in user.
     */
    public function testARejectedBearerCredentialStillFallsBackToTheClientToken(): void
    {
        $controller = $this->createController();
        $this->logInSessionUser($controller);
        $_SERVER['HTTP_X_PMF_TOKEN'] = 'installation-token';

        $response = $controller->create($this->questionRequest(
            'Bearer pmf_ffffffffffffffffffffffffffffffffffffffff',
            clientToken: 'installation-token',
        ));

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testAWrongClientTokenNextToARejectedBearerCredentialIsRejected(): void
    {
        $controller = $this->createController();
        $this->logInSessionUser($controller);
        $_SERVER['HTTP_X_PMF_TOKEN'] = 'guessed-token';

        $this->expectClientTokenRejection();

        $controller->create($this->questionRequest(
            'Bearer pmf_ffffffffffffffffffffffffffffffffffffffff',
            clientToken: 'guessed-token',
        ));
    }

    public function testTheClientTokenStillWorksWithoutABearerCredential(): void
    {
        $controller = $this->createController();
        $this->logInSessionUser($controller);
        $_SERVER['HTTP_X_PMF_TOKEN'] = 'installation-token';

        $response = $controller->create($this->questionRequest(null, clientToken: 'installation-token'));

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testARequestWithoutAnyCredentialIsRejected(): void
    {
        $this->expectClientTokenRejection();

        $this->createController()->create($this->questionRequest(null));
    }

    public function testAnOAuth2AccessTokenWithTheScopeActsAsItsUser(): void
    {
        $this->enableOAuth2();
        $token = $this->issueAccessToken('token-1', (string) self::ADMIN_ID, ['question.write']);
        $controller = $this->createController();

        $response = $controller->create($this->questionRequest('Bearer ' . $token));

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame(self::ADMIN_ID, $this->currentUserOf($controller)->getUserId());
    }

    public function testAnExpiredOAuth2AccessTokenIsRejected(): void
    {
        $this->enableOAuth2();
        $token = $this->issueAccessToken('token-1', (string) self::ADMIN_ID, ['question.write'], lifetime: -3600);

        $this->expectBearerRejection();

        $this->createController()->create($this->questionRequest('Bearer ' . $token));
    }

    public function testARevokedOAuth2AccessTokenIsRejected(): void
    {
        $this->enableOAuth2();
        $token = $this->issueAccessToken('token-1', (string) self::ADMIN_ID, ['question.write']);
        $this->configuration
            ->getDb()
            ->query("UPDATE faqoauth_access_tokens SET revoked = 1 WHERE identifier = 'token-1'");

        $this->expectBearerRejection();

        $this->createController()->create($this->questionRequest('Bearer ' . $token));
    }

    /**
     * A token the authorization server has no record of was not issued by it.
     */
    public function testAnUnknownOAuth2AccessTokenIsRejected(): void
    {
        $this->enableOAuth2();
        $token = $this->issueAccessToken('token-1', (string) self::ADMIN_ID, ['question.write'], persist: false);

        $this->expectBearerRejection();

        $this->createController()->create($this->questionRequest('Bearer ' . $token));
    }

    public function testAnOAuth2AccessTokenWithoutTheScopeOfTheEndpointIsRejected(): void
    {
        $this->enableOAuth2();
        $token = $this->issueAccessToken('token-1', (string) self::ADMIN_ID, ['faq.write']);

        $this->expectBearerRejection();

        $this->createController()->create($this->questionRequest('Bearer ' . $token));
    }

    public function testAnOAuth2AccessTokenWithATamperedScopeIsRejected(): void
    {
        $this->enableOAuth2();
        $token = $this->issueAccessToken('token-1', (string) self::ADMIN_ID, ['faq.write']);
        [$header, $payload, $signature] = explode('.', $token);
        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        $claims['scopes'] = ['question.write'];
        $forgedPayload = rtrim(strtr(base64_encode((string) json_encode($claims)), '+/', '-_'), '=');

        $this->expectBearerRejection();

        $this->createController()->create($this->questionRequest(
            'Bearer ' . $header . '.' . $forgedPayload . '.' . $signature,
        ));
    }

    /**
     * Client-credentials tokens are issued to a client, not to a user, so there is nobody the
     * request could act as.
     */
    public function testAnOAuth2AccessTokenWithoutAUserIsRejected(): void
    {
        $this->enableOAuth2();
        $token = $this->issueAccessToken('token-1', '', ['question.write']);

        $this->expectBearerRejection();

        $this->createController()->create($this->questionRequest('Bearer ' . $token));
    }

    public function testAnOAuth2AccessTokenIsRejectedWhileOAuth2IsDisabled(): void
    {
        $this->enableOAuth2();
        $token = $this->issueAccessToken('token-1', (string) self::ADMIN_ID, ['question.write']);
        $this->configuration->set('oauth2.enable', 'false');

        $this->expectBearerRejection();

        $this->createController()->create($this->questionRequest('Bearer ' . $token));
    }

    public function testAnOAuth2AccessTokenDoesNotGrantWhatItsUserMayNotDo(): void
    {
        $this->enableOAuth2();
        $token = $this->issueAccessToken('token-1', (string) $this->createAccount('no-rights'), ['question.write']);

        $this->expectException(ForbiddenException::class);

        $this->createController()->create($this->questionRequest('Bearer ' . $token));
    }

    /**
     * AbstractController::hasValidToken() reports its reason in the challenge, not the message.
     */
    private function expectClientTokenRejection(): void
    {
        $this->expectExceptionObject(new UnauthorizedHttpException(challenge: '"x-pmf-token" is not valid.'));
    }

    private function expectBearerRejection(): void
    {
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage(
            'The bearer credential is invalid, expired, revoked or lacks the "question.write" scope.',
        );
    }

    private function createController(): QuestionController
    {
        return new QuestionController(
            $this->createStub(Notification::class),
            $this->createStub(QuestionHistoryRepository::class),
        );
    }

    private function currentUserOf(QuestionController $controller): CurrentUser
    {
        $currentUser = new ReflectionProperty(AbstractController::class, 'currentUser')->getValue($controller);
        self::assertInstanceOf(CurrentUser::class, $currentUser);

        return $currentUser;
    }

    /**
     * The session of a client that logged in through POST /v4.0/login.
     */
    private function logInSessionUser(QuestionController $controller): void
    {
        $permission = $this->createStub(BasicPermission::class);
        $permission->method('hasPermission')->willReturn(true);

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(self::ADMIN_ID);

        new ReflectionProperty(AbstractController::class, 'currentUser')->setValue($controller, $currentUser);
    }

    private function questionRequest(?string $authorization, ?string $clientToken = null): Request
    {
        $server = [];
        if ($authorization !== null) {
            $server['HTTP_AUTHORIZATION'] = $authorization;
        }

        if ($clientToken !== null) {
            $server['HTTP_X_PMF_TOKEN'] = $clientToken;
        }

        return Request::create(
            'https://faq.example.org/api/v4.0/question',
            method: 'POST',
            server: $server,
            content: (string) json_encode([
                'category-id' => 1,
                'question' => 'Does a personal credential work?',
                'author' => 'API client',
                'email' => 'client@example.org',
            ]),
        );
    }

    /**
     * @param list<string> $scopes
     */
    private function storeApiKey(int $ownerId, array $scopes, ?string $expiresAt = null): void
    {
        $db = $this->configuration->getDb();
        $db->query(sprintf(
            'INSERT INTO faqapi_keys (id, user_id, api_key, name, scopes, last_used_at, expires_at, created)'
            . " VALUES (1, %d, '%s', 'test key', '%s', NULL, %s, %s)",
            $ownerId,
            hash('sha256', self::API_KEY),
            $db->escape((string) json_encode($scopes)),
            $expiresAt === null ? 'NULL' : "'" . $expiresAt . "'",
            $db->now(),
        ));
    }

    private function createAccount(string $login): int
    {
        $user = new User($this->configuration);
        self::assertTrue($user->createUser($login, 'a password'));
        $user->setStatus('active');

        return $user->getUserId();
    }

    private function enableOAuth2(): void
    {
        $this->configuration->set('oauth2.enable', 'true');
        $this->configuration->set('oauth2.publicKeyPath', $this->privateCopyOf('public.key'));
    }

    /**
     * The league server refuses key files that are readable by others.
     */
    private function privateCopyOf(string $keyFile): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pmf-oauth-bearer-');
        self::assertNotFalse($path);
        copy(PMF_TEST_DIR . '/fixtures/oauth2/' . $keyFile, $path);
        chmod($path, 0o600);
        $this->temporaryFiles[] = $path;

        return $path;
    }

    /**
     * An access token as the league authorization server issues it: a JWT signed with the
     * private key, recorded in faqoauth_access_tokens.
     *
     * @param list<string> $scopes
     */
    private function issueAccessToken(
        string $identifier,
        string $userId,
        array $scopes,
        int $lifetime = 3600,
        bool $persist = true,
    ): string {
        $now = new DateTimeImmutable();
        $expiresAt = $now->modify(sprintf('%+d seconds', $lifetime));

        $jwt = JwtConfiguration::forAsymmetricSigner(
            new Sha256(),
            InMemory::file($this->privateCopyOf('private.key')),
            InMemory::plainText('unused'),
        );

        $builder = $jwt
            ->builder()
            ->permittedFor(self::OAUTH_CLIENT)
            ->identifiedBy($identifier)
            ->issuedAt($now->modify('-2 hours'))
            ->canOnlyBeUsedAfter($now->modify('-2 hours'))
            ->expiresAt($expiresAt)
            ->relatedTo($userId)
            ->withClaim('scopes', $scopes);

        if ($persist) {
            $db = $this->configuration->getDb();
            $db->query(sprintf(
                'INSERT INTO faqoauth_access_tokens (identifier, client_id, user_id, scopes, revoked, expires_at, created)'
                . " VALUES ('%s', '%s', %s, '%s', 0, '%s', %s)",
                $identifier,
                self::OAUTH_CLIENT,
                $userId === '' ? 'NULL' : "'" . $userId . "'",
                $db->escape((string) json_encode($scopes)),
                $expiresAt->format('Y-m-d H:i:s'),
                $db->now(),
            ));
        }

        return $builder->getToken($jwt->signer(), $jwt->signingKey())->toString();
    }
}
