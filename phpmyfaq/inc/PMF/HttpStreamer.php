<?php

/**
 * Simple HTTP Streamer.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 *  v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-02
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_HttpStreamer Class.
 *
 * This class manages the stream of a generic content
 * taking into account the correct http headers settings
 *
 * Currently it supports only 4 content (mime) types:
 * - PDF: application/pdf
 * - XHTML: application/xhtml+xml
 * - XML: text/xml
 * - Generic file: application/octet-stream
 *
 * @category  phpMyFAQ
 *
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-02
 */
class PMF_HttpStreamer
{
    /**
     * HTTP content disposition attachment constant.
     *
     * @var string
     */
    const HTTP_CONTENT_DISPOSITION_ATTACHMENT = 'attachment';

    /**
     * HTTP content disposition inline constant.
     *
     * @var string
     */
    const HTTP_CONTENT_DISPOSITION_INLINE = 'inline';

    /**
     * Disposition attachment constant.
     *
     * @var string
     */
    const EXPORT_DISPOSITION_ATTACHMENT = 'attachment';

    /**
     * Disposition inline constant.
     *
     * @var string
     */
    const EXPORT_DISPOSITION_INLINE = 'inline';

    /**
     * Enable buffer.
     *
     * @var bool
     */
    const EXPORT_BUFFER_ENABLE = true;

    /**
     * PMF export data type.
     *
     * @var string
     */
    private $type;

    /**
     * HTTP Content Disposition.
     *
     * @var string
     */
    private $disposition;

    /**
     * HTTP streaming data.
     *
     * @var string
     */
    private $content;

    /**
     * HTTP streaming data length.
     *
     * @var int
     */
    private $size;

    /**
     * Constructor.
     *
     * @param string $type    Type
     * @param string $content Content
     *
     * @return PMF_HttpStreamer
     */
    public function __construct($type, $content)
    {
        $this->type = $type;
        $this->disposition = self::HTTP_CONTENT_DISPOSITION_INLINE;
        $this->content = $content;
        $this->size = strlen($this->content);
    }

    /**
     * Sends data.
     *
     * @param string $disposition Disposition
     */
    public function send($disposition)
    {
        if (isset($disposition)) {
            $this->disposition = $disposition;
        }

        // Sanity checks
        if (headers_sent()) {
            die('<b>PMF_HttpStreamer Class</b> error: unable to send my headers: someone already sent other headers!');
        }
        if (self::EXPORT_BUFFER_ENABLE) {
            if (ob_get_contents()) {
                die('<b>PMF_HttpStreamer Class</b> error: unable to send my data: someone already sent other data!');
            }
        }

        // Manage output buffering
        if (self::EXPORT_BUFFER_ENABLE) {
            ob_start();
        }
        // Send the right HTTP headers
        $this->_setHttpHeaders();
        // Send the raw content
        $this->_streamContent();
        // Manage output buffer flushing
        if (self::EXPORT_BUFFER_ENABLE) {
            ob_end_flush();
        }
    }

    /**
     * Sends HTTP Headers.
     */
    private function _setHttpHeaders()
    {
        $filename = $description = $mimeType = '';

        // Evaluate data upon export type request
        switch ($this->type) {
            case 'pdf':
                $filename = 'phpmyfaq.pdf';
                $description = 'phpMyFaq PDF export file';
                $mimeType = 'application/pdf';
                break;
            case 'xhtml':
                $filename = 'phpmyfaq.xhtml';
                $description = 'phpMyFaq XHTML export file';
                $mimeType = 'text/html';
                // Why not: text/html or text/xml?
                // See e.g.: http://www.hixie.ch/advocacy/xhtml
                // Unfortunaltelly IE doesn't handle it correctly :(
                // so currenctly we must use text/html as default.
                // See e.g.: http://keystonewebsites.com/articles/mime_type.php
                if (isset($_SERVER['HTTP_ACCEPT']) && !(strpos($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml') === false)) {
                    $mimeType = 'application/xhtml+xml';
                }
                break;
            case 'xml':
                $filename = 'phpmyfaq.xml';
                $description = 'phpMyFaq XML export file';
                $mimeType = 'text/xml';
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
            // In this case no default statement is required:
            // the one above is just for clean coding style
            default:
                $filename = 'phpmyfaq.pmf';
                $description = 'Generic file';
                $mimeType = 'application/octet-stream';
                break;
        }

        // Set the correct HTTP headers:
        // 1. Prevent proxies&browsers caching
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header('Expires: 0');
        header('Cache-Control: private, no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');

        // 2. Set the correct values for file streaming
        header('Content-Type: '.$mimeType);
        if (($this->disposition == self::HTTP_CONTENT_DISPOSITION_ATTACHMENT) &&
             isset($_SERVER['HTTP_USER_AGENT']) && !(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') === false)) {
            header('Content-Type: application/force-download');
        }
        // RFC2616, �19.5.1: $filename must be a quoted-string
        header('Content-Disposition: '.$this->disposition.'; filename="'.PMF_Export::getExportTimestamp().'_'.$filename.'"');
        if (!empty($description)) {
            header('Content-Description: '.$description);
        }
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: none');
        header('Content-Length: '.$this->size);
    }

    /**
     * Streams the content.
     */
    private function _streamContent()
    {
        print $this->content;
    }
}
