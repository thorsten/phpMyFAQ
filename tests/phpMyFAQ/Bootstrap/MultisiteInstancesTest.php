<?php

declare(strict_types=1);

namespace phpMyFAQ\Bootstrap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MultisiteInstances::class)]
final class MultisiteInstancesTest extends TestCase
{
    private string $multisiteDirectory;

    protected function setUp(): void
    {
        $this->multisiteDirectory = sys_get_temp_dir() . '/pmf-multisite-instances-' . bin2hex(random_bytes(6));
        mkdir($this->multisiteDirectory, 0o777, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->multisiteDirectory . '/multisite.php');
        @rmdir($this->multisiteDirectory . '/one.example.org');
        @rmdir($this->multisiteDirectory);
    }

    public function testExistWhenAHostDirectoryLiesNextToTheMultisiteScript(): void
    {
        touch($this->multisiteDirectory . '/multisite.php');
        mkdir($this->multisiteDirectory . '/one.example.org');

        $this->assertTrue(new MultisiteInstances($this->multisiteDirectory)->exist());
    }

    public function testDoNotExistWhenOnlyTheMultisiteScriptIsShipped(): void
    {
        touch($this->multisiteDirectory . '/multisite.php');

        $this->assertFalse(new MultisiteInstances($this->multisiteDirectory)->exist());
    }

    public function testDoNotExistWhenTheMultisiteScriptWasRemoved(): void
    {
        mkdir($this->multisiteDirectory . '/one.example.org');

        $this->assertFalse(new MultisiteInstances($this->multisiteDirectory)->exist());
    }

    public function testDoNotExistWhenTheMultisiteDirectoryIsMissing(): void
    {
        $this->assertFalse(new MultisiteInstances($this->multisiteDirectory . '/missing')->exist());
    }
}
