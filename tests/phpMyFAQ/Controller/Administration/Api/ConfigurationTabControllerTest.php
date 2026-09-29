<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Regression tests for https://github.com/thorsten/phpMyFAQ/issues/4710:
 * unchecked configuration checkboxes must always be persisted as 'false'.
 */
#[AllowMockObjectsWithoutExpectations]
class ConfigurationTabControllerTest extends TestCase
{
    private const array OLD_CONFIGURATION = [
        'main.referenceURL' => 'https://faq.example.test',
        'main.enableNotifications' => 'true',
        'main.enableWysiwygEditor' => 'true',
        'main.enableMarkdownEditor' => 'false',
        'main.titleFAQ' => 'Old title',
        'records.numberOfRecordsPerPage' => '1',
        'records.defaultAttachmentEncKey' => '',
        'records.enableAttachmentEncryption' => 'false',
        'security.enableRegistration' => 'true',
    ];

    /** @var array<string, string>|null */
    private ?array $updatedConfiguration = null;

    protected function setUp(): void
    {
        $instance = new ReflectionProperty(Token::class, 'instance');
        $instance->setValue(null, null);
        $_COOKIE = [];
        $this->updatedConfiguration = null;

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();
    }

    protected function tearDown(): void
    {
        $instance = new ReflectionProperty(Token::class, 'instance');
        $instance->setValue(null, null);
        $_COOKIE = [];
    }

    /**
     * @param array<string, string> $oldConfiguration
     */
    private function buildController(array $oldConfiguration, Session $session): ConfigurationTabController
    {
        $controller = (new ReflectionClass(ConfigurationTabController::class))->newInstanceWithoutConstructor();

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static fn(string $id) => match ($id) {
                'session' => $session,
                default => null,
            });

        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getAll')->willReturn($oldConfiguration);
        $configuration
            ->method('get')
            ->willReturnCallback(static fn(string $item): mixed => match ($oldConfiguration[$item] ?? null) {
                'true' => true,
                'false' => false,
                default => $oldConfiguration[$item] ?? null,
            });
        $configuration->expects($this->never())->method('replaceMainReferenceUrl');
        $configuration
            ->method('update')
            ->willReturnCallback(function (array $newConfigs): bool {
                $this->updatedConfiguration = $newConfigs;

                return true;
            });

        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturnCallback(
            static fn(int $userId, string $permission): bool => (
                $permission === PermissionType::CONFIGURATION_EDIT->value
            ),
        );

        $user = $this->createMock(CurrentUser::class);
        $user->perm = $perm;
        $user->method('isLoggedIn')->willReturn(true);
        $user->method('getUserId')->willReturn(5);

        $parent = (new ReflectionClass(ConfigurationTabController::class))->getParentClass();
        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('currentUser')->setValue($controller, $user);
        $parent->getProperty('configuration')->setValue($controller, $configuration);

