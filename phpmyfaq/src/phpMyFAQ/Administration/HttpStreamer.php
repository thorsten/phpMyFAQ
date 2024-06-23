<?php

/**
 * Simple HTTP Streamer based on Symfony HttpFoundation.
 * This class manages the stream of a generic content
 * taking into account the correct http headers settings
 *
 * Currently, it supports only 3 content (mime) types:
 * - PDF: application/pdf
 * - CSV: text/csv
 * - JSON: application/json
 * - Generic file: application/octet-stream
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-02
 */

namespace phpMyFAQ\Administration;

use DateTime;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Export;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class HttpStreamer
 *
 * @package phpMyFAQ
 */
class HttpStreamer
{
    /** HTTP Content Disposition. */
    private string $disposition = HeaderUtils::DISPOSITION_INLINE;

    /** HTTP streaming data length. */
    private readonly int $size;

    private Response $response;

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

        $this->response = new Response();
        $this->setHttpHeaders();
        $this->response->setContent($this->streamContent());
        $this->response->send();
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
                $description = 'phpMyFAQ PDF export file';
                $mimeType = 'application/pdf';
                break;
            case 'csv':
                $filename = 'phpmyfaq.csv';
                $description = 'phpMyFAQ CSV export file';
                $mimeType = 'text/csv';
                break;
            case 'json':
                $filename = 'phpmyfaq.json';
                $description = 'phpMyFAQ JSON export file';
                $mimeType = 'application/json';
                break;
            // In this case, no default statement is required:
            // the one above is just for clean coding style
            default:
                $filename = 'phpmyfaq.pmf';
                $description = 'phpMyFAQ Generic export file';
                $mimeType = 'application/octet-stream';
                break;
        }

        $filename = Export::getExportTimestamp() . '_' . $filename;

        // Set the correct HTTP headers:
        // 1. Prevent proxies&browsers caching
        $this->response->setLastModified(new DateTime());
        $this->response->setExpires(new DateTime());
        $this->response->setCache([
            'must_revalidate'  => true,
            'no_cache'         => true,
            'no_store'         => true,
            'no_transform'     => false,
            'public'           => false,
            'private'          => true,
        ]);

        // 2. Set the correct values for file streaming
        $this->response->headers->set('Content-Type', $mimeType);

        // 3. RFC2616, �19.5.1: $filename must be a quoted-string
        $disposition = HeaderUtils::makeDisposition($this->disposition, $filename);
        $this->response->headers->set('Content-Disposition', $disposition);
        $this->response->headers->set('Content-Description', $description);
        $this->response->headers->set('Content-Transfer-Encoding', 'binary');
        $this->response->headers->set('Accept-Ranges', 'none');
        $this->response->headers->set('Content-Length', (string) $this->size);
    }

    /**
     * Streams the content.
     */
    private function streamContent(): string
    {
        return $this->content;
    }
}
