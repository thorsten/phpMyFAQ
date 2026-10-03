<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InMemoryHtaccessFile::class)]
final class InMemoryHtaccessFileTest extends TestCase
{
    public function testIsReadableAlwaysReturnsTrue(): void
    {
        $this->assertTrue(new InMemoryHtaccessFile()->isReadable());
    }

    public function testValidBecomesFalseOnceAnEmptyFileHasBeenReadFrom(): void
    {
        $file = new InMemoryHtaccessFile();
        $file->rewind();

        // The stream only learns about its end from a read attempt.
        $this->assertTrue($file->valid());
        $this->assertSame('', $file->fgets());
        $this->assertFalse($file->valid());
    }

    public function testValidFollowsEndOfFileWhileIterating(): void
    {
        $file = new InMemoryHtaccessFile();
        $file->fwrite("RewriteEngine On\nRewriteBase /\n");
        $file->rewind();

        $this->assertTrue($file->valid());

        $lines = [];
        while ($file->valid()) {
            $lines[] = rtrim((string) $file->fgets(), "\n");
        }

        $this->assertFalse($file->valid());
        $this->assertSame(['RewriteEngine On', 'RewriteBase /'], $lines);
    }

    public function testContentWrittenCanBeReadBackCompletely(): void
    {
        $file = new InMemoryHtaccessFile();
        $file->fwrite("# phpMyFAQ\n");
        $file->rewind();

        $content = '';
        while (!$file->eof()) {
            $content .= $file->fgets();
        }

        $this->assertSame("# phpMyFAQ\n", $content);
    }
}
