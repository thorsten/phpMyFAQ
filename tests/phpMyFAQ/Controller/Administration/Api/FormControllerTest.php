<?php

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Forms;
use phpMyFAQ\Language;
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

#[AllowMockObjectsWithoutExpectations]
class FormControllerTest extends TestCase
{
    private Configuration $configuration;

    private Forms $forms;

    protected function setUp(): void
    {
        $instance = new ReflectionProperty(Token::class, 'instance');
        $instance->setValue(null, null);
        $_COOKIE = [];

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->forms = new Forms($this->configuration);
    }

    protected function tearDown(): void
    {
        $instance = new ReflectionProperty(Token::class, 'instance');
        $instance->setValue(null, null);
        $_COOKIE = [];
    }

    private function buildController(Session $session): FormController
    {
        $controller = (new ReflectionClass(FormController::class))->newInstanceWithoutConstructor();

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($session) {
                return $id === 'session' ? $session : null;
            });

        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturn(true);

        $actingUser = $this->createMock(CurrentUser::class);
        $actingUser->perm = $perm;
        $actingUser->method('isLoggedIn')->willReturn(true);
        $actingUser->method('getUserId')->willReturn(1);

        $parent = (new ReflectionClass(FormController::class))->getParentClass();
        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('configuration')->setValue($controller, $this->configuration);
        $parent->getProperty('currentUser')->setValue($controller, $actingUser);

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

    private function jsonRequest(array $payload): Request
    {
        return new Request([], [], [], [], [], [], json_encode($payload));
    }

    private function inputActive(int $formId, int $inputId): int
    {
        foreach ($this->forms->getFormData($formId) as $input) {
            if ((int) $input->input_id === $inputId) {
                return (int) $input->input_active;
            }
        }

        return -1;
    }

    private function inputRequired(int $formId, int $inputId): int
    {
        foreach ($this->forms->getFormData($formId) as $input) {
            if ((int) $input->input_id === $inputId) {
                return (int) $input->input_required;
            }
        }

        return -1;
    }

    /**
     * Regression test for #4396: a JSON boolean "false" must deactivate the input.
     * Previously FILTER_VALIDATE_INT turned false into null, so deactivating failed.
     */
    public function testActivateInputPersistsDeactivation(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $controller = $this->buildController($session);
        $csrf = $this->primeCsrf($session, 'activate-input');

        // Start from an activated input.
        $this->forms->saveActivateInputStatus(1, 1, 1);
        self::assertSame(1, $this->inputActive(1, 1));

        $response = $controller->activateInput($this->jsonRequest([
            'csrf' => $csrf,
            'formid' => 1,
            'inputid' => 1,
            'checked' => false,
        ]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(0, $this->inputActive(1, 1));
    }

    public function testActivateInputPersistsActivation(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $controller = $this->buildController($session);
        $csrf = $this->primeCsrf($session, 'activate-input');

        $this->forms->saveActivateInputStatus(1, 1, 0);
        self::assertSame(0, $this->inputActive(1, 1));

        $response = $controller->activateInput($this->jsonRequest([
            'csrf' => $csrf,
            'formid' => 1,
            'inputid' => 1,
            'checked' => true,
        ]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(1, $this->inputActive(1, 1));
    }

    /**
     * Regression test for #4396: a JSON boolean "false" must clear the required flag.
     */
    public function testSetInputAsRequiredPersistsNotRequired(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $controller = $this->buildController($session);
        $csrf = $this->primeCsrf($session, 'require-input');

        $this->forms->saveRequiredInputStatus(1, 1, 1);
        self::assertSame(1, $this->inputRequired(1, 1));

        $response = $controller->setInputAsRequired($this->jsonRequest([
            'csrf' => $csrf,
            'formid' => 1,
            'inputid' => 1,
            'checked' => false,
        ]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(0, $this->inputRequired(1, 1));
    }
}
