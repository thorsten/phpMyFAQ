<?php

namespace phpMyFAQ\Helper;

use phpMyFAQ\Attachment\AbstractAttachment;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AttachmentHelperTest extends TestCase
{
    private AttachmentHelper $attachmentHelper;

    protected function setUp(): void
    {
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->attachmentHelper = new AttachmentHelper();
    }

    public function testGetAttachmentListEmpty()
    {
        $attachmentList = [];
        $result = $this->attachmentHelper->getAttachmentList($attachmentList);
        $this->assertEquals([], $result);
    }

    public function testGetAttachmentListWithAttachments()
    {
        $attachmentMock = $this->createStub(AbstractAttachment::class);
        $attachmentMock->method('getMimeType')->willReturn('application/pdf');
        $attachmentMock->method('buildUrl')->willReturn('https://example.com/file.pdf');
        $attachmentMock->method('getFilename')->willReturn('file.pdf');

        $attachmentList = [$attachmentMock];

        $result = $this->attachmentHelper->getAttachmentList($attachmentList);

        $expectedResult = [
            [
                'icon' => 'file-pdf-o',
                'url' => 'https://example.com/file.pdf',
                'filename' => 'file.pdf',
            ],
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetAttachmentListMapsAllIconGroups(): void
    {
        $fixtures = [
            ['application/zip',               'file-archive-o'],
            ['audio/mpeg',                    'file-audio-o'],
            ['text/xml',                      'file-code-o'],
            ['application/vnd.ms-excel',      'file-excel-o'],
            ['image/png',                     'file-image-o'],
            ['application/vnd.ms-powerpoint', 'file-powerpoint-o'],
            ['application/pdf',               'file-pdf-o'],
            ['text/plain',                    'file-text-o'],
            ['application/msword',            'file-word-o'],
            ['video/mpeg',                    'file-video-o'],
            ['application/x-custom',          'file-o'],
        ];

        $attachments = array_map(function (array $fixture): AbstractAttachment {
            $attachment = $this->createStub(AbstractAttachment::class);
            $attachment->method('getMimeType')->willReturn($fixture[0]);
            $attachment->method('buildUrl')->willReturn('https://example.com/' . rawurlencode($fixture[0]));
            $attachment->method('getFilename')->willReturn($fixture[1] . '.bin');

            return $attachment;
        }, $fixtures);

        $result = $this->attachmentHelper->getAttachmentList($attachments);

        foreach ($fixtures as $index => [$mimeType, $icon]) {
            $this->assertSame($icon, $result[$index]['icon']);
            $this->assertSame($icon . '.bin', $result[$index]['filename']);
            $this->assertStringContainsString(rawurlencode($mimeType), $result[$index]['url']);
        }
    }
}
