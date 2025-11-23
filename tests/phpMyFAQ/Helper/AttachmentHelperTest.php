<?php

namespace phpMyFAQ\Helper;

use phpMyFAQ\Attachment\AbstractAttachment;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;

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
        $attachmentMock = $this->createMock(AbstractAttachment::class);
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
}
