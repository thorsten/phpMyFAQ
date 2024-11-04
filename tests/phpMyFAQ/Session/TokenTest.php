<?php

namespace phpMyFAQ\Session;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TokenTest extends TestCase
{
    private SessionInterface $session;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $_SERVER['SERVER_PORT'] = 443;
        $this->session = $this->createMock(SessionInterface::class);
    }

    public function testGetInstance(): void
    {
        $token1 = Token::getInstance($this->session);
        $token2 = Token::getInstance($this->session);

        $this->assertInstanceOf(Token::class, $token1);
        $this->assertSame($token1, $token2);
    }

    /**
     * @throws \Exception
     */
    public function testGetTokenInput(): void
    {
        $token = Token::getInstance($this->session);
        $page = 'example_page';

        $tokenInput = $token->getTokenInput($page);

        $this->assertStringContainsString(
            '<input type="hidden" id="pmf-csrf-token" name="pmf-csrf-token" value="',
            $tokenInput
        );
    }

    /**
     * @throws \Exception
     */
    public function testVerifyToken(): void
    {
        $token = Token::getInstance($this->session);
        $page = 'example_page';

        $_POST['pmf-csrf-token'] = $token->getTokenString($page);
        $_COOKIE[sprintf('%s-%s', 'pmf-csrf-token', substr(md5($page), 0, 10))] = $token->getTokenString($page);

        $this->assertTrue($token->verifyToken($page, $_POST['pmf-csrf-token']));
    }

    /**
     * @throws \Exception
     */
    public function testRemoveToken(): void
    {
        $token = Token::getInstance($this->session);
        $page = 'example_page';

        // Add a token to session and cookie
        $token->getTokenString($page);

        // Remove the token
        $this->assertTrue($token->removeToken($page));
    }
}
