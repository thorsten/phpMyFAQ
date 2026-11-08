<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Template\ThemeManager;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * PMF_LANGUAGE_DIR is a constant, so the only way to point the configuration tab at a missing
 * translations directory is a process of its own in which the constant is defined differently.
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(ConfigurationTabController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ConfigurationTabControllerLanguageDirectoryTest extends TestCase
{
    use TestDatabaseTrait;

    /**
     * @throws \Exception
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testListThrowsABadRequestWhenTheLanguageDirectoryIsMissing(): void
    {
        $missingDirectory = sys_get_temp_dir() . '/pmf-missing-translations-' . bin2hex(random_bytes(6));
        self::assertFalse(defined('PMF_LANGUAGE_DIR'));
        define('PMF_LANGUAGE_DIR', $missingDirectory);

        Strings::init();
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en');

        $configuration = $this->createTestConfiguration();
        $language = new Language($configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $requestLanguage = $this->createStub(Language::class);
        $requestLanguage->method('setLanguageByAcceptLanguage')->willReturn('en');

        $controller = new ConfigurationTabController(
            $requestLanguage,
            $this->createStub(System::class),
            $this->createStub(ThemeManager::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer($configuration));

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('The directory ' . $missingDirectory . ' was not found!');

        $controller->list(new Request([], [], ['mode' => 'main']));
    }

    private function createAuthenticatedContainer(object $configuration): ContainerInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => (
                    $userId === 42
                    && $right === PermissionType::CONFIGURATION_EDIT->value
                ),
            );

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($configuration, $currentUser, $session, $adminLog) {
                return match ($id) {
                    'phpmyfaq.configuration' => $configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.admin.admin-log' => $adminLog,
                    default => null,
                };
            });

        return $container;
    }
}
