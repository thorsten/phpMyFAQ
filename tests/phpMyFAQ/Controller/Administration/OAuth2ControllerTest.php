<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Auth\OAuth2\ClientRegistration;
use phpMyFAQ\Auth\OAuth2\Repository\ClientRepository;
use phpMyFAQ\Auth\OAuth2\Repository\ScopeRepository;
use phpMyFAQ\Enums\OAuth2GrantType;
use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\AdminMenuBuilder;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(OAuth2Controller::class)]
#[UsesNamespace('phpMyFAQ')]
final class OAuth2ControllerTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private Session $session;

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
        $this->session = new Session(new MockArraySessionStorage());

        $language = new Language($this->configuration, $this->session);
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    protected function tearDown(): void
    {
        Token::resetInstanceForTests();

        parent::tearDown();
    }

    /**
     * @throws \Exception
     */
    public function testIndexRequiresTheConfigurationPermission(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->index([PermissionType::USER_EDIT]);
    }

    /**
     * @throws \Exception
     */
    public function testIndexListsTheRegisteredClients(): void
    {
        new ClientRepository($this->configuration)->register(
            'pmf_intranet',
            new ClientRegistration(
                'Intranet <portal>',
                'https://intranet.example.org/callback',
                [OAuth2GrantType::AUTHORIZATION_CODE],
                confidential: false,
            ),
            secretHash: null,
            userId: 42,
        );

        $content = (string) $this->index([PermissionType::CONFIGURATION_EDIT])->getContent();

        self::assertStringContainsString('data-client-id="pmf_intranet"', $content);
        self::assertStringContainsString('data-client-grants="authorization_code"', $content);
        self::assertStringContainsString('data-client-confidential="0"', $content);
        self::assertStringContainsString('data-client-redirect-uri="https://intranet.example.org/callback"', $content);
        self::assertStringContainsString('Intranet &lt;portal&gt;', $content);
        self::assertStringNotContainsString('Intranet <portal>', $content);
        self::assertStringContainsString(Translation::get('msgOAuth2ClientPublic'), $content);
    }

    /**
     * @throws \Exception
     */
    public function testIndexSaysSoWhenNoClientIsRegistered(): void
    {
        $content = (string) $this->index([PermissionType::CONFIGURATION_EDIT])->getContent();

        self::assertStringContainsString(Translation::get('msgOAuth2ClientsEmpty'), $content);
        self::assertStringContainsString('id="pmf-oauth2-client-form"', $content);
    }

    /**
     * Every scope the REST API checks is offered; a stored scope it does not know is listed as unused.
     *
     * @throws \Exception
     */
    public function testIndexListsKnownAndStoredScopes(): void
    {
        $scopes = new ScopeRepository($this->configuration);
        $scopes->enable('faq.read', 'Read FAQs');
        $scopes->enable('legacy.scope', 'Created by hand');

        $content = (string) $this->index([PermissionType::CONFIGURATION_EDIT])->getContent();

        self::assertMatchesRegularExpression(
            '/data-scope="faq\\.read">\\s*<td>\\s*<input[^>]*pmf-oauth2-scope-enabled[^>]*checked/',
            $content,
        );
        self::assertDoesNotMatchRegularExpression(
            '/data-scope="faq\\.write">\\s*<td>\\s*<input[^>]*pmf-oauth2-scope-enabled[^>]*checked/',
            $content,
        );
        self::assertStringContainsString('value="Read FAQs"', $content);
        self::assertStringContainsString('data-scope="legacy.scope"', $content);
        self::assertSame(1, substr_count($content, Translation::get('msgOAuth2ScopeUnused')));
    }

    /**
     * @throws \Exception
     */
    public function testIndexWarnsWhileTheAuthorizationServerIsDisabled(): void
    {
        $this->configuration->set('oauth2.enable', 'false');
        $disabled = (string) $this->index([PermissionType::CONFIGURATION_EDIT])->getContent();

        $this->configuration->set('oauth2.enable', 'true');
        $enabled = (string) $this->index([PermissionType::CONFIGURATION_EDIT])->getContent();

        $warning = htmlspecialchars(Translation::get('msgOAuth2Disabled'), ENT_QUOTES);
        self::assertStringContainsString($warning, $disabled);
        self::assertStringNotContainsString($warning, $enabled);
    }

    /**
     * @param list<PermissionType> $heldPermissions
     * @throws \Exception
     */
    private function index(array $heldPermissions): Response
    {
        $controller = new OAuth2Controller();
        $controller->setContainer($this->container($heldPermissions));

        $request = Request::create('https://localhost/admin/oauth2');
        $request->attributes->set('_route', 'admin.oauth2');

        return $controller->index($request);
    }

    /**
     * @param list<PermissionType> $heldPermissions
     */
    private function container(array $heldPermissions, bool $isSuperAdmin = false): ContainerInterface
    {
        $held = array_map(static fn(PermissionType $permission): string => $permission->value, $heldPermissions);

        $permission = $this->createStub(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(static fn(int $userId, mixed $right): bool => in_array($right, $held, strict: true));

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('isSuperAdmin')->willReturn($isSuperAdmin);
        $currentUser->method('getUserId')->willReturn(42);
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['display_name', 'Admin User'],
                ['email',        'admin@example.com'],
            ]);

        $adminLog = $this->createStub(AdminLog::class);
        $adminHelper = $this->createStub(AdminMenuBuilder::class);
        $adminHelper->method('canAccessContent')->willReturn(true);
        $adminHelper->method('addMenuEntry')->willReturn('');
        $adminHelper->method('setUser')->willReturnSelf();

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(fn(string $id): ?object => match ($id) {
                'phpmyfaq.configuration' => $this->configuration,
                'phpmyfaq.user.current_user' => $currentUser,
                'session' => $this->session,
                'phpmyfaq.admin.admin-log' => $adminLog,
                'phpmyfaq.admin.helper' => $adminHelper,
                default => null,
            });

        return $container;
    }
}
