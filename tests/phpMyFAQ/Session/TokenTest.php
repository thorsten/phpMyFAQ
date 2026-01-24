<?php

namespace phpMyFAQ\Session;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[AllowMockObjectsWithoutExpectations]
class TokenTest extends TestCase
{
    private SessionInterface $sessionMock;
    private Token $token;

    /**
     * @throws Exception
     * @throws \Exception
     */
    protected function setUp(): void
    {
        // Mock the SessionInterface
        $this->sessionMock = $this->createStub(SessionInterface::class);
        $this->token = Token::getInstance($this->sessionMock);
    }

    public function testGetInstanceReturnsTokenInstance(): void
    {
        $this->assertInstanceOf(Token::class, $this->token);
    }

    public function testSetAndGetPage(): void
    {
        $this->token->setPage('testPage');
        $this->assertEquals('testPage', $this->token->getPage());
    }

    public function testSetAndGetExpiry(): void
    {
        $this->token->setExpiry(3600);
        $this->assertEquals(3600, $this->token->getExpiry());
    }

    public function testSetAndGetSessionToken(): void
    {
        $this->token->setSessionToken('testSessionToken');
        $this->assertEquals('testSessionToken', $this->token->getSessionToken());
    }

    public function testSetAndGetCookieToken(): void
    {
        $this->token->setCookieToken('testCookieToken');
        $this->assertEquals('testCookieToken', $this->token->getCookieToken());
    }

    public function testGetTokenInput(): void
    {
        $this->sessionMock->method('get')->willReturn($this->token->setSessionToken('testToken'));

        $inputHtml = $this->token->getTokenInput('testPage');
        $expectedHtml = '<input type="hidden" id="pmf-csrf-token" name="pmf-csrf-token"';
        $this->assertStringContainsString($expectedHtml, $inputHtml);
    }

    /**
     * @throws \Exception
     */
    public function testGetTokenString(): void
    {
        $this->sessionMock->method('get')->willReturn($this->token->setSessionToken('testToken'));

        $tokenString = $this->token->getTokenString('testPage');
        $this->assertIsString($tokenString);
    }

    public function testVerifyTokenReturnsFalseForInvalidToken(): void
    {
        $this->token->setSessionToken('testSessionToken');
        $this->sessionMock->method('get')->willReturn($this->token);

        $this->assertFalse($this->token->verifyToken('testPage', 'invalidToken'));
    }

    public function testRemoveToken(): void
    {
        $this->sessionMock->method('remove')->with($this->equalTo('pmf-csrf-token.testPage'));

        $this->assertTrue($this->token->removeToken('testPage'));
    }
}
