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
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();


        $this->attachmentHelper = new AttachmentHelper();
    }

    public function testRenderAttachmentListEmpty()
    {
        $attachmentList = [];
        $result = $this->attachmentHelper->renderAttachmentList($attachmentList);
        $this->assertEquals('', $result);
    }

    public function testRenderAttachmentListWithAttachments()
    {
        $attachmentMock = $this->createMock(AbstractAttachment::class);
        $attachmentMock->method('getMimeType')->willReturn('application/pdf');
        $attachmentMock->method('buildUrl')->willReturn('http://example.com/file.pdf');
        $attachmentMock->method('getFilename')->willReturn('file.pdf');

        $attachmentList = [$attachmentMock];

        $result = $this->attachmentHelper->renderAttachmentList($attachmentList);

        $expectedHtml = '<p>Attached files:</p><ul>';
        $expectedHtml .= '<li><i class="bi bi-file-pdf-o" aria-hidden="true"></i> <a href="http://example.com/file.pdf">file&period;pdf</a></li>';
        $expectedHtml .= '</ul>';

        $this->assertEquals($expectedHtml, $result);
    }
}
