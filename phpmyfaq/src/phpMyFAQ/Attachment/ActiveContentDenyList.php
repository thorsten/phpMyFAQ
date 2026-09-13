<?php

/**
 * Deny list of executable and active-content file types for attachments
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-09-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Attachment;

/**
 * Attachments may carry arbitrary documents, so there is no allow list. Files
 * that a web server or browser could execute (server-side scripts, shell and
 * batch scripts, executables, HTML/SVG/XML with scripting, Flash) are refused
 * by extension and by detected MIME type.
 */
final class ActiveContentDenyList
{
    private const array EXTENSIONS = [
        'php',
        'phtml',
        'phar',
        'php3',
        'php4',
        'php5',
        'php6',
        'php7',
        'php8',
        'phps',
        'pht',
        'cgi',
        'pl',
        'py',
        'sh',
        'bash',
        'bat',
        'cmd',
        'exe',
        'com',
        'scr',
        'msi',
        'jar',
        'js',
        'mjs',
        'vbs',
        'ps1',
        'html',
        'htm',
        'xhtml',
        'shtml',
        'svg',
        'svgz',
        'xml',
        'swf',
        'htaccess',
    ];

    private const array MIME_TYPES = [
        'application/x-php',
        'application/x-httpd-php',
        'application/x-httpd-php-source',
        'text/x-php',
        'application/x-phar',
        'application/x-perl',
        'text/x-perl',
        'application/x-python',
        'text/x-python',
        'application/x-python-code',
        'application/x-sh',
        'application/x-shellscript',
        'text/x-shellscript',
        'application/x-msdownload',
        'application/x-msdos-program',
        'application/x-dosexec',
        'application/x-msi',
        'application/x-ms-installer',
        'application/java-archive',
        'application/x-java-archive',
        'application/javascript',
        'text/javascript',
        'application/x-javascript',
        'application/ecmascript',
        'text/ecmascript',
        'text/vbscript',
        'text/html',
        'application/xhtml+xml',
        'image/svg+xml',
        'application/xml',
        'text/xml',
        'application/x-shockwave-flash',
        'application/vnd.microsoft.portable-executable',
        'application/x-executable',
        'application/x-sharedlib',
        'application/x-mach-binary',
    ];

    /**
     * Returns the reason (extension or MIME type) the file is refused for, or
     * null when the file may be stored as an attachment.
     */
    public static function deniedReason(string $filename, ?string $mimeType): ?string
    {
        $extension = strtolower(pathinfo(basename($filename), PATHINFO_EXTENSION));
        if ($extension !== '' && in_array($extension, self::EXTENSIONS, strict: true)) {
            return '.' . $extension;
        }

        [$mimeTypeWithoutParameters] = explode(';', (string) $mimeType, limit: 2);
        $normalizedMimeType = strtolower(trim($mimeTypeWithoutParameters));
        if ($normalizedMimeType !== '' && in_array($normalizedMimeType, self::MIME_TYPES, strict: true)) {
            return $normalizedMimeType;
        }

        return null;
    }

    public static function isDenied(string $filename, ?string $mimeType): bool
    {
        return self::deniedReason($filename, $mimeType) !== null;
    }
}
