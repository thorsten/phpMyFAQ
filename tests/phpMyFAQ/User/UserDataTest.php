<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\Configuration;

class UserDataTest extends TestCase
{
    private Sqlite3 $database;
    private UserData $userData;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $this->database = $this->createMock(Sqlite3::class);
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
        $this->database->method('fetchObject')->willReturn((object)['key' => 'value']);

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
        $this->userData->set(
            ['display_name', 'is_visible', 'twofactor_enabled', 'secret'],
            ['value', 'value', 'value', 'value']
        );

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
}
