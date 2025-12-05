<?php

namespace phpMyFAQ;

use Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\User\UserData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class UserTest extends TestCase
{
    private Configuration|MockObject $configuration;
    private Sqlite3|MockObject $database;
    private User $user;
    private UserData|MockObject $userData;

    protected function setUp(): void
    {
        $this->configuration = $this->createStub(Configuration::class);
        $this->database = $this->createMock(Sqlite3::class);
        $this->userData = $this->createMock(UserData::class);

        $this->configuration->method('getDb')->willReturn($this->database);
        $this->configuration->method('get')->willReturnMap([
            ['security.permLevel', 'basic'],
        ]);

        $this->user = new User($this->configuration);
        $this->user->userdata = $this->userData;
    }

    public function testIsEmailAddressReturnsTrueForValidEmail(): void
    {
        $reflection = new ReflectionClass($this->user);
        $method = $reflection->getMethod('isEmailAddress');

        $result = $method->invoke($this->user, 'test@example.com');
        $this->assertTrue($result);
    }

    public function testIsEmailAddressReturnsFalseForInvalidEmail(): void
    {
        $reflection = new ReflectionClass($this->user);
        $method = $reflection->getMethod('isEmailAddress');

        $result = $method->invoke($this->user, 'invalid-email');
        $this->assertFalse($result);
    }

    public function testCreateUserThrowsExceptionWhenEmailAlreadyExistsAsLogin(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(User::ERROR_USER_EMAIL_NOT_UNIQUE);

        // Mock that login is valid
        $this->database->method('escape')->willReturn('test@example.com');

        // Mock that getUserByLogin returns false (login doesn't exist as login)
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(0);

        // Mock that email exists in userdata
        $this->userData->method('emailExists')->willReturn(true);

        $this->user->createUser('test@example.com');
    }

    public function testCreateUserDoesNotCheckEmailWhenLoginIsNotEmail(): void
    {
        // Mock database operations to simulate login not existing
        $this->database->method('escape')->willReturn('username');
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(0);

        // emailExists should never be called for non-email logins
        $this->userData->expects($this->never())->method('emailExists');

        // This will throw an exception because no auth container is set up,
        // but that's expected - we just want to verify emailExists wasn't called
        try {
            $this->user->createUser('username');
        } catch (Exception $e) {
            // Expected - ignore this exception as we're only testing the email check logic
        }
    }

    public function testCreateUserThrowsExceptionWhenLoginNotUnique(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(User::ERROR_USER_LOGIN_NOT_UNIQUE);

        // Mock that login exists
        $this->database->method('escape')->willReturn('existinguser');
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(1);
        $this->database->method('fetchArray')->willReturn([
            'user_id' => 1,
            'login' => 'existinguser',
            'account_status' => 'active',
            'is_superadmin' => false,
            'auth_source' => 'local'
        ]);

        $this->user->createUser('existinguser');
    }
}
