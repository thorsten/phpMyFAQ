<?php

namespace phpMyFAQ\Attachment;

use PHPUnit\Framework\TestCase;

class AbstractMimeTypeTest extends TestCase
{
    public function testGuessByExt(): void
    {
        $this->assertEquals('application/andrew-inset', AbstractMimeType::guessByExt('ez'));
        $this->assertEquals('application/mac-binhex40', AbstractMimeType::guessByExt('hqx'));
        $this->assertEquals('application/octet-stream', AbstractMimeType::guessByExt('extension'));
    }
}
