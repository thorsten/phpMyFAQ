<?php

/**
 * Attachment helper class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2020 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-12-30
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Attachment\File;

/**
 * Class AttachmentHelper
 * @package phpMyFAQ\Helper
 */
class AttachmentHelper
{
    /** @var array */
    private $translation;

    /**
     * AttachmentHelper constructor.
     */
    public function __construct()
    {
        global $PMF_LANG;
        $this->translation = $PMF_LANG;
    }

    /**
     * Returns a HTML list of attached files.
     * @param array $attachmentList
     * @return File[]
     */
    public function renderAttachmentList(array $attachmentList): string
    {
        if (count($attachmentList) === 0) {
            return '';
        }

        $html = sprintf('<p>%s:</p><ul>', $this->translation['msgAttachedFiles']);

        foreach ($attachmentList as $attachment) {
            $html .= sprintf(
                '<li><i class="fa fa-%s" aria-hidden="true"></i> <a href="%s">%s</a></li>',
                $this->mapMimeTypeToIcon($attachment->getMimeType()),
                $attachment->buildUrl(),
                $attachment->getFilename()
            );
        }

        return $html . '</ul>';
    }

    /**
     * @param string $mimeType
     * @return string
     */
    private function mapMimeTypeToIcon(string $mimeType): string
    {
        switch ($mimeType) {
            case 'application/zip':
                return 'file-archive-o';
                break;
            case 'audio/basic':
            case 'audio/midi':
            case 'audio/mpeg':
            case 'audio/x-aiff':
            case 'audio/x-mpegurl':
            case 'audio/x-pn-realaudio':
            case 'audio/x-pn-realaudio-plugin':
            case 'audio/x-realaudio':
            case 'audio/x-wav':
                return 'file-audio-o';
                break;
            case 'application/xhtml+xml':
            case 'text/xml':
                return 'file-code-o';
                break;
            case 'application/vnd.ms-excel':
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                return 'file-excel-o';
                break;
            case 'image/bmp':
            case 'image/gif':
            case 'image/ief':
            case 'image/jpeg':
            case 'image/png':
            case 'image/tiff':
            case 'image/vnd.djvu':
            case 'image/vnd.wap.wbmp':
            case 'image/x-cmu-raster':
            case 'image/x-portable-anymap':
            case 'image/x-portable-bitmap':
            case 'image/x-portable-graymap':
            case 'image/x-portable-pixmap':
            case 'image/x-rgb':
            case 'image/x-xbitmap':
            case 'image/x-xpixmap':
            case 'image/x-xwindowdump':
                return 'file-image-o';
                break;
            case 'application/vnd.ms-powerpoint':
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                return 'file-powerpoint-o';
                break;
            case 'application/pdf':
                return 'file-pdf-o';
                break;
            case 'text/plain':
            case 'text/richtext':
            case 'text/rtf':
                return 'file-text-o';
                break;
            case 'application/msword':
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return 'file-word-o';
                break;
            case 'video/mpeg':
            case 'video/quicktime':
            case 'video/vnd.mpegurl':
            case 'video/x-msvideo':
            case 'video/x-sgi-movie':
                return 'file-video-o';
                break;
        }

        return 'file-o';
    }
}
