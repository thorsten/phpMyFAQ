<?php

/**
 * Attachment helper class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-12-30
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Attachment\AttachmentAbstract;
use phpMyFAQ\Translation;

/**
 * Class AttachmentHelper
 * @package phpMyFAQ\Helper
 */
class AttachmentHelper
{
    /**
     * Returns an HTML list of attached files.
     *
     * @param AttachmentAbstract[] $attachmentList
     * @return string
     */
    public function renderAttachmentList(array $attachmentList): string
    {
        if (count($attachmentList) === 0) {
            return '';
        }

        $html = sprintf('<p>%s:</p><ul>', Translation::get('msgAttachedFiles'));

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

    private function mapMimeTypeToIcon(string $mimeType): string
    {
        return match ($mimeType) {
            'application/zip' => 'file-archive-o',
            'audio/basic', 'audio/midi', 'audio/mpeg', 'audio/x-aiff', 'audio/x-mpegurl', 'audio/x-pn-realaudio',
            'audio/x-pn-realaudio-plugin', 'audio/x-realaudio', 'audio/x-wav' => 'file-audio-o',
            'application/xhtml+xml', 'text/xml' => 'file-code-o',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'file-excel-o',
            'image/bmp', 'image/gif', 'image/ief', 'image/jpeg', 'image/png', 'image/tiff', 'image/vnd.djvu',
            'image/vnd.wap.wbmp', 'image/x-cmu-raster', 'image/x-portable-anymap', 'image/x-portable-bitmap',
            'image/x-portable-graymap', 'image/x-portable-pixmap', 'image/x-rgb', 'image/x-xbitmap', 'image/x-xpixmap',
            'image/x-xwindowdump' => 'file-image-o',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'file-powerpoint-o',
            'application/pdf' => 'file-pdf-o',
            'text/plain', 'text/richtext', 'text/rtf' => 'file-text-o',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'file-word-o',
            'video/mpeg', 'video/quicktime', 'video/vnd.mpegurl', 'video/x-msvideo',
            'video/x-sgi-movie' => 'file-video-o',
            default => 'file-o',
        };
    }
}
