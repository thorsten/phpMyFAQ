<?php

/**
 * Simple HTTP Streamer.
 *
 * This class manages the stream of a generic content
 * taking into account the correct http headers settings
 *
 * Currently, it supports only 4 content (mime) types:
 * - PDF: application/pdf
 * - HTML5: text/html
 * - CSV: text/csv
 * - JSON: application/json
 * - Generic file: application/octet-stream
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 *  v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-02
 */

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;

/**
 * Class HttpStreamer
 *
 * @package phpMyFAQ
 */
class HttpStreamer
{
    /**
     * HTTP content disposition attachment constant.
     *
     * @var string
     */
    final public const HTTP_CONTENT_DISPOSITION_ATTACHMENT = 'attachment';

    /**
     * HTTP content disposition inline constant.
     *
     * @var string
     */
    final public const HTTP_CONTENT_DISPOSITION_INLINE = 'inline';

    /**
     * Disposition attachment constant.
     *
     * @var string
     */
    final public const EXPORT_DISPOSITION_ATTACHMENT = 'attachment';

    /**
     * Disposition inline constant.
     *
     * @var string
     */
    final public const EXPORT_DISPOSITION_INLINE = 'inline';

    /**
     * HTTP Content Disposition.
     */
    private string $disposition = self::HTTP_CONTENT_DISPOSITION_INLINE;

    /**
     * HTTP streaming data length.
     */
    private readonly int $size;

    /**
     * Constructor.
     *
     * @param string $type Type
     * @param string $content Content
     */
    public function __construct(private readonly string $type, private readonly string $content)
    {
        $this->size = strlen($this->content);
    }

    /**
     * Sends data.
     *
     * @param string $disposition Disposition
     * @throws Exception
     */
    public function send(string $disposition): void
    {
        $this->disposition = $disposition;

        // Sanity checks
        if (headers_sent()) {
            throw new Exception('Error: unable to send my headers: someone already sent other headers!');
        }
        if (ob_get_contents()) {
            throw new Exception('Error: unable to send my data: someone already sent other data!');
        }

        // Manage output buffering
        ob_start();
        // Send the right HTTP headers
        $this->setHttpHeaders();
        // Send the raw content
        $this->streamContent();
        // Manage output buffer flushing
        ob_end_flush();
    }

    /**
     * Sends HTTP Headers.
     */
    private function setHttpHeaders(): void
    {
        // Evaluate data upon export type request
        switch ($this->type) {
            case 'pdf':
                $filename = 'phpmyfaq.pdf';
                $description = 'phpMyFaq PDF export file';
                $mimeType = 'application/pdf';
                break;
            case 'html5':
                $filename = 'phpmyfaq.html';
                $description = 'phpMyFaq HTML5 export file';
                $mimeType = 'text/html';
                break;
            case 'csv':
                $filename = 'phpmyfaq.csv';
                $description = 'phpMyFaq CSV export file';
                $mimeType = 'text/csv';
                break;
            case 'json':
                $filename = 'phpmyfaq.json';
                $description = 'phpMyFaq JSON export file';
                $mimeType = 'application/json';
                break;
            // In this case, no default statement is required:
            // the one above is just for clean coding style
            default:
                $filename = 'phpmyfaq.pmf';
                $description = 'Generic file';
                $mimeType = 'application/octet-stream';
                break;
        }

        $filename = Export::getExportTimestamp() . '_' . $filename;

        // Set the correct HTTP headers:
        // 1. Prevent proxies&browsers caching
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Expires: 0');
        header('Cache-Control: private, no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');

        // 2. Set the correct values for file streaming
        header('Content-Type: ' . $mimeType);
        if (
            ($this->disposition == self::HTTP_CONTENT_DISPOSITION_ATTACHMENT)
            && isset($_SERVER['HTTP_USER_AGENT']) && !(!str_contains((string) $_SERVER['HTTP_USER_AGENT'], 'MSIE'))
        ) {
            header('Content-Type: application/force-download');
        }
        // RFC2616, �19.5.1: $filename must be a quoted-string
        header('Content-Disposition: ' . $this->disposition . '; filename="phpMyFAQ_' . $filename . '"');
        header('Content-Description: ' . $description);
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: none');
        header('Content-Length: ' . $this->size);
    }

    /**
     * Streams the content.
     */
    private function streamContent(): void
    {
        echo $this->content;
    }
}
