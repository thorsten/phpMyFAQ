<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class HttpStreamerTest extends TestCase
{
    public function testSend(): void
    {
        $export = new HttpStreamer('pdf', 'test content');

        $this->expectOutputString('test content');

        $response = new Response();
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Description', 'phpMyFAQ PDF export file');
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Accept-Ranges', 'none');
        $response->headers->set('Content-Length', '12');

        try {
            $export->send(HeaderUtils::DISPOSITION_ATTACHMENT);
        } catch (Exception $e) {
            $this->assertEquals(
                'Error: unable to send my headers: someone already sent other headers!',
                $e->getMessage()
            );
        }

        $headers = $response->headers->all();

        $this->assertContains('application/pdf', $headers['content-type']);
        $this->assertContains('phpMyFAQ PDF export file', $headers['content-description']);
        $this->assertContains('binary', $headers['content-transfer-encoding']);
        $this->assertContains('none', $headers['accept-ranges']);
        $this->assertContains('12', $headers['content-length']);
    }
}
