<?php

namespace phpMyFAQ\Attachment;

use PHPUnit\Framework\TestCase;

class AttachmentDirectoryResolverTest extends TestCase
{
    private const string ROOT = '/srv/www/phpmyfaq';

    public function testMasterInstanceKeepsConfiguredRelativePath(): void
    {
        $this->assertSame(self::ROOT . '/content/user/attachments', AttachmentDirectoryResolver::resolve(
            'content/user/attachments',
            self::ROOT,
        ));
    }

    public function testMasterInstanceKeepsConfiguredAbsolutePath(): void
    {
        $this->assertSame('/var/attachments', AttachmentDirectoryResolver::resolve('/var/attachments', self::ROOT));
    }

    public function testClientInstanceGetsOwnNamespaceForRelativePath(): void
    {
        $this->assertSame(self::ROOT
        . '/content/user/attachments/multisite/a.faq.test', AttachmentDirectoryResolver::resolve(
            'content/user/attachments',
            self::ROOT,
            self::ROOT . '/multisite/a.faq.test',
        ));
    }

    public function testClientInstanceGetsOwnNamespaceForAbsolutePath(): void
    {
        $this->assertSame('/var/attachments/multisite/a.faq.test', AttachmentDirectoryResolver::resolve(
            '/var/attachments/',
            self::ROOT,
            self::ROOT . '/multisite/a.faq.test/',
        ));
    }

    public function testTwoClientsNeverShareADirectory(): void
    {
        $a = AttachmentDirectoryResolver::resolve('content/user/attachments', self::ROOT, '/x/multisite/a.faq.test');
        $b = AttachmentDirectoryResolver::resolve('content/user/attachments', self::ROOT, '/x/multisite/b.faq.test');
        $master = AttachmentDirectoryResolver::resolve('content/user/attachments', self::ROOT);

        $this->assertNotSame($a, $b);
        $this->assertNotSame($a, $master);
        $this->assertNotSame($b, $master);
    }

    public function testNamespaceIsLowercasedHostname(): void
    {
        $this->assertSame('a.faq.test', AttachmentDirectoryResolver::instanceNamespace('/x/multisite/A.FAQ.Test'));
    }

    public function testInvalidNamespaceIsIgnored(): void
    {
        $this->assertNull(AttachmentDirectoryResolver::instanceNamespace('/x/multisite/..'));
        $this->assertNull(AttachmentDirectoryResolver::instanceNamespace('/x/multisite/a b'));
        $this->assertNull(AttachmentDirectoryResolver::instanceNamespace(''));
        $this->assertNull(AttachmentDirectoryResolver::instanceNamespace(null));
    }

    public function testTraversalOutsideRootIsRejected(): void
    {
        $this->assertFalse(AttachmentDirectoryResolver::resolve('../../etc', self::ROOT));
        $this->assertFalse(AttachmentDirectoryResolver::resolve('../../etc', self::ROOT, '/x/multisite/a.faq.test'));
    }
}
