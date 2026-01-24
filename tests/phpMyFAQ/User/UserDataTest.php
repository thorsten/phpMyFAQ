<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserDataTest extends TestCase
{
    private Sqlite3 $database;
    private UserData $userData;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $this->database = $this->createStub(Sqlite3::class);
        $configuration->method('getDb')->willReturn($this->database);
        $this->userData = new UserData($configuration);
    }

    public function testGet(): void
    {
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(1);
        $this->database->method('fetchArray')->willReturn(['field' => 'value']);

        $this->userData->load(1);
        $result = $this->userData->get('field');
        $this->assertEquals('value', $result);
    }

    public function testFetch(): void
    {
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(1);
        $this->database->method('fetchObject')->willReturn((object) ['key' => 'value']);

        $result = $this->userData->fetch('key', 'value');
        $this->assertEquals('value', $result);
    }

    public function testFetchAll(): void
    {
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(1);
        $this->database->method('fetchArray')->willReturn(['user_id' => 1]);

        $result = $this->userData->fetchAll('key', 'value');
        $this->assertEquals(['user_id' => 1], $result);
    }

    public function testLoad(): void
    {
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(1);
        $this->database->method('fetchArray')->willReturn(['field' => 'value']);

        $result = $this->userData->load(1);
        $this->assertTrue($result);
    }

    public function testSave(): void
    {
        $this->database->method('query')->willReturn(true);
        $this->userData->load(1);
        $this->userData->set(['display_name', 'is_visible', 'twofactor_enabled', 'secret'], [
            'value',
            'value',
            'value',
            'value',
        ]);

        $result = $this->userData->save();
        $this->assertTrue($result);
    }

    public function testAdd(): void
    {
        $this->database->method('query')->willReturn(true);

        $result = $this->userData->add(1);
        $this->assertTrue($result);
    }

    public function testDelete(): void
    {
        $this->database->method('query')->willReturn(true);

        $result = $this->userData->delete(1);
        $this->assertTrue($result);
    }

    public function testEmailExistsReturnsTrueWhenEmailExists(): void
    {
        $this->database->method('escape')->willReturn('test@example.com');
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(1);

        $result = $this->userData->emailExists('test@example.com');
        $this->assertTrue($result);
    }

    public function testEmailExistsReturnsFalseWhenEmailDoesNotExist(): void
    {
        $this->database->method('escape')->willReturn('nonexistent@example.com');
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(0);

        $result = $this->userData->emailExists('nonexistent@example.com');
        $this->assertFalse($result);
    }

    public function testEmailExistsReturnsFalseForEmptyEmail(): void
    {
        $result = $this->userData->emailExists('');
        $this->assertFalse($result);
    }

    public function testGetDecodesHtmlEntitiesInDisplayName(): void
    {
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(1);
        $this->database->method('fetchArray')->willReturn(['display_name' => 'J&uuml;rgen']);

        $this->userData->load(1);
        $result = $this->userData->get('display_name');
        $this->assertEquals('Jürgen', $result);
    }

    public function testGetPreservesPlainUtf8InDisplayName(): void
    {
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(1);
        $this->database->method('fetchArray')->willReturn(['display_name' => 'Jürgen']);

        $this->userData->load(1);
        $result = $this->userData->get('display_name');
        $this->assertEquals('Jürgen', $result);
    }
}
