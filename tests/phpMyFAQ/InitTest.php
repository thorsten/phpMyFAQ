<?php

namespace phpMyFAQ;

use PHPUnit\Framework\TestCase;

class InitTest extends TestCase
{
    private array $backupGlobals;

    protected function setUp(): void
    {
        // Backup superglobals
        $this->backupGlobals = [
            '_SERVER' => $_SERVER,
            '_REQUEST' => $_REQUEST,
            '_GET' => $_GET,
            '_POST' => $_POST,
            '_COOKIE' => $_COOKIE,
            '_FILES' => $_FILES,
        ];
    }

    protected function tearDown(): void
    {
        // Restore superglobals
        $_SERVER = $this->backupGlobals['_SERVER'];
        $_REQUEST = $this->backupGlobals['_REQUEST'];
        $_GET = $this->backupGlobals['_GET'];
        $_POST = $this->backupGlobals['_POST'];
        $_COOKIE = $this->backupGlobals['_COOKIE'];
        $_FILES = $this->backupGlobals['_FILES'];
    }

    public function testCleanRequestUserAgent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Test User Agent/1.0';

        Init::cleanRequest();

        $this->assertEquals('Test+User+Agent%2F1.0', $_SERVER['HTTP_USER_AGENT']);
    }

    public function testCleanRequestXSS(): void
    {
        $_GET = [
            'name' => '<script>alert("XSS")</script>',
            'safe' => 'normal text',
        ];

        Init::cleanRequest();

        $expected = [
            'name' => 'alert("XSS")',
            'safe' => 'normal text',
        ];

        $this->assertEquals($expected, $_GET);
    }
}
