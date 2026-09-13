<?php

declare(strict_types=1);

namespace phpMyFAQ\Attachment;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ActiveContentDenyList::class)]
final class ActiveContentDenyListTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string|null, string|null}>
     */
    public static function fileProvider(): iterable
    {
        yield 'php script' => ['shell.php', 'text/plain', '.php'];
        yield 'php with upper case extension' => ['SHELL.PHTML', 'application/octet-stream', '.phtml'];
        yield 'php5' => ['x.php5', null, '.php5'];
        yield 'phar' => ['archive.phar', null, '.phar'];
        yield 'html page' => ['page.html', 'text/html', '.html'];
        yield 'svg image' => ['logo.svg', 'image/svg+xml', '.svg'];
        yield 'xml document' => ['data.xml', 'application/xml', '.xml'];
        yield 'shell script' => ['run.sh', 'text/x-shellscript', '.sh'];
        yield 'windows executable' => ['setup.exe', 'application/x-msdownload', '.exe'];
        yield 'javascript module' => ['app.mjs', 'text/javascript', '.mjs'];
        yield 'powershell' => ['evil.ps1', 'text/plain', '.ps1'];
        yield 'flash' => ['movie.swf', 'application/x-shockwave-flash', '.swf'];
        yield 'htaccess' => ['.htaccess', 'text/plain', '.htaccess'];
        yield 'renamed php detected by mime' => ['notes.txt', 'text/x-php', 'text/x-php'];
        yield 'renamed html detected by mime with charset' => ['notes.txt', 'text/html; charset=utf-8', 'text/html'];
        yield 'renamed executable detected by mime' => ['report.pdf', 'application/x-dosexec', 'application/x-dosexec'];
        yield 'pdf document' => ['report.pdf', 'application/pdf', null];
        yield 'word document' => [
            'letter.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
        ];
        yield 'png image' => ['photo.png', 'image/png', null];
        yield 'zip archive' => ['files.zip', 'application/zip', null];
        yield 'plain text' => ['readme.txt', 'text/plain', null];
        yield 'no extension, unknown mime' => ['README', null, null];
        yield 'double extension keeps final extension' => ['invoice.php.pdf', 'application/pdf', null];
    }

    #[DataProvider('fileProvider')]
    public function testDeniedReason(string $filename, ?string $mimeType, ?string $expectedReason): void
    {
        self::assertSame($expectedReason, ActiveContentDenyList::deniedReason($filename, $mimeType));
        self::assertSame($expectedReason !== null, ActiveContentDenyList::isDenied($filename, $mimeType));
    }
}
