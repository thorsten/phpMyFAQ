<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;

class HttpStreamerTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testSend(): void
    {
        $export = new HttpStreamer('pdf', 'test content');

        $this->expectOutputString('test content');

        $export->send(HttpStreamer::HTTP_CONTENT_DISPOSITION_ATTACHMENT);

        $headers = xdebug_get_headers();

        $this->assertContains('Content-Type: application/pdf', $headers);
        $this->assertContains('Content-Description: phpMyFaq PDF export file', $headers);
        $this->assertContains('Content-Transfer-Encoding: binary', $headers);
        $this->assertContains('Accept-Ranges: none', $headers);
        $this->assertContains('Content-Length: 12', $headers);
    }
}
