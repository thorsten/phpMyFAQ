<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class FileDownloaderTest extends TestCase
{
    public function testSend(): void
    {
        $export = new FileDownloader('pdf', 'test content');

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
                $e->getMessage(),
            );
        }

        $headers = $response->headers->all();

        $this->assertContains('application/pdf', $headers['content-type']);
        $this->assertContains('phpMyFAQ PDF export file', $headers['content-description']);
        $this->assertContains('binary', $headers['content-transfer-encoding']);
        $this->assertContains('none', $headers['accept-ranges']);
        $this->assertContains('12', $headers['content-length']);
    }

    public function testGetResponseReturnsConfiguredResponse(): void
    {
        $export = new FileDownloader('json', '{"faq":true}');

        $response = $export->getResponse(HeaderUtils::DISPOSITION_ATTACHMENT);

        $this->assertSame('{"faq":true}', $response->getContent());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame((string) strlen('{"faq":true}'), $response->headers->get('Content-Length'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function testGetResponseDescribesCsvExports(): void
    {
        $export = new FileDownloader('csv', "id;title\n1;FAQ");

        $response = $export->getResponse(HeaderUtils::DISPOSITION_INLINE);

        $this->assertSame('text/csv', $response->headers->get('Content-Type'));
        $this->assertSame('phpMyFAQ CSV export file', $response->headers->get('Content-Description'));
        $this->assertStringContainsString('phpmyfaq.csv', (string) $response->headers->get('Content-Disposition'));
    }

    public function testGetResponseFallsBackToGenericExportForUnknownTypes(): void
    {
        $export = new FileDownloader('xml', '<faq/>');

        $response = $export->getResponse(HeaderUtils::DISPOSITION_ATTACHMENT);

        $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));
        $this->assertSame('phpMyFAQ Generic export file', $response->headers->get('Content-Description'));
        $this->assertStringContainsString('phpmyfaq.pmf', (string) $response->headers->get('Content-Disposition'));
    }

    public function testSendRefusesWhenOutputWasAlreadyBuffered(): void
    {
        if (headers_sent()) {
            $this->markTestSkipped('Headers were already sent by the test runner.');
        }

        $export = new FileDownloader('pdf', 'test content');

        $this->expectOutputString('already sent');
        echo 'already sent';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error: unable to send my data: someone already sent other data!');

        $export->send(HeaderUtils::DISPOSITION_ATTACHMENT);
    }
}
