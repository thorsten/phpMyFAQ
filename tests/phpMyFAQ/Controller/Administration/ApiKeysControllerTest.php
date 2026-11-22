<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

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
#[CoversClass(ApiKeysController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ApiKeysControllerTest extends TestCase
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
    public function testIndexRequiresTheUserEditPermission(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->index([PermissionType::FAQS_VIEW]);
    }

    /**
     * @throws \Exception
     */
    public function testIndexRendersTheApiKeyManagementWithItsCsrfTokens(): void
    {
        $response = $this->index([PermissionType::USER_EDIT]);
        $content = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('id="pmf-api-keys"', $content);
        self::assertStringContainsString('id="pmf-api-key-form"', $content);
        self::assertStringContainsString(Translation::get('msgApiKeyCopyNow'), $content);

        $token = Token::getInstance($this->session);
        foreach (['create', 'update', 'delete'] as $action) {
            self::assertStringContainsString(
                sprintf('data-csrf-%s="%s"', $action, $token->getTokenString('api-key-' . $action)),
                $content,
            );
        }
    }

    /**
     * A key acts on behalf of its owner, so a scope whose permission the owner lacks is not offered.
     *
     * @throws \Exception
     */
    public function testIndexOffersOnlyTheScopesTheUserMayGrant(): void
    {
        $content = (string) $this->index([PermissionType::USER_EDIT, PermissionType::FAQS_VIEW])->getContent();

        self::assertStringContainsString('value="faq.read"', $content);
        self::assertStringContainsString('value="user.read"', $content);
        self::assertStringNotContainsString('value="faq.write"', $content);
        self::assertStringNotContainsString('value="category.read"', $content);
    }

    /**
     * @throws \Exception
     */
    public function testIndexOffersEveryScopeToASuperAdmin(): void
    {
        $content = (string) $this->index([PermissionType::USER_EDIT], isSuperAdmin: true)->getContent();

        self::assertStringContainsString('value="faq.write"', $content);
        self::assertStringContainsString('value="category.write"', $content);
    }

    /**
     * @param list<PermissionType> $heldPermissions
     * @throws \Exception
     */
    private function index(array $heldPermissions, bool $isSuperAdmin = false): Response
    {
        $controller = new ApiKeysController();
        $controller->setContainer($this->container($heldPermissions, $isSuperAdmin));

        $request = Request::create('https://localhost/admin/user/api-keys');
        $request->attributes->set('_route', 'admin.user.api-keys');

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
