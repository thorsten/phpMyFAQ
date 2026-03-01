<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[CoversClass(ChatController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ChatControllerTest extends TestCase
{
    private Configuration $configuration;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        try {
            $this->configuration = Configuration::getConfigurationInstance();
        } catch (\TypeError) {
            $dbHandle = new Sqlite3();
            $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
            $this->configuration = new Configuration($dbHandle);
        }

        $language = new Language($this->configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    public function testIndexRendersForLoggedInUser(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $controller = new ChatController(new UserSession($this->configuration));
        $currentUser = new CurrentUser($this->configuration);
        $currentUser->getUserById(1, true);
        $currentUser->setLoggedIn(true);

        $property = new \ReflectionProperty($controller, 'currentUser');
        $property->setValue($controller, $currentUser);

        $response = $controller->index(Request::create('/user/chat', 'GET'));

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('id="pmf-chat-conversation-list"', (string) $response->getContent());
        self::assertStringContainsString('window.pmfChatConfig', (string) $response->getContent());
    }

    private function overrideConfigurationValues(array $values): void
    {
        $reflection = new \ReflectionClass(Configuration::class);
        $configProperty = $reflection->getProperty('config');
        $currentConfig = $configProperty->getValue($this->configuration);
        self::assertIsArray($currentConfig);

        $configProperty->setValue($this->configuration, array_merge($currentConfig, $values));
    }
}
