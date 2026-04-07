<?php

declare(strict_types=1);

namespace phpMyFAQ\Session;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[CoversClass(Token::class)]
#[UsesNamespace('phpMyFAQ')]
class TokenTest extends TestCase
{
    private Session $session;
    private Token $token;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;

    /**
     * @throws Exception
     * @throws \Exception
     */
    protected function setUp(): void
    {
        Strings::init();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(directory: sys_get_temp_dir(), prefix: 'pmf-token-test-');
        static::assertNotFalse($databasePath);
        static::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        new Configuration($this->dbHandle);

        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');

        Token::resetInstanceForTests();
        $this->session = new Session(new MockArraySessionStorage());
        $this->token = Token::getInstance($this->session);
    }

    protected function tearDown(): void
    {
        Token::resetInstanceForTests();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $this->dbHandle->close();
        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, null);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, '');

        if (file_exists($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = Token::getInstance($this->session);
        $instance2 = Token::getInstance($this->session);

        static::assertSame($instance1, $instance2);
    }

    public function testResetInstanceForTestsClearsInstance(): void
    {
        $instance1 = Token::getInstance($this->session);
        Token::resetInstanceForTests();
        $instance2 = Token::getInstance($this->session);

        static::assertNotSame($instance1, $instance2);
    }

    public function testSetAndGetPage(): void
    {
        $this->token->setPage('testPage');
        static::assertSame('testPage', $this->token->getPage());
    }

    public function testSetPageReturnsSelf(): void
    {
        $result = $this->token->setPage('page');
        static::assertSame($this->token, $result);
    }

    public function testSetAndGetExpiry(): void
    {
        $this->token->setExpiry(3600);
        static::assertSame(3600, $this->token->getExpiry());
    }

    public function testSetExpiryReturnsSelf(): void
    {
        $result = $this->token->setExpiry(100);
        static::assertSame($this->token, $result);
    }

    public function testSetAndGetSessionToken(): void
    {
        $this->token->setSessionToken('abc123');
        static::assertSame('abc123', $this->token->getSessionToken());
    }

    public function testSetSessionTokenReturnsSelf(): void
    {
        $result = $this->token->setSessionToken('x');
        static::assertSame($this->token, $result);
    }

    public function testSetAndGetCookieToken(): void
    {
        $this->token->setCookieToken('cookie123');
        static::assertSame('cookie123', $this->token->getCookieToken());
    }

    public function testGetCookieTokenReturnsNullByDefault(): void
    {
        Token::resetInstanceForTests();
        $freshToken = Token::getInstance($this->session);
        static::assertNull($freshToken->getCookieToken());
    }

    public function testSetCookieTokenReturnsSelf(): void
    {
        $result = $this->token->setCookieToken('y');
        static::assertSame($this->token, $result);
    }

    /**
     * @throws \Exception
     */
    public function testGetTokenInputCreatesSessionAndReturnsHiddenInput(): void
    {
        $html = $this->token->getTokenInput('test-form');

        static::assertStringContainsString('<input type="hidden"', $html);
        static::assertStringContainsString('id="pmf-csrf-token"', $html);
        static::assertStringContainsString('name="pmf-csrf-token"', $html);
        static::assertStringContainsString('value="', $html);
    }

    /**
     * @throws \Exception
     */
    public function testGetTokenInputReusesExistingSession(): void
    {
        $html1 = $this->token->getTokenInput('reuse-page');
        $html2 = $this->token->getTokenInput('reuse-page');

        static::assertSame($html1, $html2);
    }

    /**
     * @throws \Exception
     */
    public function testGetTokenStringCreatesSessionAndReturnsString(): void
    {
        $tokenString = $this->token->getTokenString('test-string-page');

        static::assertIsString($tokenString);
        static::assertNotEmpty($tokenString);
        static::assertSame(32, strlen($tokenString)); // md5 hex length
    }

    /**
     * @throws \Exception
     */
    public function testGetTokenStringReusesExistingSession(): void
    {
        $str1 = $this->token->getTokenString('same-page');
        $str2 = $this->token->getTokenString('same-page');

        static::assertSame($str1, $str2);
    }

    /**
     * @throws \Exception
     */
    public function testGetTokenStringDifferentPagesProduceDifferentTokens(): void
    {
        $str1 = $this->token->getTokenString('page-a');
        $str2 = $this->token->getTokenString('page-b');

        static::assertNotSame($str1, $str2);
    }

    public function testVerifyTokenReturnsFalseForNullRequestToken(): void
    {
        static::assertFalse($this->token->verifyToken('page'));
    }

    public function testVerifyTokenReturnsFalseWhenNoSessionExists(): void
    {
        static::assertFalse($this->token->verifyToken('nonexistent', 'some-token'));
    }

    /**
     * @throws \Exception
     */
    public function testVerifyTokenReturnsFalseForExpiredToken(): void
    {
        $tokenString = $this->token->getTokenString(page: 'expired-page', expiry: -1);

        static::assertFalse($this->token->verifyToken('expired-page', $tokenString));
    }

    /**
     * @throws \Exception
     */
    public function testVerifyTokenReturnsFalseWhenSessionMatchesButCookieMissing(): void
    {
        $tokenString = $this->token->getTokenString('verify-page');

        static::assertFalse($this->token->verifyToken('verify-page', $tokenString));
    }

    /**
     * @throws \Exception
     */
    public function testVerifyTokenReturnsTrueWhenSessionAndCookieMatch(): void
    {
        $page = 'full-verify';
        $tokenString = $this->token->getTokenString($page);

        $cookieName = sprintf(
            '%s-%s',
            Token::PMF_SESSION_NAME,
            substr(string: md5($page), offset: 0, length: 10),
        );
        $_COOKIE[$cookieName] = $tokenString;

        try {
            static::assertTrue($this->token->verifyToken($page, $tokenString));
        } finally {
            unset($_COOKIE[$cookieName]);
        }
    }

    /**
     * @throws \Exception
     */
    public function testVerifyTokenWithRemoveTokenFlag(): void
    {
        $page = 'remove-test';
        $tokenString = $this->token->getTokenString($page);

        $cookieName = sprintf(
            '%s-%s',
            Token::PMF_SESSION_NAME,
            substr(string: md5($page), offset: 0, length: 10),
        );
        $_COOKIE[$cookieName] = $tokenString;

        try {
            static::assertTrue($this->token->verifyToken($page, $tokenString, removeToken: true));

            // After removal, the session token should be gone
            static::assertFalse($this->token->verifyToken($page, $tokenString));
        } finally {
            unset($_COOKIE[$cookieName]);
        }
    }

    /**
     * @throws \Exception
     */
    public function testVerifyTokenReturnsFalseForWrongToken(): void
    {
        $page = 'wrong-token-page';
        $this->token->getTokenString($page);

        $cookieName = sprintf(
            '%s-%s',
            Token::PMF_SESSION_NAME,
            substr(string: md5($page), offset: 0, length: 10),
        );
        $_COOKIE[$cookieName] = 'wrong-cookie';

        try {
            static::assertFalse($this->token->verifyToken($page, 'wrong-request-token'));
        } finally {
            unset($_COOKIE[$cookieName]);
        }
    }

    public function testRemoveTokenReturnsTrue(): void
    {
        static::assertTrue($this->token->removeToken('any-page'));
    }

    /**
     * @throws \Exception
     */
    public function testRemoveTokenClearsSessionData(): void
    {
        $page = 'to-remove';
        $this->token->getTokenString($page);

        $sessionKey = sprintf('%s.%s', Token::PMF_SESSION_NAME, $page);
        static::assertNotNull($this->session->get($sessionKey));

        $this->token->removeToken($page);

        static::assertNull($this->session->get($sessionKey));
    }
}