        return $controller;
    }

    private function primeCsrf(Session $session, string $page): string
    {
        $tokenValue = 'unit-test-token-' . bin2hex(random_bytes(8));
        $cookieName = 'pmf-csrf-token-' . substr(md5($page), 0, 10);

        $reflection = new ReflectionClass(Token::class);
        $token = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('session')->setValue($token, $session);
        $token->setPage($page);
        $token->setExpiry(time() + 3600);
        $token->setSessionToken($tokenValue);
        $token->setCookieToken($tokenValue);

        $session->set('pmf-csrf-token.' . $page, $token);
        $_COOKIE[$cookieName] = $tokenValue;

        return $tokenValue;
    }

    /**
     * @param array<string, string> $edit
     * @param array<string, string> $oldConfiguration
     * @return array<string, string>
     */
    private function save(
        array $edit,
        ?array $availableFields = null,
        array $oldConfiguration = self::OLD_CONFIGURATION,
    ): array {
        $session = new Session(new MockArraySessionStorage());
        $controller = $this->buildController($oldConfiguration, $session);

        $post = [
            'pmf-csrf-token' => $this->primeCsrf($session, 'configuration'),
            'edit' => $edit,
        ];
        if ($availableFields !== null) {
            $post['availableFields'] = json_encode($availableFields);
        }

        $response = $controller->save(new Request([], $post));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsArray($this->updatedConfiguration);

        return $this->updatedConfiguration;
    }

    public function testUncheckedCheckboxIsPersistedAsFalseWithoutAvailableFields(): void
    {
        // The form now sends the hidden "false" value of an unchecked checkbox itself,
        // so persistence must not depend on the JavaScript-generated availableFields list.
        $updated = $this->save(['main.enableNotifications' => 'false']);

        $this->assertSame('false', $updated['main.enableNotifications']);
        $this->assertSame('true', $updated['security.enableRegistration'], 'Fields from other tabs are untouched');
    }

    public function testCheckedCheckboxIsPersistedAsTrue(): void
    {
        $updated = $this->save(['main.enableMarkdownEditor' => 'true', 'main.enableNotifications' => 'true']);

        $this->assertSame('true', $updated['main.enableNotifications']);
        $this->assertSame('true', $updated['main.enableMarkdownEditor']);
    }

    public function testLegacyTruthyCheckboxValueIsNormalisedToTrue(): void
    {
        $updated = $this->save(['main.enableNotifications' => '1']);

        $this->assertSame('true', $updated['main.enableNotifications']);
    }

    public function testMissingCheckboxListedInAvailableFieldsIsPersistedAsFalse(): void
    {
        $updated = $this->save(['main.titleFAQ' => 'New title'], ['main.titleFAQ', 'main.enableNotifications']);

        $this->assertSame('false', $updated['main.enableNotifications']);
        $this->assertSame('New title', $updated['main.titleFAQ']);
    }

    public function testMissingCheckboxWithLegacyStoredValueIsPersistedAsFalse(): void
    {
        $old = self::OLD_CONFIGURATION;
        $old['main.enableNotifications'] = '1';

        $updated = $this->save(['main.titleFAQ' => 'New title'], ['main.titleFAQ', 'main.enableNotifications'], $old);

        $this->assertSame('false', $updated['main.enableNotifications']);
    }

    public function testMissingNonCheckboxFieldInAvailableFieldsKeepsStoredValue(): void
    {
        // A non-checkbox field with a "truthy looking" stored value must never be reset.
        $updated = $this->save(['main.titleFAQ' => 'New title'], ['main.titleFAQ', 'records.numberOfRecordsPerPage']);

        $this->assertSame('1', $updated['records.numberOfRecordsPerPage']);
    }

    public function testEnablingMarkdownEditorDisablesWysiwygEditor(): void
    {
        $updated = $this->save(['main.enableMarkdownEditor' => 'true', 'main.enableWysiwygEditor' => 'true']);

        $this->assertSame('true', $updated['main.enableMarkdownEditor']);
        $this->assertSame('false', $updated['main.enableWysiwygEditor']);
    }

    public function testSubmittedFalseMarkdownEditorDoesNotDisableWysiwygEditor(): void
    {
        // Regression: the hidden "false" value must not be mistaken for "Markdown enabled".
        $updated = $this->save(['main.enableMarkdownEditor' => 'false', 'main.enableWysiwygEditor' => 'true']);

        $this->assertSame('false', $updated['main.enableMarkdownEditor']);
        $this->assertSame('true', $updated['main.enableWysiwygEditor']);
    }

    public function testInvalidCsrfTokenIsRejected(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $controller = $this->buildController(self::OLD_CONFIGURATION, $session);
        $this->primeCsrf($session, 'configuration');

        $response = $controller->save(
            new Request([], [
                'pmf-csrf-token' => 'wrong',
                'edit' => ['main.enableNotifications' => 'false'],
            ]),
        );

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertNull($this->updatedConfiguration);
    }
}
