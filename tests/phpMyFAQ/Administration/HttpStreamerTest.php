<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderUtils;

class HttpStreamerTest extends TestCase
{
    public function testSend(): void
    {
        $export = new HttpStreamer('pdf', 'test content');

        $this->expectOutputString('test content');

        try {
            $export->send(HeaderUtils::DISPOSITION_ATTACHMENT);
        } catch (Exception $e) {
            $this->assertEquals(
                'Error: unable to send my headers: someone already sent other headers!',
                $e->getMessage()
            );
        }

        $headers = xdebug_get_headers();

        $this->assertContains('Content-Type: application/pdf', $headers);
        $this->assertContains('Content-Description: phpMyFAQ PDF export file', $headers);
        $this->assertContains('Content-Transfer-Encoding: binary', $headers);
        $this->assertContains('Accept-Ranges: none', $headers);
        $this->assertContains('Content-Length: 12', $headers);
    }
}
